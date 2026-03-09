<?php
require_once __DIR__ . '/includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security_headers.php';
require_once '../includes/support_workflow.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

dd_support_ensure_tables($conn);

$message = '';
$error = '';
$validStatuses = ['new', 'in_progress', 'scheduled', 'resolved', 'closed'];
$validPriorities = ['low', 'medium', 'high'];
$validTypes = ['support', 'installation'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_request'])) {
    $requestType = trim((string)($_POST['request_type'] ?? 'support'));
    $subject = trim((string)($_POST['subject'] ?? ''));
    $requestMessage = trim((string)($_POST['message'] ?? ''));
    $location = trim((string)($_POST['location'] ?? ''));
    $systemSize = trim((string)($_POST['system_size'] ?? ''));
    $visitDate = trim((string)($_POST['preferred_visit_date'] ?? ''));
    $contactName = trim((string)($_POST['contact_name'] ?? ''));
    $contactEmail = trim((string)($_POST['contact_email'] ?? ''));
    $contactPhone = trim((string)($_POST['contact_phone'] ?? ''));
    $status = trim((string)($_POST['status'] ?? 'new'));
    $priority = trim((string)($_POST['priority'] ?? 'medium'));
    $notifyCustomer = isset($_POST['notify_customer']) && $_POST['notify_customer'] === '1';
    $adminResponse = trim((string)($_POST['admin_response'] ?? ''));

    if (
        !in_array($requestType, $validTypes, true) ||
        !in_array($status, $validStatuses, true) ||
        !in_array($priority, $validPriorities, true) ||
        $subject === '' ||
        $requestMessage === ''
    ) {
        $error = 'Invalid create payload. Please fill required fields.';
    } elseif (
        $requestType === 'installation' &&
        ($location === '' || $systemSize === '' || $contactName === '' || $contactPhone === '')
    ) {
        $error = 'Installation requests require location, system size, contact name and phone.';
    } else {
        $channel = 'admin_panel';
        $stmt = $conn->prepare(
            "INSERT INTO support_requests
             (request_type, user_id, subject, message, location, system_size, preferred_visit_date, channel, contact_name, contact_email, contact_phone, status, priority, admin_response, created_at, updated_at)
             VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );
        if (!$stmt) {
            $error = 'Failed to prepare create query.';
        } else {
            $stmt->bind_param(
                'sssssssssssss',
                $requestType,
                $subject,
                $requestMessage,
                $location,
                $systemSize,
                $visitDate,
                $channel,
                $contactName,
                $contactEmail,
                $contactPhone,
                $status,
                $priority,
                $adminResponse
            );
            if ($stmt->execute()) {
                $newId = (int)$stmt->insert_id;
                if ($notifyCustomer && ($contactEmail !== '' || $contactPhone !== '')) {
                    dd_support_notify_customer_status(
                        $conn,
                        [
                            'id' => $newId,
                            'request_type' => $requestType,
                            'user_id' => null,
                            'contact_email' => $contactEmail,
                            'contact_phone' => $contactPhone,
                        ],
                        $status,
                        $adminResponse
                    );
                }
                log_activity($conn, $_SESSION['admin_id'], 'create_support_request');
                $message = 'Request created successfully.';
            } else {
                $error = 'Failed to create request.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_request'])) {
    $requestId = (int)($_POST['request_id'] ?? 0);
    if ($requestId <= 0) {
        $error = 'Invalid request ID for delete.';
    } else {
        $conn->begin_transaction();
        try {
            $delNotif = $conn->prepare('DELETE FROM customer_notifications WHERE support_request_id = ?');
            if ($delNotif) {
                $delNotif->bind_param('i', $requestId);
                $delNotif->execute();
            }
            $delReq = $conn->prepare('DELETE FROM support_requests WHERE id = ?');
            if (!$delReq) {
                throw new RuntimeException('Failed to prepare delete query.');
            }
            $delReq->bind_param('i', $requestId);
            $delReq->execute();
            $conn->commit();
            log_activity($conn, $_SESSION['admin_id'], 'delete_support_request');
            $message = 'Request deleted successfully.';
        } catch (Throwable $e) {
            $conn->rollback();
            $error = 'Failed to delete request.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all_requests'])) {
    $confirmText = trim((string)($_POST['confirm_delete_all'] ?? ''));
    if ($confirmText !== 'DELETE ALL') {
        $error = 'Type "DELETE ALL" to confirm bulk deletion.';
    } else {
        $conn->begin_transaction();
        try {
            $conn->query('DELETE FROM customer_notifications WHERE support_request_id IS NOT NULL');
            $conn->query('DELETE FROM support_requests');
            $conn->commit();
            log_activity($conn, $_SESSION['admin_id'], 'delete_all_support_requests');
            $message = 'All support requests deleted.';
        } catch (Throwable $e) {
            $conn->rollback();
            $error = 'Failed to delete all requests.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_request'])) {
    $requestId = (int)($_POST['request_id'] ?? 0);
    $status = trim((string)($_POST['status'] ?? ''));
    $priority = trim((string)($_POST['priority'] ?? ''));
    $adminResponse = trim((string)($_POST['admin_response'] ?? ''));
    $notifyCustomer = isset($_POST['notify_customer']) && $_POST['notify_customer'] === '1';

    if ($requestId <= 0 || !in_array($status, $validStatuses, true) || !in_array($priority, $validPriorities, true)) {
        $error = 'Invalid request update payload.';
    } else {
        $getStmt = $conn->prepare("SELECT * FROM support_requests WHERE id = ? LIMIT 1");
        if ($getStmt) {
            $getStmt->bind_param('i', $requestId);
            $getStmt->execute();
            $res = $getStmt->get_result();
            $request = $res ? $res->fetch_assoc() : null;
        } else {
            $request = null;
        }

        if (!$request) {
            $error = 'Request not found.';
        } else {
            $stmt = $conn->prepare(
                "UPDATE support_requests
                 SET status = ?, priority = ?, admin_response = ?, updated_at = NOW()
                 WHERE id = ?"
            );
            if ($stmt) {
                $stmt->bind_param('sssi', $status, $priority, $adminResponse, $requestId);
                if ($stmt->execute()) {
                    if ($notifyCustomer) {
                        dd_support_notify_customer_status($conn, $request, $status, $adminResponse);
                    }
                    log_activity($conn, $_SESSION['admin_id'], 'update_support_request');
                    $message = 'Request updated successfully.';
                } else {
                    $error = 'Failed to update request.';
                }
            } else {
                $error = 'Failed to prepare update query.';
            }
        }
    }
}

$typeFilter = trim((string)($_GET['type'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));
$search = trim((string)($_GET['search'] ?? ''));

$where = [];
$params = [];
$types = '';

if ($typeFilter !== '') {
    $where[] = 'request_type = ?';
    $params[] = $typeFilter;
    $types .= 's';
}
if ($statusFilter !== '') {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}
if ($search !== '') {
    $where[] = '(subject LIKE ? OR message LIKE ? OR contact_name LIKE ? OR contact_phone LIKE ? OR contact_email LIKE ?)';
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $types .= 'sssss';
}

$sql = 'SELECT * FROM support_requests';
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY created_at DESC LIMIT 200';

$stmt = $conn->prepare($sql);
$requests = [];
if ($stmt) {
    if ($types !== '') {
        $bind = [];
        $bind[] = &$types;
        foreach ($params as $k => $v) {
            $bind[] = &$params[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

$counts = [
    'new' => 0,
    'in_progress' => 0,
    'scheduled' => 0,
    'resolved' => 0,
    'closed' => 0,
];
$countResult = $conn->query("SELECT status, COUNT(*) AS total FROM support_requests GROUP BY status");
if ($countResult) {
    while ($row = $countResult->fetch_assoc()) {
        $key = (string)$row['status'];
        if (isset($counts[$key])) {
            $counts[$key] = (int)$row['total'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Requests</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/brand.css">
</head>
<body class="bg-slate-900 text-slate-100">
<div class="flex min-h-screen">
    <?php include 'includes/sidebar.php'; ?>
    <main class="flex-1 p-10">
        <div class="bg-slate-800 rounded-xl p-6 mb-6 border border-white/10">
            <p class="text-slate-400 text-sm uppercase tracking-wide">Operations</p>
            <h1 class="text-3xl font-bold text-white mt-1">Support & Installation Requests</h1>
            <p class="text-slate-400 mt-2">Track customer issues, update statuses, and notify customers from one place.</p>
        </div>

        <?php if ($message !== ''): ?>
            <div class="mb-4 rounded-xl bg-emerald-600/20 border border-emerald-600 text-emerald-200 px-4 py-3"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="mb-4 rounded-xl bg-red-600/20 border border-red-600 text-red-200 px-4 py-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
            <form method="POST" class="xl:col-span-2 bg-slate-800 rounded-xl p-5 border border-white/10 grid grid-cols-1 md:grid-cols-2 gap-3">
                <input type="hidden" name="create_request" value="1">
                <h2 class="md:col-span-2 text-lg font-semibold text-white">Create Request</h2>
                <p class="md:col-span-2 text-sm text-slate-400 -mt-2 mb-1">Create a support ticket or installation request on behalf of a customer.</p>
                <select name="request_type" class="px-3 py-2 rounded bg-slate-700 border border-slate-600" required>
                    <option value="support">Support</option>
                    <option value="installation">Installation</option>
                </select>
                <select name="status" class="px-3 py-2 rounded bg-slate-700 border border-slate-600">
                    <?php foreach ($validStatuses as $st): ?>
                        <option value="<?= htmlspecialchars($st) ?>"><?= htmlspecialchars(str_replace('_', ' ', $st)) ?></option>
                    <?php endforeach; ?>
                </select>
                <input name="subject" placeholder="Subject" class="md:col-span-2 px-3 py-2 rounded bg-slate-700 border border-slate-600" required />
                <textarea name="message" rows="3" placeholder="Message" class="md:col-span-2 px-3 py-2 rounded bg-slate-700 border border-slate-600" required></textarea>
                <input name="contact_name" placeholder="Contact name" class="px-3 py-2 rounded bg-slate-700 border border-slate-600" />
                <input name="contact_phone" placeholder="Contact phone" class="px-3 py-2 rounded bg-slate-700 border border-slate-600" />
                <input name="contact_email" placeholder="Contact email" class="md:col-span-2 px-3 py-2 rounded bg-slate-700 border border-slate-600" />
                <input name="location" placeholder="Location (installation)" class="px-3 py-2 rounded bg-slate-700 border border-slate-600" />
                <input name="system_size" placeholder="System size (installation)" class="px-3 py-2 rounded bg-slate-700 border border-slate-600" />
                <input name="preferred_visit_date" placeholder="Preferred visit date" class="md:col-span-2 px-3 py-2 rounded bg-slate-700 border border-slate-600" />
                <select name="priority" class="px-3 py-2 rounded bg-slate-700 border border-slate-600">
                    <?php foreach ($validPriorities as $pv): ?>
                        <option value="<?= htmlspecialchars($pv) ?>"><?= ucfirst($pv) ?></option>
                    <?php endforeach; ?>
                </select>
                <input name="admin_response" placeholder="Initial admin response (optional)" class="px-3 py-2 rounded bg-slate-700 border border-slate-600" />
                <label class="md:col-span-2 inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="notify_customer" value="1">
                    Notify customer after create
                </label>
                <div class="md:col-span-2">
                    <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold rounded px-4 py-2">Create Request</button>
                </div>
            </form>

            <form method="POST" class="bg-red-950/40 border border-red-700 rounded-xl p-5 flex flex-col gap-3">
                <input type="hidden" name="delete_all_requests" value="1">
                <h2 class="text-lg font-semibold text-red-200">Danger Zone</h2>
                <p class="text-sm text-red-300">Delete all support and installation requests.</p>
                <input
                    name="confirm_delete_all"
                    placeholder='Type DELETE ALL'
                    class="px-3 py-2 rounded bg-slate-900 border border-red-700 text-white"
                    required
                />
                <button
                    type="submit"
                    class="bg-red-600 hover:bg-red-700 text-white font-bold rounded px-4 py-2"
                    onclick="return confirm('This will permanently delete all requests. Continue?');"
                >
                    Delete All Requests
                </button>
            </form>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
            <?php foreach ($counts as $k => $v): ?>
                <div class="bg-slate-800 rounded-xl p-3 border border-white/10">
                    <p class="text-slate-400 text-xs uppercase"><?= htmlspecialchars(str_replace('_', ' ', $k)) ?></p>
                    <p class="text-2xl font-bold"><?= $v ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="GET" class="bg-slate-800 rounded-xl p-5 border border-white/10 mb-6 grid grid-cols-1 md:grid-cols-4 gap-3">
            <select name="type" class="px-3 py-2 rounded bg-slate-700 border border-slate-600">
                <option value="">All Types</option>
                <option value="support" <?= $typeFilter === 'support' ? 'selected' : '' ?>>Support</option>
                <option value="installation" <?= $typeFilter === 'installation' ? 'selected' : '' ?>>Installation</option>
            </select>
            <select name="status" class="px-3 py-2 rounded bg-slate-700 border border-slate-600">
                <option value="">All Statuses</option>
                <?php foreach (array_keys($counts) as $st): ?>
                    <option value="<?= htmlspecialchars($st) ?>" <?= $statusFilter === $st ? 'selected' : '' ?>>
                        <?= htmlspecialchars(str_replace('_', ' ', $st)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search customer/subject..." class="px-3 py-2 rounded bg-slate-700 border border-slate-600" />
            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold rounded px-4 py-2">Filter</button>
        </form>

        <div class="space-y-4">
            <?php foreach ($requests as $r): ?>
                <div class="bg-slate-800 rounded-xl p-4 border border-white/10">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                        <div>
                            <p class="font-semibold text-lg">#<?= (int)$r['id'] ?> - <?= htmlspecialchars($r['subject']) ?></p>
                            <p class="text-sm text-slate-400"><?= htmlspecialchars($r['request_type']) ?> | <?= htmlspecialchars($r['created_at']) ?></p>
                        </div>
                        <span class="px-2 py-1 rounded-full text-xs bg-slate-700 border border-white/10"><?= htmlspecialchars($r['status']) ?></span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm mb-3">
                        <p><span class="text-slate-400">Customer:</span> <?= htmlspecialchars((string)$r['contact_name']) ?></p>
                        <p><span class="text-slate-400">Phone:</span> <?= htmlspecialchars((string)$r['contact_phone']) ?></p>
                        <p><span class="text-slate-400">Email:</span> <?= htmlspecialchars((string)$r['contact_email']) ?></p>
                        <p><span class="text-slate-400">Priority:</span> <?= htmlspecialchars((string)$r['priority']) ?></p>
                        <?php if (!empty($r['location'])): ?>
                            <p><span class="text-slate-400">Location:</span> <?= htmlspecialchars((string)$r['location']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($r['system_size'])): ?>
                            <p><span class="text-slate-400">System Size:</span> <?= htmlspecialchars((string)$r['system_size']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="bg-slate-900/40 rounded-xl p-3 mb-3 text-sm whitespace-pre-wrap border border-white/10"><?= htmlspecialchars((string)$r['message']) ?></div>

                    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                        <input type="hidden" name="update_request" value="1">
                        <select name="status" class="px-3 py-2 rounded bg-slate-700 border border-slate-600">
                            <?php foreach (array_keys($counts) as $st): ?>
                                <option value="<?= htmlspecialchars($st) ?>" <?= $r['status'] === $st ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(str_replace('_', ' ', $st)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="priority" class="px-3 py-2 rounded bg-slate-700 border border-slate-600">
                            <?php foreach (['low', 'medium', 'high'] as $pv): ?>
                                <option value="<?= $pv ?>" <?= $r['priority'] === $pv ? 'selected' : '' ?>><?= ucfirst($pv) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <textarea name="admin_response" rows="3" placeholder="Response to customer..." class="md:col-span-2 px-3 py-2 rounded bg-slate-700 border border-slate-600"><?= htmlspecialchars((string)$r['admin_response']) ?></textarea>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="notify_customer" value="1" checked>
                            Notify customer (in-app + email if available)
                        </label>
                        <div>
                            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold rounded px-4 py-2">
                                Save Update
                            </button>
                        </div>
                    </form>
                    <form method="POST" class="mt-3">
                        <input type="hidden" name="delete_request" value="1">
                        <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                        <button
                            type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white font-bold rounded px-4 py-2"
                            onclick="return confirm('Delete request #<?= (int)$r['id'] ?> permanently?');"
                        >
                            Delete Request
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>

            <?php if (empty($requests)): ?>
                <div class="bg-slate-800 rounded-xl p-6 text-slate-400 border border-white/10">No requests found.</div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>


