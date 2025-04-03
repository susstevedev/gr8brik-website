<?php
// constants
session_name('loginid');
ini_set('session.save_path', $_SERVER['DOCUMENT_ROOT'] . '/acc/users/sessions/');
session_start();

define('DB_SERVER', 'sql209.infinityfree.com');
define('DB_USER', 'if0_36019408');
define('DB_PASSWORD', '5DPSN4dadTu');
define('DB_NAME', 'if0_36019408_membership');
define('DB_NAME2', 'if0_36019408_creations');
define('DB_NAME3', 'if0_36019408_forum');

if (!isset($_SESSION['requests'])) {
    $_SESSION['requests'] = [];
}

$_SESSION['requests'] = array_filter($_SESSION['requests'], function ($timestamp) {
    return $timestamp > time() - 60;
});

if (count($_SESSION['requests']) >= 60) {
    $oldest = min($_SESSION['requests']);
    $remaining = 60 - (time() - $oldest);
    $requests = $_SESSION['requests'];

    header("HTTP/1.0 429 Too Many Requests");
    $error = 'You are sending too many requests. Please wait ' .  $remaining . ' seconds.';
    if(isset($_GET['ajax'])) {
        echo json_encode(['error' => $error, 'requests' => $requests]);
    } else {
        echo 'Error: ' . $error;
    }
    exit;
}

$_SESSION['requests'][] = time();

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(128));
}

define('csrf', $_SESSION['csrf']);

if (isset($_GET['get_csrf_token'])) {
    echo json_encode(['csrf_token' => $_SESSION['csrf']]);
    exit;
}
?>