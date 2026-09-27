<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/constants.php';
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
error_reporting(0);

if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on') {
    header("HTTP/1.0 403 Forbidden");
    echo json_encode(['error' => 'Encryption is required for this action']);
    exit;
}

$sessionid = $conn->real_escape_string(isset($_COOKIE['token']) ? $_COOKIE['token'] : null);
$sql = "SELECT * FROM sessions WHERE id = '$sessionid' LIMIT 1";
$tokendata = $conn->query($sql);
$token = $tokendata->fetch_assoc();

if(!isset($_COOKIE['token']) || $tokendata->num_rows === 0) {
    if(isset($_GET['ajax'])) {
        http_response_code(500);
        exit(json_encode(['error' => 'Invalid login']));
    }
} else {
    // for backwards compatiblity
    if(!isset($_SESSION['username'])) {
        $_SESSION['username'] = $conn->real_escape_string($token['user']);
        header("Refresh:0");
    }

    $id = $_SESSION['username'];
    $result = $conn->query("SELECT * FROM users WHERE id = '$id' LIMIT 1");

    while ($row = $result->fetch_assoc()) {
        $user = $conn->real_escape_string($row['username']);
        $pwd = $conn->real_escape_string($row['password']);
        $email = $conn->real_escape_string($row['email']);
        $about = $conn->real_escape_string($row['description']);
        $x = $conn->real_escape_string($row['twitter']);
        $admin = $conn->real_escape_string($row['admin']);
        $alert = $conn->real_escape_string($row['alert']);
        $age = $conn->real_escape_string($row['age']);
        $changed = $conn->real_escape_string($row['changed']);
        $pic = $conn->real_escape_string('/acc/users/pfps/' . $id . '.jpg');
    }

    if(!$_COOKIE['userdata']) {
        $userdata = ['user' => $user, 'email' => $email, 'pic' => $pic, 'about' => $about];
        setcookie('userdata', serialize($userdata), time()+60*60*24*365);
    }
        
    if($token['password'] != $pwd) {
        logout();
    }

    if($token['rate_limiting'] != 0 && $token['rate_limiting'] != time() + 30) {
        logout();
    }

    if($_SERVER['REQUEST_TIME'] - 2592000 < strtotime($row['timestamp'])) {
        logout();
    }

    $logindata = json_encode([
        'success' => 'You are logged in', 
        'requested' => time(), 
        'token' => $sessionid, 
        'id' => $id,  
        'user' => $user, 
        'pwd' => $pwd, 
        'email' => $email, 
        'about' => $about, 
        'x' => $x, 
        'admin' => $admin, 
        'alert' => $alert, 
        'age' => $age, 
        'changed' => $changed, 
        'pic' => $pic, 
    ]);

    if(isset($_GET['ajax'])) {
        header('Content-type: application/json');
        echo $logindata;
        exit;
    }
}

function logout() {
    global $tokendata;
    global $conn;
    
    if(isset($_COOKIE['token'])) {
        if ($tokendata->num_rows != 0) {
            $conn->query("DELETE FROM sessions WHERE id = '$sessionid' LIMIT 1");
        }
        setcookie("token", "", time() - 3600);
    }
	session_destroy();
}

function isLoggedin() {
    global $tokendata;

    if (isset($_COOKIE['token'])) {
        if ($tokendata && $tokendata->num_rows != 0) {
            if ($_SERVER['PHP_SELF'] === '/acc/login.php' || $_SERVER['PHP_SELF'] === '/acc/register.php' || $_SERVER['PHP_SELF'] === '/index.php') {
                header('Location: /list.php?sort=following');
                exit;
            }
        } else {
            logout();
            if ($_SERVER['PHP_SELF'] != '/acc/login.php' && $_SERVER['PHP_SELF'] != '/acc/register.php' && $_SERVER['PHP_SELF'] != '/index.php') {
                header('Location: /acc/login.php');
                exit;
            }
        }
    } else {
        if ($_SERVER['PHP_SELF'] != '/acc/login.php' && $_SERVER['PHP_SELF'] != '/acc/register.php' && $_SERVER['PHP_SELF'] != '/index.php') {
            header('Location: /acc/login.php');
            exit;
        }
    }
}
?>