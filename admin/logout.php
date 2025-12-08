<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (isset($_SESSION['admin_id'])) {
    log_activity($conn, $_SESSION['admin_id'], 'logout');
}

session_destroy();
header('Location: login.php');
exit;
