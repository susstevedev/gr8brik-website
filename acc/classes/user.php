<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/constants.php';

$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    exit($conn->connect_error);
}
if(isset($_SESSION['username'])) {
    session_start();
    
    $id = $_SESSION['username'];
    $sql = "SELECT * FROM users WHERE id = $id";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    $user = $row['username'];
    $pwd = $row['password'];
    $email = $row['email'];
    $about = $row['description'];
    $x = $row['twitter'];
    $admin = $row['admin'];
    $alert = $row['alert'];
    $age = $row['age'];
    $pic = md5($id);

    $sql2 = "SELECT * FROM bans WHERE user = '$id'";
    $result2 = $conn->query($sql2);
    $row2 = $result2->fetch_assoc();
        
}
?>