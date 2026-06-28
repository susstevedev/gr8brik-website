<?php
/*
    2025 Gr8brik team
    This page, auth.php, is exactly what it sounds like: an authentication system used by Gr8brik
*/
    
header('Content-type: application/json');
error_reporting(0);
include $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';

class SessionManager {
    public $session;

    function setSession($session) {
        $this->session = $session;
    }
    
    function revokeSession() {
        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

        if ($conn->connect_error) {
            header('HTTP/1.0 500 Internal Server Error');
            return ['success' => false, 'error' => "Database connection failed"];
        }

        $session = $this->session;

        $check = $conn->prepare("SELECT id FROM sessions WHERE id = ?");
        $check->bind_param("s", $session);
        $check->execute();
        $check->store_result();
        if ($check->num_rows === 0) {
            return ['success' => false, 'error' => "Session could not be found or selected."];
        } elseif($_COOKIE['token'] === $session) {
            return ['success' => false, 'error' => "Session could not be deleted as it is the session currently being used."];
        }

        $stmt = $conn->prepare("DELETE FROM sessions WHERE id = ? LIMIT 1");
        $stmt->bind_param("s", $session);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => "Session deleted with success."];
        } else {
            header('HTTP/1.0 404 Not Found');
            return ['success' => false, 'error' => "Could not delete session."];
        }

        $stmt->close();
        $conn->close();
        return false;
    }
}

if (isset($_GET['revoke']) && isset($_GET['tokenId'])) {
    $revoke = new SessionManager();
    $tokenId = $_GET['tokenId'];
	$revoke->setSession($tokenId);
	$res = $revoke->revokeSession();
    echo json_encode($res);
    exit;
}

class AccountManager {
    function verifyAccount() {
        global $users_row;
        if(!loggedin() || !isset($users_row)) {
            return "User is not authenticated";
        }

        $userid = $users_row['id'];
        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

        $stmt = $conn->prepare("UPDATE users SET verify_token = NULL WHERE id = ?");
        $stmt->bind_param("i", $userid);
        if ($stmt->execute()) {
            header('Location:../list.php');
            return true;
        }
    }
    
    function login_user($user, $pwd, $remember) {
        $conn = mysqli_connect(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        if (mysqli_connect_errno()) {
            header('HTTP/1.0 500 Internal Server Error');
            return ['error' => "Database connection failed"];
        }       

        $user = mysqli_real_escape_string($conn, htmlspecialchars(urldecode($user)));
        $pwd = mysqli_real_escape_string($conn, $pwd);
        $remember = ($remember === 'true') ? 1 : 0;

        if (empty($user)) {
            header('HTTP/1.0 500 Internal Server Error');
            return ['error' => "Email or username cannot be blank"];
        }

        $sql = "SELECT * FROM users WHERE email = '$user' OR username = '$user' LIMIT 1";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($result);

        //if (mysqli_num_rows($result) > 0) {
            // Hashing
            $db_hashed_pwd = null;

            if (!empty($row['salt'])) {
                if (md5($pwd . $row['salt']) === $row['password']) {
                    $db_hashed_pwd = md5($pwd . $row['salt']);
                }
            } elseif (md5($pwd) === $row['password']) {
                $db_hashed_pwd = md5($pwd);
            } elseif (password_verify($pwd, $row['password'])) {
                $db_hashed_pwd = null;
            }

            if (mysqli_num_rows($result) > 0 && password_verify($pwd, $row['password']) || mysqli_num_rows($result) > 0 && $db_hashed_pwd !== null) {
                $userid = $row['id'];
                $login_from = $_SERVER['REMOTE_ADDR'];
                $tokenid = uniqid();
                $time = time();
                $user_agent = $_SERVER['HTTP_USER_AGENT'];

                // Rehash legacy password
                if($db_hashed_pwd !== null) {
                    $new_hash = password_hash($pwd, PASSWORD_DEFAULT);
                    $new_salt = null;

                    $stmt = $conn->prepare("UPDATE users SET password = ?, salt = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $new_hash, $new_salt, $userid);

                    if (!$stmt->execute()) {
                        return ['error' => "Error rehashing legacy account password. Please contact us at " . DB_MAIL];
                    }
                }

                $date8 = new DateTime('9998-12-29'); // setting deactive date anything after this will result in a "hard ban" (no chance for appeal no expire)
                
                // For account deactivation (soft deletion until a certain date)
                if (!empty($row['deactive'])) {
                    if ($_SERVER['REQUEST_TIME'] - 2592000 < strtotime($row['deactive']) && $date8->format('Y-m-d') > $row['deactive']) {
                        $sql = "INSERT INTO sessions (id, user, timestamp) VALUES ('$tokenid', '$userid', '$time') LIMIT 1";
                        if (mysqli_query($conn, $sql)) {
                            header('HTTP/1.0 500 Internal Server Error');
                            return ['popup' => "Do you want to reactivate your account?", 'error' => "See modal for more info", 'goto' => "/acc/index?reactive=1&token=" . $tokenid];
                        }
                    } else {
                        header('HTTP/1.0 500 Internal Server Error');
                        return false;
                    }
                }

                // Account ban system
                $sql = "SELECT * FROM bans WHERE user = $userid AND end_date >= UNIX_TIMESTAMP() ORDER BY end_date DESC LIMIT 1";
                $result2 = mysqli_query($conn, $sql);

                while ($row2 = mysqli_fetch_assoc($result2)) {
                    if (!empty($result2)) {
                        header('HTTP/1.0 500 Internal Server Error');
                        return ['error' => "This account has been banned until " . date("M d, Y H:i", $row2['end_date']) . ". " . htmlspecialchars($row2['reason'])];
                    }
                }

                // Create login session
                $sql = "INSERT INTO sessions (id, login_from, user_agent, user, password, timestamp, remember) VALUES ('$tokenid', '$login_from', '$user_agent', '$userid', '$db_hashed_pwd', '$time', '$remember') LIMIT 1";
                if (mysqli_query($conn, $sql)) {
                    if($remember === 1) {
                        setcookie('token', $tokenid, [
                            'expires' => $time + (60 * 60 * 24 * 400),
                            'path' => '/',
                            'domain' => '.gr8brik.rf.gd',
                            'secure' => false,
                            'httponly' => true,
                            'samesite' => 'Lax'
                        ]);
                    } else {
                        if(isset($_COOKIE['token'])) {
                            $_COOKIE['token'] = null;
                        }
                    }
                    
                    $_SESSION['userid'] = $userid;
                    $_SESSION['tokenid'] = $tokenid;
                    return ['success' => true];
                }
            } else {
                header('HTTP/1.0 500 Internal Server Error');
                return ['error' => "Invalid combination of email or username and password."];
            }
        
        return ['error' => 'An error occured. Please try again later.'];
    }
}

if(isset($_GET['verify_account'])) {
    $accManager = new AccountManager();
	echo $accManager->verifyAccount();
    exit;
}

if (isset($_POST['login'])) {
    $accManager = new AccountManager();
	echo json_encode($accManager->login_user($_POST['mail'], $_POST['pwd'], $_POST['remember']));
    exit;
}

/*function login_user($user, $pwd) {
    $conn = mysqli_connect(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if (mysqli_connect_errno()) {
        header('HTTP/1.0 500 Internal Server Error');
        return ['error' => "Database connection failed"];
    }       

    $user = mysqli_real_escape_string($conn, htmlspecialchars(urldecode($user)));
    $pwd = mysqli_real_escape_string($conn, $pwd);

    if (empty($user)) {
        header('HTTP/1.0 500 Internal Server Error');
        return ['error' => "Email or username cannot be blank"];
    }

    $sql = "SELECT * FROM users WHERE email = '$user' OR username = '$user' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    if (mysqli_num_rows($result) > 0) {
        // Hashing

        $db_hashed_pwd = null;

        if (!empty($row['salt'])) {
            if (md5($pwd . $row['salt']) === $row['password']) {
                $db_hashed_pwd = md5($pwd . $row['salt']);
            }
        } elseif (md5($pwd) === $row['password']) {
            $db_hashed_pwd = md5($pwd);
        } elseif (password_verify($pwd, $row['password'])) {
            $db_hashed_pwd = null;
        }

        if ($db_hashed_pwd !== null || password_verify($pwd, $row['password'])) {
            $userid = $row['id'];
            $login_from = $_SERVER['REMOTE_ADDR'];
            $tokenid = uniqid();
            $time = time();
            $user_agent = $_SERVER['HTTP_USER_AGENT'];

            // Rehash legacy password
            if($db_hashed_pwd !== null) {
                $new_hash = password_hash($pwd, PASSWORD_DEFAULT);
                $new_salt = null;

                $stmt = $conn->prepare("UPDATE users SET password = ?, salt = ? WHERE id = ?");
                $stmt->bind_param("ssi", $new_hash, $new_salt, $userid);

                if (!$stmt->execute()) {
                    return ['error' => "Error rehashing password. Please contact us at " . DB_MAIL];
                }
            }

            // For account deactivation (soft deletion until a certain date)
            if (!empty($row['deactive'])) {
                if ($_SERVER['REQUEST_TIME'] - 2592000 < strtotime($row['deactive'])) {
                    $sql = "INSERT INTO sessions (id, user, timestamp) VALUES ('$tokenid', '$userid', '$time') LIMIT 1";
                    if (mysqli_query($conn, $sql)) {
                        header('HTTP/1.0 500 Internal Server Error');
                        return ['popup' => "Do you want to reactivate your account?", 'error' => "See modal for more info", 'goto' => "/acc/index?reactive=1&token=" . $tokenid];
                    }
                } else {
                    header('HTTP/1.0 500 Internal Server Error');
                    return ['error' => "This account has been deactivated more than 30 days ago and cannot be reactivated."];
                }
            }

            // Account ban system
            $sql = "SELECT * FROM bans WHERE user = $userid AND end_date >= UNIX_TIMESTAMP() ORDER BY end_date DESC LIMIT 1";
            $result2 = mysqli_query($conn, $sql);

            while ($row2 = mysqli_fetch_assoc($result2)) {
                if (!empty($result2)) {
                    header('HTTP/1.0 500 Internal Server Error');
                    return ['error' => "This account has been banned until " . date("M d, Y H:i", $row2['end_date']) . ". " . htmlspecialchars($row2['reason'])];
                }
            }

            // Create login session
            $sql = "INSERT INTO sessions (id, login_from, user_agent, user, password, timestamp) VALUES ('$tokenid', '$login_from', '$user_agent', '$userid', '$db_hashed_pwd', '$time') LIMIT 1";
            if (mysqli_query($conn, $sql)) {
                setcookie('token', $tokenid, [
                    'expires' => $time + (60 * 60 * 24 * 400),
                    'path' => '/',
                    'domain' => '.gr8brik.rf.gd',
                    'secure' => false,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
                $_SESSION['userid'] = $userid;
                return ['success' => true];
            }
        } else {
            header('HTTP/1.0 500 Internal Server Error');
            return ['error' => "Invalid password"];
        }
    } else {
        header('HTTP/1.0 500 Internal Server Error');
        return ['error' => "Invalid email or username"];
    }

    return ['error' => 'An error occured. Please try again later.'];
}

if (isset($_POST['login'])) {
    $login_user = login_user($_POST['mail'], $_POST['pwd']);
    echo json_encode($login_user);
    exit;
}*/

function register_user($username, $password, $email) {
    $conn = mysqli_connect(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if (mysqli_connect_errno()) {
        header('HTTP/1.0 500 Internal Server Error');
        return ['error' => "Database connection failed"];
    }

    $username = mysqli_real_escape_string($conn, htmlspecialchars((urldecode($username))));
    $email = mysqli_real_escape_string($conn, trim($email ?? ''));
    $salt = uniqid();
    $created = date("Y-m-d H:i:s");
    $bloguser = md5(uniqid());
    $tokenid = uniqid();
    $login_from = $_SERVER['REMOTE_ADDR'];

    if (empty($password) || empty($email) || empty($username)) {
        header("HTTP/1.0 400 Bad Request");
        if (empty($password)) {
            return ['error' => "Password field is blank"];
        }
        if (empty($email)) {
            return ['error' => "Email field is blank"];
        }
        if (empty($username)) {
            return ['error' => "Username field is blank"];
        }
    }
    
    $username_available = is_username_available($username);
    if ($username_available['available'] === '0') {
        header("HTTP/1.0 400 Bad Request");
        return ['error' => $username_available['reason']];
    }
        
    /*
    $letter = htmlspecialchars(substr(strtolower($username), 0, 1));
    $valid = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
    
   	if(!in_array($letter, $valid)) {
		$letter = "?";
    }
    
    $picture = '/ajax/pfp?method=default&letter=' . $letter;
    */

    $picture = "/img/no_image.png";
	
    if(strlen($password) > 255) {
        header("HTTP/1.0 400 Bad Request");
        return ['error' => "Password cannot be more than 255 characters"];
    }

    if(strlen($password) < 4) {
        header("HTTP/1.0 400 Bad Request");
        return ['error' => "Password cannot be less than 4 characters"];
    }

    // Don't yell at me because I used to use MD5
    //$password = md5($password . $salt);
    $password = password_hash($password, PASSWORD_DEFAULT);

    // Prevents re-registration for banned or deactivated accounts
    // Allow list replaced with not allowed list to avoid some issues with school emails
    $nonallowed_domains = array(
        'mailinator.com',
        '10minutemail.com',
        'guerrillamail.com',
        'tempmail.com',
        'trashmail.com',
        'yopmail.com',
        'emailondeck.com',
        'getnada.com',
        'dispostable.com',
        'fakeinbox.com'
    );

    $current_domain = strtolower(explode('@', $email)[1]);
    if (in_array($current_domain, $nonallowed_domains)) {
        header('HTTP/1.0 400 Bad Request');
        return ['error' => "Email address domain is not allowed."];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('HTTP/1.0 400 Bad Request');
        return ['error' => "Invalid email address format."];
    }

    if (!checkdnsrr($current_domain, 'MX')) {
        header('HTTP/1.0 400 Bad Request');
        return ['error' => "Email address cannot send and/or recive emails."];
    }
    
    $sql_check = "SELECT id FROM users WHERE email = '$email'";
    $stmt = mysqli_query($conn, $sql_check);

    if (mysqli_num_rows($stmt) > 0) {
        header("HTTP/1.0 400 Bad Request");
        return ['error' => "Email address is already in use."];
    }
    
    $sql = "INSERT INTO users (blog_user_id, username, password, email, age, picture, verify_token) VALUES ('$bloguser', '$username', '$password', '$email', '$created', '$picture', '$tokenid') LIMIT 1";
    if (mysqli_query($conn, $sql)) {
            $sql = "SELECT id FROM users WHERE username = '$username'";
            $result = mysqli_query($conn, $sql);
            $row2 = mysqli_fetch_assoc($result);

            $userid = $row2['id'];
            $time = time();

            // Create session token
            $sql = "INSERT INTO sessions (id, login_from, user, timestamp, remember) VALUES ('$tokenid', '$login_from', '$userid', '$time', '1') LIMIT 1";
            if (mysqli_query($conn, $sql)) {
                setcookie('token', $tokenid, [
                    'expires' => $time + (60 * 60 * 24 * 400),
                    'path' => '/',
                    'domain' => '.gr8brik.rf.gd',
                    'secure' => false,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
                $_SESSION['userid'] = $userid;
                $_SESSION['tokenid'] = $tokenid;
                return ['success' => true];
            }
   } else {
   		header("HTTP/1.0 500 Internal Server Error");
    	return ['error' => "Failed to register account."];
   	}

    return ['error' => 'An error occured. Please try again later.'];
}

if (isset($_POST['register'])) {
    $register_user = register_user(htmlspecialchars($_POST['name']), htmlspecialchars($_POST['pwd']), htmlspecialchars($_POST['mail']));
    echo json_encode($register_user);
    exit;
}
?>