<?php
ob_start(); // Start output buffering
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Set content type header early
header('Content-Type: application/json');

// Check if admin is logged in and is a super user
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'super') {
    ob_end_clean(); // Discard any buffered output before sending error
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_users':
            $result = $conn->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
            $users = [];
            while($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            ob_end_clean(); // Discard any buffered output
            echo json_encode(['status' => 'success', 'users' => $users]);
            break;

        case 'get_user':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('User ID is required.');
            }
            $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
            }
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            ob_end_clean(); // Discard any buffered output
            if ($user) {
                echo json_encode(['status' => 'success', 'user' => $user]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'User not found.']);
            }
            $stmt->close();
            break;

        case 'save_user':
            $id = $_POST['id'] ?? null;
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'admin'; // Default to 'admin' if not provided

            if (empty($username) || empty($email)) {
                throw new Exception('Username and Email are required.');
            }

            if ($id) { // Update
                if (empty($password)) {
                    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
                    if (!$stmt) {
                        throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
                    }
                    $stmt->bind_param("sssi", $username, $email, $role, $id);
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?");
                    if (!$stmt) {
                        throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
                    }
                    $stmt->bind_param("ssssi", $username, $email, $hashed_password, $role, $id);
                }
                $action_string = 'edit_user: ' . $id;
            } else { // Insert
                if (empty($password)) {
                    throw new Exception('Password is required for new users.');
                }
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                if (!$stmt) {
                    throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
                }
                $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
                $action_string = 'add_user';
            }

            if ($stmt->execute()) {
                if (!$id) {
                    $id = $stmt->insert_id;
                    $action_string = 'add_user: ' . $id;
                }
                log_activity($conn, $_SESSION['admin_id'], $action_string);
                ob_end_clean(); // Discard any buffered output
                echo json_encode(['status' => 'success']);
            } else {
                if ($conn->errno == 1062) { // Duplicate entry
                    ob_end_clean(); // Discard any buffered output
                    echo json_encode(['status' => 'error', 'message' => 'Username or Email already exists.']);
                } else {
                    throw new Exception("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
                }
            }
            $stmt->close();
            break;

        case 'delete_user':
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception('User ID is required.');
            }
            
            log_activity($conn, $_SESSION['admin_id'], 'delete_user: ' . $id);

            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
            }
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                ob_end_clean(); // Discard any buffered output
                echo json_encode(['status' => 'success']);
            } else {
                throw new Exception("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            $stmt->close();
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    ob_end_clean(); // Discard any buffered output before sending error
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// Ensure no extra output
exit;