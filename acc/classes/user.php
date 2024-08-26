<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/newsite/acc/classes/constants.php';

// Create connection
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    echo "Database connection failed. Please check your database configuration.";
    die;
}

$session = $_SESSION['username'];
$sql = "SELECT id, username, handle, email, password, description, age, admin, alert FROM users WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $session);
$stmt->execute();
$stmt->bind_result($id, $user, $handle, $email, $pwd, $about, $age, $admin, $alert);
$stmt->fetch();
$stmt->close();
?>