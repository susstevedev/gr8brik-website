<?php
/*
    2026 Gr8brik
    This page, auth.php, is exactly what it sounds like: an authentication system used by Gr8brik
*/

require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/account_settings.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/what_browser.php';

class SessionManager
{
    public $session;

    function setSession($session)
    {
        $this->session = $session;
    }

    function revokeSession()
    {
        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

        if ($conn->connect_error) {
            http_response_code(500);
            return ['success' => false, 'error' => "Database connection failed"];
        }

        $session = $this->session;

        $check = $conn->prepare("SELECT id FROM sessions WHERE id = ?");
        $check->bind_param("s", $session);
        $check->execute();
        $check->store_result();

        if ($check->num_rows === 0) {
            http_response_code(404);
            return ['success' => false, 'error' => "Session could not be found or selected."];
        }

        $stmt = $conn->prepare("DELETE FROM sessions WHERE id = ? LIMIT 1");
        $stmt->bind_param("s", $session);

        if ($stmt->execute()) {
            http_response_code(200);
            return ['success' => true, 'message' => "Session was deleted."];
        } else {
            http_response_code(500);
            return ['success' => false, 'error' => "Could not delete session."];
        }
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

class AccountManager
{
    function verifyAccount()
    {
        global $current_user;
        if (!loggedin() || !isset($current_user)) {
            return "User is not authenticated";
        }

        if (!isset($_GET['bot'])) {
            return false;
        }

        $userid = $current_user->id;
        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

        $stmt = $conn->prepare("UPDATE users SET verify_token = NULL WHERE id = ?");
        $stmt->bind_param("i", $userid);
        if ($stmt->execute()) {
            header("Refresh:2;url=/list.php");
            return "Verified account";
        } else {
            return "An unknown error occured. Please try again later.";
        }
    }

    //new ban helper
    public static function isBanned(mixed $db, ?string $email = null, ?string $user = null)
    {
        if ($email === null && $user === null) {
            return false;
        }

        $conditions = [];
        $params = [];
        $paramTypes = "";

        if ($email !== null) {
            $hashedEmail = hash('sha256', strtolower(trim($email)));
            $conditions[] = "(value = ? AND type = 'email')";
            $params[] = $hashedEmail;
            $paramTypes .= "s";
        }

        if ($user !== null) {
            $conditions[] = "(value = ? AND type = 'username')";
            $params[] = $user;
            $paramTypes .= "s";
        }

        $sql = "SELECT reason FROM blacklist WHERE " . implode(" OR ", $conditions) . " LIMIT 1";
        
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param($paramTypes, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            return !empty($row['reason']) ? $row['reason'] : "An unknown error occurred. Please try again later.";
        }

        return false;
    }

    public function login_user($user, $pwd, $remember)
    {
        $conn = mysqli_connect(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        if (mysqli_connect_errno()) {
            http_response_code(500);
            return ['error' => "Database connection failed"];
        }

        $remember = ($remember === 'true' || $remember === 1) ? 1 : 0;

        if($remember === 1 && !in_array('site-prefs', Cookie::controls())) {
            http_response_code(400);
            return ['error' => "Remember me requires site prefs to be enabled. Sorry!"];
        }

        if (empty($user)) {
            http_response_code(400);
            return ['error' => "Email or username cannot be blank"];
        }

        if (loggedin()) {
            http_response_code(400);
            return ['error' => "Already logged in"];
        }

        //$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1");
        $stmt = $conn->prepare("SELECT * FROM users WHERE (email = ? OR username = ?) AND (deactive IS NULL OR deactive < NOW() + INTERVAL 14 DAY) LIMIT 1");
        $stmt->bind_param("ss", $user, $user);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (!$row) {
            http_response_code(400);
            return ['error' => "Invalid combination of email or username and password."];
        }

        $db_hashed_pwd = null;
        if (!empty($row['salt'])) {
            if (md5($pwd . $row['salt']) === $row['password']) {
                $db_hashed_pwd = md5($pwd . $row['salt']);
            }
        } elseif (md5($pwd) === $row['password']) {
            $db_hashed_pwd = md5($pwd);
        }

        $correct_pwd = password_verify($pwd, $row['password']) || ($db_hashed_pwd !== null);

        if (!$correct_pwd) {
            http_response_code(400);
            return ['error' => "Invalid combination of email or username and password."];
        }

        $userid = $row['id'];
        $login_from = $_SERVER['REMOTE_ADDR'];
        $user_agent = htmlspecialchars($_SERVER['HTTP_USER_AGENT']);
        $user_agent = get_browser_name($user_agent) . ", " . get_system_name($user_agent);
        $time = time();
        $token_raw = bin2hex(random_bytes(32));
        $token_hashed = hash('sha256', $token_raw);

        if ($db_hashed_pwd !== null) {
            $new_hash = password_hash($pwd, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE users SET password = ?, salt = NULL WHERE id = ?");
            $updateStmt->bind_param("si", $new_hash, $userid);
            if (!$updateStmt->execute()) {
                return ['error' => "Error rehashing password. Contact " . DB_MAIL];
            }
        }

        if (!empty($row['deactive'])) {
            $sql = "INSERT INTO sessions (id, user, timestamp) VALUES ('$token_raw', '$userid', '$time') LIMIT 1";
            if (mysqli_query($conn, $sql)) {
                http_response_code(500);
                return [
                    'popup' => "Do you want to reactivate your account?",
                    'error' => "Do you want to reactivate your account?",
                    'goto' => "/acc/index?reactive=1&token=" . $token_raw,
                    'btn' => "Yes"
                ];
            }
        }

        $isBanned = self::isBanned($conn, $row['email'], $row['username']);
        if ($isBanned !== false) {
            http_response_code(400);
            return ['error' => $isBanned];
        }

        $banStmt = $conn->prepare("SELECT * FROM bans WHERE user = ? AND end_date >= UNIX_TIMESTAMP() ORDER BY end_date DESC LIMIT 1");
        $banStmt->bind_param("i", $userid);
        $banStmt->execute();
        $banResult = $banStmt->get_result();

        if ($banRow = $banResult->fetch_assoc()) {
            http_response_code(500);
            return [
                'popup' => "This account has been banned until " . date("M d, Y H:i", $banRow['end_date']) . ". " . htmlspecialchars($banRow['reason']),
                'error' => null,
                'goto' => null,
                'btn' => "Okay"
            ];
        }

        $sessionSql = "INSERT INTO sessions (id, login_from, user_agent, user, timestamp, remember) VALUES (?, ?, ?, ?, ?, ?)";
        $sessionStmt = $conn->prepare($sessionSql);
        $sessionStmt->bind_param("sssiii", $token_hashed, $login_from, $user_agent, $userid, $time, $remember);

        if ($sessionStmt->execute()) {
            session_regenerate_id(true);
            $_SESSION['userid'] = $userid;
            $_SESSION['tokenid'] = $token_raw;

            if ($remember === 1) {
                setcookie('token', $token_raw, [
                    'expires' => $time + (60 * 60 * 24 * 30), //30 days
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            } else {
                setcookie('token', '', [
                    'expires' => time() - 3600,
                    'path' => '/'
                ]);
            }
            return ['success' => true];
        }

        http_response_code(500);
        return ['error' => 'An unknown error occurred. Please try again later.'];
    }

    function register_user($username, $password, $email) 
    {
        $conn = mysqli_connect(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        if (mysqli_connect_errno()) {
            http_response_code(500);
            return ['error' => "Database connection failed"];
        }

        $username = mysqli_real_escape_string($conn, htmlspecialchars((urldecode($username))));
        $email = mysqli_real_escape_string($conn, trim($email ?? ''));
        $created = date("Y-m-d H:i:s");
        $bloguser = md5(uniqid());
        $tokenid = bin2hex(random_bytes(32));
        $login_from = $_SERVER['REMOTE_ADDR'];

        if (empty($password) || empty($email) || empty($username)) {
            http_response_code(400);
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

        $usernameutils = new ScreenNameUtils();
        $username_available = $usernameutils->check_username_available($username);
        if ($username_available['available'] === '0') {
            http_response_code(400);
            return ['error' => "Username is not avaliable"];
        }

        $picture = "/img/no_image.png";

        if (strlen($password) < 8) {
            http_response_code(400);
            return ['error' => "Password cannot be less than 8 characters"];
        }

        if (strlen($password) > 250) {
            http_response_code(400);
            return ['error' => "Password cannot be more than 250 characters"];
        }

        $password = password_hash($password, PASSWORD_DEFAULT);

        //Prevents re-registration for banned or deactivated accounts
        $current_domain = strtolower(explode('@', $email)[1]);
        $current_local = strtolower(explode('@', $email)[0]);

        if (str_contains($current_local, '+')) {
            http_response_code(400);
            return ['error' => "Invalid email address format"];
        }   

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            return ['error' => "Invalid email address format"];
        }

        if (!checkdnsrr($current_domain, 'MX')) {
            http_response_code(400);
            return ['error' => "Email address is not avaliable"];
        }

        //Account email blacklist system
        $isBanned = AccountManager::isBanned($conn, $email, null);
        if ($isBanned !== false) {
            http_response_code(400);
            return ['error' => "Email address is not avaliable"];
        }

        $sql_check = "SELECT id FROM users WHERE email = '$email'";
        $stmt = mysqli_query($conn, $sql_check);

        if (mysqli_num_rows($stmt) > 0) {
            http_response_code(400);
            return ['error' => "Email address is not avaliable"];
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
                $_SESSION['userid'] = $userid;
                $_SESSION['tokenid'] = $tokenid;
                return ['success' => true];
            }
        } else {
            http_response_code(500);
            return ['error' => "Failed to register account."];
        }

        http_response_code(500);
        return ['error' => 'An unknown error occured. Please try again later.'];
    }
}

if (isset($_GET['verify_account'])) {
    $accManager = new AccountManager();
    echo $accManager->verifyAccount();
    exit;
}

if (isset($_POST['login'])) {
    $accManager = new AccountManager();
    echo json_encode($accManager->login_user($_POST['mail'], $_POST['pwd'], $_POST['remember']));
    exit;
}

function register_user($username, $password, $email)
{
    $conn = mysqli_connect(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if (mysqli_connect_errno()) {
        header('HTTP/1.0 500 Internal Server Error');
        return ['error' => "Database connection failed"];
    }

    $username = mysqli_real_escape_string($conn, htmlspecialchars((urldecode($username))));
    $email = mysqli_real_escape_string($conn, trim($email ?? ''));
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

    $usernameutils = new ScreenNameUtils();
    $username_available = $usernameutils->check_username_available($username);
    if ($username_available['available'] === '0') {
        header("HTTP/1.1 400 Bad Request");
        return ['error' => "Username is not avaliable"];
    }

    $picture = "/img/no_image.png";

    if (strlen($password) < 8) {
        header("HTTP/1.1 400 Bad Request");
        return ['error' => "Password cannot be less than 8 characters"];
    }

    if (strlen($password) > 250) {
        header("HTTP/1.1 400 Bad Request");
        return ['error' => "Password cannot be more than 250 characters"];
    }

    $password = password_hash($password, PASSWORD_DEFAULT);

    // Prevents re-registration for banned or deactivated accounts
    $current_domain = strtolower(explode('@', $email)[1]);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('HTTP/1.1 400 Bad Request');
        return ['error' => "Email address is not avaliable"];
    }

    if (!checkdnsrr($current_domain, 'MX')) {
        header('HTTP/1.1 400 Bad Request');
        return ['error' => "Email address is not avaliable"];
    }

    // Account email blacklist system
    $isBanned = AccountManager::isBanned($conn, $email, null);
    if ($isBanned !== false) {
        header('HTTP/1.0 500 Internal Server Error');
        return ['error' => "Email address is not avaliable"];
    }

    $sql_check = "SELECT id FROM users WHERE email = '$email'";
    $stmt = mysqli_query($conn, $sql_check);

    if (mysqli_num_rows($stmt) > 0) {
        header("HTTP/1.1 400 Bad Request");
        return ['error' => "Email address is not avaliable"];
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
                //'domain' => '.gr8brik.rf.gd',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            $_SESSION['userid'] = $userid;
            $_SESSION['tokenid'] = $tokenid;
            return ['success' => true];
        }
    } else {
        header("HTTP/1.1 500 Internal Server Error");
        return ['error' => "Failed to register account."];
    }

    header("HTTP/1.1 503 Service Unavailable");
    return ['error' => 'An unknown error occured. Please try again later.'];
}

if (isset($_POST['register'])) {
    $accManager = new AccountManager();
    echo json_encode($accManager->register_user(htmlspecialchars($_POST['name']), htmlspecialchars($_POST['pwd']), htmlspecialchars($_POST['mail'])));
    exit;
}
