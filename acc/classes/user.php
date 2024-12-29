<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/constants.php';

$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    exit($conn->connect_error);
}

if(isset($_COOKIE['token']) || isset($_SESSION['username'])) {    
    $sessionid = $_COOKIE['token'];
    $sql = "SELECT * FROM sessions WHERE id = '$sessionid'";
    $tokendata = $conn->query($sql);
    $token = $tokendata->fetch_assoc();

    if($tokendata->num_rows > 0) {
        $_SESSION['username'] = $token['user'];
    } else {
        session_destroy();
    }

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
    $changed = $row['changed'];
    $pic = md5($id);

    /*if($token['username'] != $row['username']) {
        $sql = "UPDATE sessions SET username = '$user' WHERE id = '$sessionid'";
    }*/

}
?>