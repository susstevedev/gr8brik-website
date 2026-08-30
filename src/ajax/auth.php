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
	public static function verifyUser(mixed $db, string $token)
    {
        global $current_user;
        if (!loggedin() || !isset($current_user)) {
            return ['error' => "Please login to verify an account"];
        }

		$check = $db->prepare("SELECT id, verify_token FROM users WHERE verify_token = ? AND verify_token IS NOT NULL");
        $check->bind_param("s", $token);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows === 0) {
            http_response_code(404);
            return ['error' => "Invalid verification token. It's possible that the verification code sent via email was invalid. It's also possible that this verification token is being used by another user."];
        }

		$row = $result->fetch_assoc();
        $userid = $row['id'];
        $stmt = $db->prepare("UPDATE users SET verify_token = NULL WHERE id = ?");
        $stmt->bind_param("i", $userid);

        if ($stmt->execute()) {
			return ['success' => "Your account has been verified. You may now continue to use the platform."];
        } else {
            return ['error' => "An unknown error occured. Please try again later."];
        }
    }

	public static function send_verify_email(string $token, string $email, ?string $username = null) {
		require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/phpmailer/Exception.php';
		require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/phpmailer/PHPMailer.php';
		require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/phpmailer/SMTP.php';

		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";

		$message = "Hi! Thank you for creating an account.\n\nPlease click the link below to verify your email address:\n\n";
		$message .= $protocol . $_SERVER['HTTP_HOST'] . "/acc/verify.php?code=" . $token . "\n\n";
		$message .= 'Note: if you didn\'t request this email, ignore this email. If you didn\'t create this account, email us back and we\'ll delete it.';

		$mail = new \PHPMailer\PHPMailer\PHPMailer(true);

		try {
			$mail->isSMTP();

			$mail->Host = 'smtp.gmail.com';
			$mail->SMTPAuth = true;
			$mail->Username = GMAIL_USER;
			$mail->Password = GMAIL_APP_PWD;
			$mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
			$mail->Port = 587;

			$mail->setFrom(DB_MAIL);
			$mail->addAddress($email);

			$mail->Subject = "Email verification";
			$mail->Body = $message;

			$mail->send();
			return true;
		} catch (\PHPMailer\PHPMailer\Exception $e) {
			error_log($mail->ErrorInfo);
			return false;
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
            $conditions[] = "(value = ? AND type = 'email')";
            $params[] = strtolower(trim($email));
            $paramTypes .= "s";
        }

		if ($email !== null) {
            $conditions[] = "(value = ? AND type = 'email')";
            $params[] = hash('sha256', strtolower(trim($email)));
            $paramTypes .= "s";
        }

        if ($user !== null) {
            $conditions[] = "(value = ? AND type = 'username')";
            $params[] = $user;
            $paramTypes .= "s";
        }

        $sql = "SELECT reason FROM blacklist WHERE (" . implode(" OR ", $conditions) . ") AND (created_at IS NULL OR created_at <= CURRENT_TIMESTAMP()) AND (ignore_at IS NULL OR ignore_at >= CURRENT_TIMESTAMP()) LIMIT 1";

        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param($paramTypes, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            return !empty($row['reason']) ? $row['reason'] : "Email or username is not valid.";
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

        $stmt = $conn->prepare("SELECT id, username, email, password, deactive FROM users WHERE (email = ? OR username = ?) AND (deactive IS NULL OR deactive < NOW() + INTERVAL 14 DAY) LIMIT 1");
        $stmt->bind_param("ss", $user, $user);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (!$row) {
            http_response_code(400);
            return ['error' => "Invalid combination of email or username and password. (no account)"];
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
            $updateStmt->bind_param("si", $new_hash, $row['id']);
            if (!$updateStmt->execute()) {
                return ['error' => "Error rehashing password. Contact " . DB_MAIL];
            }
        }

        return $this->login_final($conn, $row, $remember);
    }

    public function register_user($username, $password, $email) 
    {
        $conn = mysqli_connect(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        if (mysqli_connect_errno()) {
            http_response_code(500);
            return ['error' => "Database connection failed"];
        }

        $username = mysqli_real_escape_string($conn, htmlspecialchars((urldecode($username))));
        $email = mysqli_real_escape_string($conn, trim($email ?? ''));
        $token_raw = bin2hex(random_bytes(32));
		$token_hashed = hash('sha256', $token_raw);
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

		if (loggedin()) {
            http_response_code(400);
            return ['error' => "Already logged in"];
        }

        $usernameutils = new ScreenNameUtils();
        $username_available = $usernameutils->check_username_available($username);
        if (!$username_available['available']) {
            http_response_code(400);
            return ['error' => $username_available['reason']];
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
        $current_local = strtolower(explode('@', $email)[0]);

        if (str_contains($current_local, '+') || str_contains($current_local, '.')) {
            http_response_code(400);
            return ['error' => "Invalid email address format"];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            return ['error' => "Invalid email address format"];
        }

        //Account email blacklist system
        $isBanned = AccountManager::isBanned($conn, $email);
        if ($isBanned !== false) {
            http_response_code(400);
            return ['error' => "Email address is not valid"];
        }

        $sql_check = "SELECT 1 FROM users WHERE email = '$email'";
        $stmt = mysqli_query($conn, $sql_check);

        if (mysqli_num_rows($stmt) > 0) {
            http_response_code(400);
            return ['error' => "Email address is not avaliable"];
        }

        $sql = "INSERT INTO users (blog_user_id, username, password, email, age, picture, verify_token) VALUES ('$token_raw', '$username', '$password', '$email', CURRENT_TIMESTAMP(), '$picture', '$token_raw') LIMIT 1";
        if (mysqli_query($conn, $sql)) {
            $userid = mysqli_insert_id($conn);
            $time = time();

            // Create session token
            $sql = "INSERT INTO sessions (id, login_from, user, timestamp, remember) VALUES ('$token_hashed', '$login_from', '$userid', '$time', '1') LIMIT 1";
            if (mysqli_query($conn, $sql)) {
                $_SESSION['userid'] = $userid;
                $_SESSION['tokenid'] = $token_hashed;

				self::send_verify_email($token_raw, $email, $username);
                return ['success' => true];
            }
        } else {
            http_response_code(500);
            return ['error' => "Failed to register account."];
        }

        http_response_code(500);
        return ['error' => 'An unknown error occured. Please try again later.'];
    }

    public function github_auth($data)
    {
        $conn = mysqli_connect(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        if (mysqli_connect_errno()) {
            http_response_code(500);
            return ['error' => "Database connection failed"];
        }

        if (loggedin()) {
            http_response_code(400);
            return ['error' => "Already logged in"];
        }

        $github_id = trim($data['id'] ?? '');
        $email = trim($data['email'] ?? '');
        $avatar = trim($data['avatar_url'] ?? null);
        $twitter = trim($data['twitter_username'] ?? '');
        $bio = trim($data['bio'] ?? '');

        if (empty($github_id)) {
            http_response_code(400);
            return ['error' => "Invalid github id"];
        }

        $usernameutils = new ScreenNameUtils();
        $username_rand = $usernameutils->generateRandomScreenName();
        $username = trim($data['login'] ?? $username_rand);

        //SCENARIO 1 - account is linked
        $stmt = $conn->prepare("SELECT id, username, email, deactive FROM users WHERE github_id = ? AND deactive IS NULL LIMIT 1");
        $stmt->bind_param("s", $github_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            return $this->login_final($conn, $row, 0); 
        }

        if (empty($email)) {
            http_response_code(400);
            return ['error' => "Your github profile must have a verified email."];
        }

        //SCENARIO 2 - account exists but not linked
        $stmt = $conn->prepare("SELECT id, username, email, deactive FROM users WHERE email = ? AND deactive IS NULL LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            $stmt_link = $conn->prepare("UPDATE users SET github_id = ? WHERE id = ?");
            $stmt_link->bind_param("si", $github_id, $row['id']);
            if ($stmt_link->execute()) {
                return $this->login_final($conn, $row, 0);
            }
            http_response_code(500);
            return ['error' => 'Failed to link github auth to your account.'];
        }

        //SCENARIO 3 - account creation
        $username_available = $usernameutils->check_username_available($username);
        if (!$username_available['available']) {
            $username_available = $usernameutils->check_username_available($username_rand);
            if (!$username_available['available']) {
                http_response_code(400);
                return ['error' => "Github username was unavaliable and random username was also unavaliable."];
            }
            $username = $username_rand;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            return ['error' => "Invalid email address format"];
        }

        $isBanned = AccountManager::isBanned($conn, $email);
        if ($isBanned !== false) {
            http_response_code(400);
            return ['error' => "Email address is not avaliable"];
        }

        $token_raw = bin2hex(random_bytes(32));
        $sql = "INSERT INTO users (blog_user_id, username, password, email, age, verify_token, picture, description, twitter, github_id) VALUES (?, ?, NULL, ?, CURRENT_TIMESTAMP(), ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss", $token_raw, $username, $email, $token_raw, $avatar, $bio, $twitter, $github_id);

        if ($stmt->execute()) {
            $new_userid = $conn->insert_id;

            $row = [
                'id' => $new_userid,
                'username' => $username,
                'email' => $email,
                'deactive' => null
            ];

            //self::send_verify_email($token_raw, $email, $username);
            return $this->login_final($conn, $row, 1);
        }
    }

    public function google_auth($data)
    {
        $conn = mysqli_connect(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        if (mysqli_connect_errno()) {
            http_response_code(500);
            return ['error' => "Database connection failed"];
        }

        if (loggedin()) {
            http_response_code(400);
            return ['error' => "Already logged in"];
        }

        $google_id = trim($data['id'] ?? '');
        $email = trim($data['email'] ?? '');
        $avatar = trim($data['picture'] ?? null);

        if (empty($google_id)) {
            http_response_code(400);
            return ['error' => "Invalid google id"];
        }

        $usernameutils = new ScreenNameUtils();
        $username_rand = $usernameutils->generateRandomScreenName();
        $username = preg_replace('/[^A-Za-z0-9._-]/', '', $data['name'] ?? $username_rand);

        //SCENARIO 1 - account is linked
        $stmt = $conn->prepare("SELECT id, username, email, deactive FROM users WHERE google_id = ? AND deactive IS NULL LIMIT 1");
        $stmt->bind_param("s", $google_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            return $this->login_final($conn, $row, 0); 
        }

        if (empty($email) || !$data['verified_email']) {
            http_response_code(400);
            return ['error' => "Your google profile must have a verified email."];
        }

        //SCENARIO 2 - account exists but not linked
        $stmt = $conn->prepare("SELECT id, username, email, deactive FROM users WHERE email = ? AND deactive IS NULL LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            $stmt_link = $conn->prepare("UPDATE users SET google_id = ? WHERE id = ?");
            $stmt_link->bind_param("si", $google_id, $row['id']);
            if ($stmt_link->execute()) {
                return $this->login_final($conn, $row, 0);
            }
            http_response_code(500);
            return ['error' => 'Failed to link google auth to your account.'];
        }

        //SCENARIO 3 - account creation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            return ['error' => "Invalid email address format"];
        }

        $isBanned = AccountManager::isBanned($conn, $email);
        if ($isBanned !== false) {
            http_response_code(400);
            return ['error' => "Email address is not avaliable"];
        }

        $token_raw = bin2hex(random_bytes(32));
        $sql = "INSERT INTO users (blog_user_id, username, password, email, age, verify_token, picture, google_id) VALUES (?, ?, NULL, ?, CURRENT_TIMESTAMP(), ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $token_raw, $username, $email, $token_raw, $avatar, $google_id);

        if ($stmt->execute()) {
            $new_userid = $conn->insert_id;

            $row = [
                'id' => $new_userid,
                'username' => $username,
                'email' => $email,
                'deactive' => null
            ];

            //self::send_verify_email($token_raw, $email, $username);
            return $this->login_final($conn, $row, 1);
        }
    }

    public function login_final(mixed $conn, ?array $row, ?bool $remember) {
        $userid = $row['id'];
        $login_from = $_SERVER['REMOTE_ADDR'];
        $user_agent = htmlspecialchars($_SERVER['HTTP_USER_AGENT']);
        $user_agent = get_browser_name($user_agent) . ", " . get_system_name($user_agent);
        $time = time();
        $token_raw = bin2hex(random_bytes(32));
        $token_hashed = hash('sha256', $token_raw);

        $isBanned = self::isBanned($conn, $row['email'], $row['username']);
        if ($isBanned !== false) {
            http_response_code(400);
            return ['error' => $isBanned];
        }

        if (!empty($row['deactive'])) {
            $sql = "INSERT INTO sessions (id, user, timestamp) VALUES ('$token_hashed', '$userid', '$time') LIMIT 1";
            if (mysqli_query($conn, $sql)) {
                http_response_code(500);
                return [
                    'popup' => "Do you want to reactivate your account?",
                    'error' => "Do you want to reactivate your account?",
                    'goto' => "/acc/index?reactive=1&token=" . $token_hashed,
                    'btn' => "Yes"
                ];
            }
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
    }
}

if (isset($_POST['login'])) {
    $accManager = new AccountManager();
    echo json_encode($accManager->login_user($_POST['mail'], $_POST['pwd'], $_POST['remember']));
    exit;
}

if (isset($_POST['register'])) {
    $accManager = new AccountManager();
    echo json_encode($accManager->register_user(htmlspecialchars($_POST['name']), htmlspecialchars($_POST['pwd']), htmlspecialchars($_POST['mail'])));
    exit;
}

if (isset($_GET['authtype'])) {
    if (empty($_SESSION['oauth_state'])) {
        $_SESSION['oauth_state'] = bin2hex(random_bytes(16));
    }

    if ($_GET['authtype'] === 'github') {
        $client_id = GITHUB_CLIENT_ID;
        $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/ajax/auth.php?github_callback=true';
        $scope = 'user:email';
        $state = $_SESSION['oauth_state'];

        $auth_url = 'https://github.com/login/oauth/authorize?' . http_build_query([
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'scope' => $scope,
            'state' => $state
        ]);

        header('Location: ' . $auth_url);
        exit;
    } else if($_GET['authtype'] === 'google') { 
        $client_id = GOOGLE_CLIENT_ID;
        $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/ajax/auth.php?google_callback=true';
        $state = $_SESSION['oauth_state'];

        $auth_url = "https://accounts.google.com/o/oauth2/auth?" . http_build_query([
            'client_id'     => $client_id,
            'redirect_uri'  => $redirect_uri,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'access_type'   => 'online',
            'state' => $state
        ]);

        header('Location: ' . $auth_url);
        exit;
    } else {
        echo "Please provide an auth type";
        exit;
    }
}

if (isset($_GET['github_callback']) && isset($_GET['code'])) {
    if (empty($_GET['state']) || empty($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
        echo "Invalid state token";
        exit;
    }
    unset($_SESSION['oauth_state']);

    $client_id = GITHUB_CLIENT_ID;
    $client_secret = GITHUB_CLIENT_PWD;
    $code = $_GET['code'];

    $token_url = 'https://github.com/login/oauth/access_token';
    $post_data = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'code' => $code
    ];

    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $response = curl_exec($ch);

    $result = json_decode($response, true);
    $access_token = $result['access_token'] ?? null;
    
    if ($access_token) {
        $ch = curl_init('https://api.github.com/user');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
            'User-Agent: PHP'
        ]);

        $user_response = curl_exec($ch);
        $user_data = json_decode($user_response, true);

        if (empty($user_data['email'])) {
            $ch = curl_init('https://api.github.com/user/emails');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $access_token,
                'User-Agent: PHP'
            ]);
            $emails_response = curl_exec($ch);

            $emails = json_decode($emails_response, true);
            if (is_array($emails)) {
                foreach ($emails as $email_record) {
                    if ($email_record['primary'] && $email_record['verified']) {
                        $user_data['email'] = $email_record['email'];
                        break;
                    }
                }
            } else {
                echo "Github account does not have an email address";
                exit;
            }
        }

        $auth = new AccountManager();
        if(loggedin()) {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/account_settings.php';

            $account_settings = new AccountSettings();
            $userid = $current_user->id;
            $github_id = $user_data['id'];

            if(empty($current_user->github_id)) {
                $result = $account_settings->link_github_account($userid, $github_id);
            } else {
                $result = $account_settings->unlink_github_account($userid);
            }
        } else {
            $result = $auth->github_auth($user_data);
        }

        if(isset($result['success'])) {
            header('Location: /acc/index.php');
        } else if(isset($result['error'])) {
            echo $result['error'];
        }
        exit;
    }
}

if (isset($_GET['google_callback']) && isset($_GET['code'])) {
    if (empty($_GET['state']) || empty($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
        echo "Invalid state token";
        exit;
    }
    unset($_SESSION['oauth_state']);

    $client_id = GOOGLE_CLIENT_ID;
    $client_secret = GOOGLE_CLIENT_SECRET;
    $code = $_GET['code'];
    $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/ajax/auth.php?google_callback=true';
    $token_uri = 'https://oauth2.googleapis.com/token';

    $token_data = [
        'code'          => $code,
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri'  => $redirect_uri,
        'grant_type'    => 'authorization_code'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_uri);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $response = curl_exec($ch);

    $token_response = json_decode($response, true);

    if (!isset($token_response['access_token'])) {
        exit('Failed to obtain access token');
    }

    $access_token = $token_response['access_token'];
    $userinfo_url = 'https://www.googleapis.com/oauth2/v2/userinfo';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $userinfo_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token
    ]);
    $user_response = curl_exec($ch);
    $user_data = json_decode($user_response, true);

    $auth = new AccountManager();
    if(loggedin()) {
        exit; //todo add linking of existing like github
    } else {
        $result = $auth->google_auth($user_data);
    }

    if(isset($result['success'])) {
        header('Location: /acc/index.php');
    } else if(isset($result['error'])) {
        echo $result['error'];
    }
    exit;
}