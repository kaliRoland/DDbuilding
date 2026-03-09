<?php
require_once __DIR__ . '/includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security_headers.php';

// If the admin is not logged in, redirect to login page
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Only super admins can access backup functionality
if ($_SESSION['admin_role'] !== 'super') {
    header('Location: index.php');
    exit;
}

log_activity($conn, $_SESSION['admin_id'], 'view_backup_restore');

// Get database credentials for display and backup operations
$db_host = $db_host;
$db_name = $db_name;
$db_user = $db_username;
$db_pass = $db_password;

$message = '';
$message_type = '';

// Handle backup deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_backup'])) {
    $backup_filename = $_POST['delete_backup'];

    // Security check - only allow deletion of files in backups directory
    $backup_file_path = '../backups/' . basename($backup_filename);

    if (file_exists($backup_file_path) && is_file($backup_file_path)) {
        if (unlink($backup_file_path)) {
            $message = 'Backup deleted successfully!';
            $message_type = 'success';
            log_activity($conn, $_SESSION['admin_id'], 'delete_backup', "Filename: $backup_filename");
        } else {
            $message = 'Failed to delete backup file.';
            $message_type = 'error';
        }
    } else {
        $message = 'Backup file not found.';
        $message_type = 'error';
    }
}

// Handle backup creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_backup'])) {
    $backup_type = $_POST['backup_type'];
    $include_uploads = isset($_POST['include_uploads']);

    try {
        $backup_dir = '../backups';
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $backup_filename = "backup_{$timestamp}";

        if ($backup_type === 'database' || $backup_type === 'full') {
            // Database backup
            $db_backup_file = "{$backup_dir}/{$backup_filename}_database.sql";

            // Use mysqldump command with full path
            $mysqldump_path = 'c:\xampp\mysql\bin\mysqldump.exe';
            $command = "\"{$mysqldump_path}\" --host={$db_host} --user={$db_user}";

            // Only add password if it's not empty
            if (!empty($db_pass)) {
                $command .= " --password=\"{$db_pass}\"";
            }

            $command .= " {$db_name}";

            // Execute mysqldump and capture output
            $backup_content = shell_exec($command);

            if ($backup_content === null || trim($backup_content) === '') {
                throw new Exception('Database backup failed - no output from mysqldump');
            }

            // Write the backup content to file
            if (file_put_contents($db_backup_file, $backup_content) === false) {
                throw new Exception('Failed to write database backup to file');
            }

            // Verify the file was written and has content
            if (!file_exists($db_backup_file) || filesize($db_backup_file) === 0) {
                throw new Exception('Database backup file is empty');
            }
        }

        if ($backup_type === 'uploads' || $backup_type === 'full') {
            // Uploads directory backup
            $uploads_backup_file = "{$backup_dir}/{$backup_filename}_uploads.zip";

            // Create zip archive of uploads directory
            $zip = new ZipArchive();
            if ($zip->open($uploads_backup_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                $uploads_dir = '../uploads';
                if (is_dir($uploads_dir)) {
                    $files = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($uploads_dir),
                        RecursiveIteratorIterator::LEAVES_ONLY
                    );

                    foreach ($files as $file) {
                        if (!$file->isDir()) {
                            $filePath = $file->getRealPath();
                            $relativePath = substr($filePath, strlen($uploads_dir) + 1);
                            $zip->addFile($filePath, $relativePath);
                        }
                    }
                }
                $zip->close();
            } else {
                throw new Exception('Failed to create uploads backup archive');
            }
        }

        $message = 'Backup created successfully!';
        $message_type = 'success';
        log_activity($conn, $_SESSION['admin_id'], 'create_backup', "Type: $backup_type, Include uploads: " . ($include_uploads ? 'yes' : 'no'));

    } catch (Exception $e) {
        $message = 'Backup failed: ' . $e->getMessage();
        $message_type = 'error';
        error_log("Backup error: " . $e->getMessage());
    }
}

// Get list of existing backups
$backups = [];
$backup_dir = '../backups';
if (is_dir($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $file_path = $backup_dir . '/' . $file;
            $backups[] = [
                'filename' => $file,
                'size' => filesize($file_path),
                'date' => filemtime($file_path),
                'type' => strpos($file, '_database.sql') !== false ? 'database' : (strpos($file, '_uploads.zip') !== false ? 'uploads' : 'full')
            ];
        }
    }
    // Sort by date descending
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/brand.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-5NVJVRF7');</script>
    <!-- End Google Tag Manager -->
</head>
<body class="bg-slate-900 text-slate-100">
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5NVJVRF7"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
<body class="bg-slate-900 text-slate-100">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-800 p-6">
            <img src="../uploads/logo/logo.png" alt="DDbuildingTech Logo" class="h-8 mb-8">
            <nav>
                <a href="index.php" class="block py-2 px-4 rounded hover:bg-slate-700">Dashboard</a>
                <a href="products.php" class="block py-2 px-4 rounded hover:bg-slate-700">Products</a>
                <a href="manage_slides.php" class="block py-2 px-4 rounded hover:bg-slate-700">Hero Slides</a>
                <a href="add_product.php" class="block py-2 px-4 rounded hover:bg-slate-700" id="add-product-btn">Add New Product</a>
                <a href="gallery.php" class="block py-2 px-4 rounded hover:bg-slate-700">Manage Gallery</a>
                <a href="site_settings.php" class="block py-2 px-4 rounded hover:bg-slate-700">Site Settings</a>
                <a href="order_management.php" class="block py-2 px-4 rounded hover:bg-slate-700">Order Management</a>
                <a href="https://ddbuildingtech.com/blog" target="_blank" class="block py-2 px-4 rounded hover:bg-slate-700">Blog Management</a>
                <a href="manage_users.php" class="block py-2 px-4 rounded hover:bg-slate-700">Manage Users</a>
                <a href="backup_restore.php" class="block py-2 px-4 rounded bg-amber-500 text-slate-900">Backup & Restore</a>
                <a href="logout.php" class="block py-2 px-4 rounded hover:bg-slate-700 mt-4">Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-10">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-white">Backup & Restore</h1>
                <div class="text-sm text-slate-400">
                    Super Admin Only
                </div>
            </div>

            <?php if ($message): ?>
                <div class="bg-<?= $message_type === 'success' ? 'green' : 'red' ?>-600 text-white p-4 rounded mb-6">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Create Backup Section -->
            <div class="bg-slate-800 p-6 rounded-lg mb-8">
                <h2 class="text-xl font-bold text-white mb-4">Create New Backup</h2>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Backup Type</label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" name="backup_type" value="database" checked
                                       class="mr-2 text-amber-500 focus:ring-amber-500">
                                <span class="text-white">Database Only</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="backup_type" value="uploads"
                                       class="mr-2 text-amber-500 focus:ring-amber-500">
                                <span class="text-white">Uploads Directory Only</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="backup_type" value="full"
                                       class="mr-2 text-amber-500 focus:ring-amber-500">
                                <span class="text-white">Full Backup (Database + Uploads)</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="include_uploads" id="include_uploads"
                               class="mr-2 text-amber-500 focus:ring-amber-500 rounded">
                        <label for="include_uploads" class="text-white">Include uploads directory</label>
                    </div>

                    <button type="submit" name="create_backup"
                            class="bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold py-2 px-6 rounded">
                        Create Backup
                    </button>
                </form>
            </div>

            <!-- Existing Backups Section -->
            <div class="bg-slate-800 p-6 rounded-lg">
                <h2 class="text-xl font-bold text-white mb-4">Existing Backups</h2>

                <?php if (empty($backups)): ?>
                    <p class="text-slate-400">No backups found.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-slate-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Filename</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Size</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Date Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-slate-800 divide-y divide-slate-700">
                                <?php foreach ($backups as $backup): ?>
                                    <tr class="hover:bg-slate-700">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-white"><?= htmlspecialchars($backup['filename']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                <?php
                                                switch ($backup['type']) {
                                                    case 'database': echo 'bg-blue-100 text-blue-800'; break;
                                                    case 'uploads': echo 'bg-green-100 text-green-800'; break;
                                                    case 'full': echo 'bg-purple-100 text-purple-800'; break;
                                                    default: echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?= ucfirst($backup['type']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">
                                            <?= number_format($backup['size'] / 1024 / 1024, 2) ?> MB
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">
                                            <?= date('M j, Y g:i A', $backup['date']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="../backups/<?= urlencode($backup['filename']) ?>"
                                               download class="text-amber-400 hover:text-amber-300 mr-3">
                                                Download
                                            </a>
                                            <button onclick="deleteBackup('<?= htmlspecialchars($backup['filename']) ?>')"
                                                    class="text-red-400 hover:text-red-300">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- System Information -->
            <div class="mt-8 bg-slate-800 p-6 rounded-lg">
                <h2 class="text-xl font-bold text-white mb-4">System Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-white mb-2">Database</h3>
                        <p class="text-slate-400">Host: <?= htmlspecialchars($db_host ?? 'Unknown') ?></p>
                        <p class="text-slate-400">Database: <?= htmlspecialchars($db_name ?? 'Unknown') ?></p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-white mb-2">Directories</h3>
                        <p class="text-slate-400">Uploads: <?= is_dir('../uploads') ? 'Exists' : 'Missing' ?></p>
                        <p class="text-slate-400">Backups: <?= is_dir('../backups') ? 'Exists' : 'Will be created' ?></p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-800 rounded-lg max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-white mb-4">Confirm Deletion</h3>
                    <p class="text-slate-300 mb-6">Are you sure you want to delete this backup? This action cannot be undone.</p>
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="delete_backup" id="deleteBackupName">
                        <div class="flex gap-2">
                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded">
                                Delete Backup
                            </button>
                            <button type="button" onclick="closeDeleteModal()" class="bg-slate-600 hover:bg-slate-700 text-white font-bold py-2 px-4 rounded">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function deleteBackup(filename) {
            document.getElementById('deleteBackupName').value = filename;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Handle backup type selection
        document.querySelectorAll('input[name="backup_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const includeUploads = document.getElementById('include_uploads');
                if (this.value === 'full') {
                    includeUploads.checked = true;
                    includeUploads.disabled = true;
                } else {
                    includeUploads.disabled = false;
                }
            });
        });

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        // Initialize Lucide icons
        lucide.createIcons();
    </script>
</body>
</html>


