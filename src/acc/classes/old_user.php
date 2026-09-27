<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/constants.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/what_browser.php';

if(isset($_GET['src'])) {
    $value = urlencode($_GET['src']);
    setcookie("src", $value, time() + 3600); // expires in an hour
}

if(isset($_COOKIE['cookieControlPrefs'])) {
    $cookie = $_COOKIE['cookieControlPrefs'];
    $cookie_data = json_decode($cookie, true);

    if ($cookie_data != null) {
        $site_preferences = isset($cookie_data['site-preferences']) ? $cookie_data['site-preferences'] : null;
        $login_data = isset($cookie_data['login-data']) ? $cookie_data['login-data'] : null;
    }
}

$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    exit($conn->connect_error);
}

function logout() {
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if (isset($_COOKIE['token'])) {
        $tokenid = $_COOKIE['token'];
        $conn->query("DELETE FROM sessions WHERE id = '$tokenid'");
        setcookie("token", "", time() - 3600);
        setcookie('userdata', '', time() - 3600);
    }
	session_destroy();
    header("Refresh:0");
}

function isLoggedin() {
    $sessionid = isset($_COOKIE['token']) ? $_COOKIE['token'] : null;
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

    if ($_COOKIE['token']) { 
        $sql = "SELECT * FROM sessions WHERE id = '$sessionid'";
        $tokendata = $conn->query($sql);

        if ($tokendata->num_rows === 0) {
            setcookie("token", "", time() - 3600);
            if ($_SERVER['PHP_SELF'] != '/acc/login.php') { 
                header('Location: /acc/login.php');
                exit;
            }
        } else {
            $login = true;
        }
    } else { 
        if ($_SERVER['PHP_SELF'] != '/acc/login.php') { 
            header('Location: /acc/login.php');
            exit;
        }
    }

    if ($_SERVER['PHP_SELF'] === '/acc/login.php' && $login === true) { 
        header('Location: /index.php');
    }
}

if(isset($_COOKIE['token'])) {    
    $sessionid = $_COOKIE['token'];
    $sql = "SELECT * FROM sessions WHERE id = '$sessionid'";
    $tokendata = $conn->query($sql);
    $token = $tokendata->fetch_assoc();

    if($tokendata->num_rows > 0) {
        $_SESSION['username'] = $token['user'];

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

        if(!$_COOKIE['userdata']) {
            $userdata = ['user' => $user, 'email' => $email, 'pic' => $pic, 'about' => $about];
            setcookie('userdata', serialize($userdata), time()+60*60*24*365);
            header("Refresh:0");
            exit;
        }
        
        if($token['password'] != $row['password']) {
            logout();
        }

        if($token['rate_limiting'] != 0 && $token['rate_limiting'] != time() + 30) {
            header('HTTP/1.0 500 Internal Server Error');
            echo json_encode(['rate_limiting' => 'Sorry, your being rate limited due to weird activity. Please wait 30 seconds and try your action again.']);
        }

    } else {
        session_destroy();
    }
}
?>