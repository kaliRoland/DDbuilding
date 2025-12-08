<?php
$db_host = 'localhost';
$db_username = 'root';
$db_password = ''; // Your MySQL password
$db_name = 'ddnew_db';

$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
