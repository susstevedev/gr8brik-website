<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/what_browser.php';
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
$loggedin = loggedin();

if(isset($_GET['ajax'])) {
    error_reporting(0);
}

function login() {
    global $id, $token, $users_row;
    if (!loggedin()) {
        return false;
    }

    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        return false;
    }
    
    $token_raw = $_SESSION['tokenid'] ?? $_COOKIE['token'];
    $token_hashed = hash('sha256', $token_raw);

    $session_stmt = $conn->prepare("SELECT user, timestamp FROM sessions WHERE id = ? LIMIT 1");
    $session_stmt->bind_param("s", $token_hashed);
    $session_stmt->execute();
    $session_res = $session_stmt->get_result();

    if ($session_res->num_rows <= 0) {
        $session_stmt->close();
        $conn->close();
        logout();
        return false;
    }

    $token = $session_res->fetch_assoc();
    $session_stmt->close();

    $_SESSION['tokenid'] = $token_raw;
    $id = $token['user'];
    $_SESSION['userid'] = $token['user'];

    $user = User::getUser($id);
    $user->id = $id;
    return $user;
}

//doing $current_user->username
//is faster than
//doing login()->username
//i think
$current_user = login();

if (loggedin()) {
    if (rand(1, 20) <= 1) {
        regenerate_session();
        delete_old_sessions();
        Cookie::del_old_analytics($conn, 15);

        $lock_file = __DIR__ . '/.cleanup_lock';
        $currentTime = time();
        clearstatcache(true, $lock_file);
        
        if (!file_exists($lock_file) || ($currentTime - filemtime($lock_file) > 3600)) {
            delete_inactive_users();
            @touch($lock_file);
        }
    }
}

class User {
    public ?int $id;
    public ?string $email;
    public ?string $github_id;
    public ?string $username;
    public ?string $picture;
    public ?string $banner;
    public ?string $description;
    public ?string $twitter;
    public ?string $bsky;
    public ?bool $admin;
    public ?int $alert;
    public ?string $age;
    public ?string $verify_token;
    public ?string $deactive;

    public function __construct(?array $data = []) {
        $this->id = $data['id'] ?? 0;
        $this->github_id = $data['github_id'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->username = $data['username'] ?? '[deleted]';
        $this->picture = $data['picture'] ?? ($this->email ? $this->userGravatar($this->email, 256) : '/img/no_image.png');
        $this->banner = $data['banner'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->twitter = $data['twitter'] ?? null;
        $this->bsky = $data['bsky'] ?? null;
        $this->admin = $data['admin'] ?? false;
        $this->alert = $data['alert'] ?? 0;
        $this->age = $data['age'] ?? null;
        $this->verify_token = $data['verify_token'] ?? null;
        $this->deactive = $data['deactive'] ?? null;
    }

    public static function isDeleted(?int $id): bool {
        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        if ($conn->connect_error) {
            return true;
        }

        if (empty($id)) {
            return true;
        }

        $stmt = $conn->prepare("
            SELECT username, deactive
            FROM users
            WHERE id = ?
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            $stmt->close();
            $conn->close();
            return true; 
        }

        $row = $res->fetch_assoc();
        $stmt->close();
        $conn->close();

        if (empty($row)) {
            return true;
        }

        if ($row['deactive'] !== null) {
            return true;
        }

        return false;
    }

    public static function getUser(?int $id = 0) {
        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

        $user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND deactive IS NULL");
        $user_stmt->bind_param("i", $id);
        $user_stmt->execute();
        $user_res = $user_stmt->get_result();
        $user_row = $user_res->fetch_assoc();

        return new User($user_row);
    }

    public static function getUsers(?array $ids): array {
        if (empty($ids)) {
            return [];
        }

        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        $ids = array_unique(array_map('intval', $ids));
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));

        $stmt = $conn->prepare("
            SELECT *
            FROM users
            WHERE id IN ($placeholders)
            AND deactive IS NULL
        ");

        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while($row = $result->fetch_assoc()) {
            $user = new User($row);
            $users[$user->id] = $user;
        }

        $stmt->close();
        $conn->close();
        return $users;
    }

    public static function getUserByName(?string $username) {
        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

        $user_stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND deactive IS NULL");
        $user_stmt->bind_param("s", $username);
        $user_stmt->execute();
        $user_res = $user_stmt->get_result();
        $user_row = $user_res->fetch_assoc();

        return new User($user_row);
    }

    public static function getUserByEmail(?string $email) {
        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

        $user_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND deactive IS NULL");
        $user_stmt->bind_param("s", $email);
        $user_stmt->execute();
        $user_res = $user_stmt->get_result();
        $user_row = $user_res->fetch_assoc();

        return new User($user_row);
    }

    private function userGravatar(?string $email, ?int $size = 50) {
        if(empty($email)) {
            return;
        }

        $cleaned_email = strtolower(trim($email));
        $hash = hash('sha256', $cleaned_email);

        $params = [
            's' => $size,
            'd' => 'identicon',
            'r' => 'pg'
        ];

        return "https://www.gravatar.com/avatar/" . $hash . "?" . http_build_query($params);
    }

    public static function isVerified() {
        global $current_user;

        if(loggedin()) {
            $id = $current_user->id;

            if($id === null) {
                return false;
            }

            if(self::isDeleted($id)) {
                return false;
            }

            if($current_user->verify_token != null) {
                return false;
            }

            return true;
        } else {
            return false;
        }
    }
}

function get_warn_status() {
    global $current_user;
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

    if(loggedin()) {
        $id = $current_user->id ?? 0;
		$acc_issue = false;
        $text = false;
        $additional = false;
        $button = "Got it";
        
    	$warning_stmt = $conn->prepare("SELECT * FROM warnings WHERE user = ? AND seen = 0 LIMIT 1");
        $warning_stmt->bind_param("i", $id);
        $warning_stmt->execute();
        $warning_stmt = $warning_stmt->get_result();
        
        $ban_stmt = $conn->prepare("SELECT * FROM bans WHERE user = ? LIMIT 1");
        $ban_stmt->bind_param("i", $id);
        $ban_stmt->execute();
        $ban_res = $ban_stmt->get_result();

        if($warning_stmt->num_rows != 0) {
            $warning_row = $warning_stmt->fetch_assoc();
        	
        	$acc_issue = true;
        	$text = "Your account has been warned for the following reason:";
            $reason = $warning_row['reason'];
            $additional = false;
        	$button = "Got it";
        }
        
        if ($ban_res->num_rows !== 0) {
       		$ban_data = $ban_res->fetch_assoc();
            if ($ban_data['end_date'] === null || $ban_data['end_date'] >= time()) {
            	$acc_issue = true;
                $text = "Your account has been banned for the following reason:";
                $reason = $ban_data['reason'];
                $additional = "Banned until " . date("M d, Y H:i", $ban_data['end_date']);
                $button = "Logout";
            }
        }
                
        if($acc_issue == true) {
            $json = array(
                'status' => "yes", 
                'text' => htmlspecialchars($text),
                'reason' => htmlspecialchars($reason),
                'additional' => htmlspecialchars($additional),
                'button' => htmlspecialchars($button),
                'success' => true
            );
        } else {
            $json = array(
                'status' => "no",
                'success' => true
            );
        }
        return $json;
    } else {
        $json = array(
            'error' => "Not authenticated",
        	'success' => false
        );
        return $json;
    }
}

function seen_warn_status() {
    global $current_user;

    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    $id = $current_user->id;

    if(loggedin()) {
        $warning_stmt = $conn->prepare("SELECT * FROM warnings WHERE user = ? AND seen = 1 LIMIT 1");
        $warning_stmt->bind_param("i", $id);
        $warning_stmt->execute();
        $warning_stmt = $warning_stmt->get_result();
        
        if($warning_stmt->num_rows < 0) {
            return false;
        }
        
    	$warning_stmt = $conn->prepare("UPDATE warnings SET seen = 1 WHERE user = ?");
        $warning_stmt->bind_param("i", $id);
                
        if($warning_stmt->execute()) {
            return true;
        }
    }
   	return false;
}

if(isset($_GET['get_warn_status']) && basename($_SERVER['PHP_SELF']) === "user.php") {
    header("Content-type: application/json");
    echo json_encode(get_warn_status());
	exit;
}

if(isset($_GET['seen_warn_status']) && basename($_SERVER['PHP_SELF']) === "user.php") {
    header("Content-type: application/json");
    echo json_encode(seen_warn_status());
	exit;
}

if (isset($_GET['ajax'])) {
    header('Content-type: application/json');

    if (loggedin()) {
        $id = $current_user->id;
    } else {
        echo json_encode(['error' => 'User is not authenticated or no userid was provided.']);
        exit;
    }

    $your_followers_stmt = $conn->prepare("SELECT COUNT(*) as count FROM follow WHERE profileid = ? LIMIT 1");
    $your_followers_stmt->bind_param("i", $id);
    $your_followers_stmt->execute();
    $your_followers_res = $your_followers_stmt->get_result();

    if ($your_followers_res->num_rows != 0) {
        $your_followers_row = $your_followers_res->fetch_assoc()['count'];
    }

    $who_youre_following_stmt = $conn->prepare("SELECT COUNT(*) as count FROM follow WHERE userid = ? LIMIT 1");
    $who_youre_following_stmt->bind_param("i", $id);
    $who_youre_following_stmt->execute();
    $who_youre_following_res = $who_youre_following_stmt->get_result();

    if ($who_youre_following_res->num_rows != 0) {
        $who_youre_following_row = $who_youre_following_res->fetch_assoc()['count'];
    }

    $logindata = json_encode([
        'success' => true,
        'id' => $current_user->id,
        'pfp' => $current_user->picture,
        'user' => $current_user->username,
        'alert' => $current_user->alert,
        'stats' => [
            'followers' => $your_followers_row ?? 0,
            'following' => $who_youre_following_row ?? 0,
        ]
    ]);
    echo $logindata;
    exit;
}

if (isset($_GET['ajaxv2'])) {
    header('Content-type: application/json');

    if (loggedin() && !isset($_GET['userid'])) {
        $id = $current_user->id;
    } else {
        $id = $_GET['userid'] ?? 0;

        if(!isset($_GET['userid'])) {
            echo json_encode(['error' => 'User is not authenticated or no userid was provided.']);
            exit;
        }
    }
    $usero = User::getUser($id);

    $your_followers_stmt = $conn->prepare("SELECT COUNT(*) as count FROM follow WHERE profileid = ? LIMIT 1");
    $your_followers_stmt->bind_param("i", $id);
    $your_followers_stmt->execute();
    $your_followers_res = $your_followers_stmt->get_result();

    if ($your_followers_res->num_rows != 0) {
        $your_followers_row = $your_followers_res->fetch_assoc()['count'];
    }

    $who_youre_following_stmt = $conn->prepare("SELECT COUNT(*) as count FROM follow WHERE userid = ? LIMIT 1");
    $who_youre_following_stmt->bind_param("i", $id);
    $who_youre_following_stmt->execute();
    $who_youre_following_res = $who_youre_following_stmt->get_result();

    if ($who_youre_following_res->num_rows != 0) {
        $who_youre_following_row = $who_youre_following_res->fetch_assoc()['count'];
    }

    if(!isset($_GET['userid'])) {
        $logindata = json_encode([
            'success' => true,
            'id' => $usero->id,
            'pfp' => $usero->picture,
            'user' => $usero->username,
            'alert' => $usero->alert,
            'stats' => [
                'followers' => $your_followers_row ?? 0,
                'following' => $who_youre_following_row ?? 0,
            ]
        ]);
    } else {
        $logindata = json_encode((array)$usero);
    }

    echo $logindata;
    exit;
}

function logout(?bool $redirect = false) {
    $token_raw = $_SESSION['tokenid'] ?? $_COOKIE['token'] ?? null;

    if ($token_raw !== null) {
        $token_hashed = hash('sha256', $token_raw);
        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        
        if (!$conn->connect_error) {
            $stmt = $conn->prepare("DELETE FROM sessions WHERE id = ? LIMIT 1");
            $stmt->bind_param("s", $token_hashed);
            $stmt->execute();
            $stmt->close();
            $conn->close();
        }
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        session_destroy();
    }

    if (isset($_COOKIE['token'])) {
        setcookie('token', '', [ 'expires' => time() - 3600 ]);
        unset($_COOKIE['token']);
    }

    if ($redirect === true) {
        header('Location: /index.php');
        exit;
    }
}

function regenerate_session() {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/what_browser.php';
    global $conn, $current_user;

    if (!loggedin()) {
        return false;
    }

    $old_token = hash('sha256', $_SESSION['tokenid']);
    $user_ip = $_SERVER['REMOTE_ADDR'];
    
    $raw_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $user_agent = get_browser_name($raw_ua) . ", " . get_system_name($raw_ua);

    $stmt_check = $conn->prepare("SELECT remember, login_from, user_agent FROM sessions WHERE id = ?");
    if (!$stmt_check) {
        return false;
    }

    $stmt_check->bind_param("s", $old_token);
    $stmt_check->execute();
    $res = $stmt_check->get_result();

    if ($res && $res->num_rows > 0) {
        $stored = $res->fetch_assoc();
        $stmt_check->close();

        if ($stored['user_agent'] !== $user_agent || !ip_subnet_same($stored['login_from'], $user_ip)) {            
            $stmt_kill = $conn->prepare("DELETE FROM sessions WHERE id = ?");
            $stmt_kill->bind_param("s", $old_token);
            $stmt_kill->execute();
            $stmt_kill->close();
            logout();
            return false;
        }

        if ((int)$stored['remember'] !== 1) {
            return true;
        }
    } else {
        $stmt_check->close();
        return false;
    }

    $new_raw_token = bin2hex(random_bytes(32)); 
    $new_token = hash('sha256', $new_raw_token);
    $active = (string)time();

    $stmt = $conn->prepare("
        UPDATE sessions 
        SET id = ?, timestamp = ?, login_from = ?, user_agent = ? 
        WHERE id = ? AND remember = 1
    ");
    
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("sssss", $new_token, $active, $user_ip, $user_agent, $old_token);
    if ($stmt->execute()) {
        $stmt->close();
        session_regenerate_id(true);
        $_SESSION['tokenid'] = $new_raw_token;
        return true;
    } else {
        error_log("mysql error: " . $stmt->error);
        $stmt->close();
        return false;
    }
}

function delete_old_sessions() {
    global $conn;
    if (loggedin()) {
        $expiry_short = time() - (60 * 60 * 24 * 1);//1 day
		$expiry_long = time() - (60 * 60 * 24 * 15);//15 day
        
        $old_sessions_stmt = $conn->prepare("DELETE FROM sessions WHERE timestamp < ? AND remember = 0");
        $old_sessions_stmt->bind_param("i", $expiry_short);
        $old_sessions_stmt->execute();
        $old_sessions_stmt->close();
        
        $old_sessions_stmt = $conn->prepare("DELETE FROM sessions WHERE timestamp < ? AND remember = 1");
        $old_sessions_stmt->bind_param("i", $expiry_long);
        $old_sessions_stmt->execute();
        $old_sessions_stmt->close();
        return true;
    }
    return false;
}

function delete_inactive_users($single_user_id = null, $blacklist_email = false) {
    global $conn;

    $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
    $conn3 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);

    if ($conn->connect_error || $conn2->connect_error || $conn3->connect_error) {
        error_log("db connection failed " . ($conn->connect_error ?: $conn2->connect_error ?: $conn3->connect_error));
        if ($conn2 && !$conn2->connect_error) {
            $conn2->close();
        }

        if ($conn3 && !$conn3->connect_error) {
            $conn3->close();
        }

        return false;
    }

    $inactive_user_ids = [];

    if ($single_user_id !== null) {
        $inactive_user_ids[] = (int)$single_user_id;
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE deactive IS NOT NULL AND STR_TO_DATE(deactive, '%Y-%m-%d %H:%i:%s') < NOW() - INTERVAL 14 DAY LIMIT 20");
        if (!$stmt || !$stmt->execute()) {
            error_log("failed query for users " . ($conn->error ?: $stmt->error));
            $conn2->close(); $conn3->close();
            return false;
        }

        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $inactive_user_ids[] = (int)$row['id'];
        }
        $stmt->close();
    }

    if (empty($inactive_user_ids)) {
        $conn2->close(); $conn3->close();
        return true;
    }

    $placeholders = implode(',', array_fill(0, count($inactive_user_ids), '?'));
    $types = str_repeat('i', count($inactive_user_ids));
    $usernames_blacklist = [];
    $emails_blacklist = [];
    
    $stmt_names = $conn->prepare("SELECT username, email FROM users WHERE id IN ($placeholders)");
    if ($stmt_names) {
        $stmt_names->bind_param($types, ...$inactive_user_ids);
        $stmt_names->execute();
        $res_names = $stmt_names->get_result();
        while ($row = $res_names->fetch_assoc()) {
            if (!empty($row['username'])) {
                $usernames_blacklist[] = $row['username'];
            }
            if ($blacklist_email && $single_user_id !== null && !empty($row['email'])) {
                $emails_blacklist[] = hash('sha256', strtolower(trim($row['email'])));
            }
        }
        $stmt_names->close();
    }

    if (!empty($usernames_blacklist)) {
        $row_placeholders = implode(',', array_fill(0, count($usernames_blacklist), "(?, 'username', 'auto deleted user account')"));
        $name_types = str_repeat('s', count($usernames_blacklist));
        $sql_bl = "INSERT IGNORE INTO blacklist (value, type, reason) VALUES $row_placeholders";
        
        if ($stmt_bl = $conn->prepare($sql_bl)) {
            $stmt_bl->bind_param($name_types, ...$usernames_blacklist);
            $stmt_bl->execute();
            $stmt_bl->close();
        }
    }

    if (!empty($emails_blacklist)) {
        $email_placeholders = implode(',', array_fill(0, count($emails_blacklist), "(?, 'email', 'banned by admin request')"));
        $email_types = str_repeat('s', count($emails_blacklist));
        $sql_el = "INSERT IGNORE INTO blacklist (value, type, reason) VALUES $email_placeholders";
        
        if ($stmt_el = $conn->prepare($sql_el)) {
            $stmt_el->bind_param($email_types, ...$emails_blacklist);
            $stmt_el->execute();
            $stmt_el->close();
        }
    }

    $execute_bulk = function($db, $sql, $types, $ids) {
        if ($stmt = $db->prepare($sql)) {
            $stmt->bind_param($types, ...$ids);
            if (!$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                throw new Exception('issue with sql query' . $err);
            }
            $stmt->close();
        } else {
            throw new Exception('issue with sql prep' . $db->error);
        }
    };

    $conn->begin_transaction();
    $conn2->begin_transaction();
    $conn3->begin_transaction();

    try {
        //forum
        $execute_bulk($conn3, "UPDATE messages SET userid = 0 WHERE userid IN ($placeholders)", $types, $inactive_user_ids);

        //models
        $execute_bulk($conn2, "UPDATE model SET user = 0 WHERE user IN ($placeholders)", $types, $inactive_user_ids);
        $execute_bulk($conn2, "UPDATE parts SET userid = 0 WHERE userid IN ($placeholders)", $types, $inactive_user_ids);

        //comments
        $execute_bulk($conn2, "DELETE FROM comment_votes WHERE user_id IN ($placeholders)", $types, $inactive_user_ids);
        $execute_bulk($conn2, "DELETE FROM comment_votes WHERE comment_id IN (SELECT id FROM comments WHERE hidden = 1 AND user IN ($placeholders))", $types, $inactive_user_ids);
        $execute_bulk($conn2, "UPDATE comments SET user = 0 WHERE hidden = 0 AND user IN ($placeholders)", $types, $inactive_user_ids);
        $execute_bulk($conn2, "DELETE FROM comments WHERE hidden = 1 AND user IN ($placeholders)", $types, $inactive_user_ids);

        //user interactions
        $execute_bulk($conn2, "DELETE FROM votes WHERE user IN ($placeholders)", $types, $inactive_user_ids);
        $execute_bulk($conn, "DELETE FROM follow WHERE userid IN ($placeholders) OR profileid IN ($placeholders)", $types . $types, array_merge($inactive_user_ids, $inactive_user_ids));
        $execute_bulk($conn, "DELETE FROM user_blocks WHERE userid IN ($placeholders) OR profileid IN ($placeholders)", $types . $types, array_merge($inactive_user_ids, $inactive_user_ids));
        $execute_bulk($conn, "DELETE FROM notifications WHERE user IN ($placeholders) OR profile IN ($placeholders)", $types . $types, array_merge($inactive_user_ids, $inactive_user_ids));
        $execute_bulk($conn, "DELETE FROM subscriptions WHERE userid IN ($placeholders)", $types, $inactive_user_ids);

        //mod records
        $execute_bulk($conn, "DELETE FROM bans WHERE user IN ($placeholders)", $types, $inactive_user_ids);
        $execute_bulk($conn, "DELETE FROM appeals WHERE user IN ($placeholders)", $types, $inactive_user_ids);
        $execute_bulk($conn2, "DELETE FROM reported WHERE user IN ($placeholders)", $types, $inactive_user_ids);
        $execute_bulk($conn2, "DELETE FROM reports WHERE reporter_user_id IN ($placeholders)", $types, $inactive_user_ids);
        $execute_bulk($conn2, "DELETE FROM reports WHERE reportable_type = 'profile' AND reportable_id IN ($placeholders)", $types, $inactive_user_ids);

        //dms
        $execute_bulk($conn, "DELETE FROM direct_message WHERE userid IN ($placeholders)", $types, $inactive_user_ids);
        $execute_bulk($conn, "DELETE FROM message_group WHERE userid IN ($placeholders) OR profileid IN ($placeholders)", $types . $types, array_merge($inactive_user_ids, $inactive_user_ids));

        //the actual user
        $execute_bulk($conn, "DELETE FROM users WHERE id IN ($placeholders)", $types, $inactive_user_ids);
        $execute_bulk($conn, "DELETE FROM sessions WHERE user IN ($placeholders)", $types, $inactive_user_ids);
        $execute_bulk($conn, "DELETE FROM php_sessions WHERE userid IN ($placeholders)", $types, $inactive_user_ids);

        $conn->commit();
        $conn2->commit();
        $conn3->commit();

        $extensions = ['jpg', 'png', 'jpeg', 'webp', 'gif'];
        foreach ($inactive_user_ids as $id) {
            foreach ($extensions as $ext) {
                $pfp = "../acc/users/pfps/{$id}.{$ext}";
                $banner = "../acc/users/banners/{$id}..{$ext}";

                if (file_exists($pfp)) {
                    @unlink($pfp);
                }

                if (file_exists($banner)) {
                    @unlink($banner);
                }
            }
        }
    } catch (Exception $e) {
        $conn->rollback();
        $conn2->rollback();
        $conn3->rollback();

        $conn2->close();
        $conn3->close();

        error_log("cleanup failed " . $e->getMessage());
        echo "<center>The cleanup of deleted users has failed</center>";
        return false;
    }

    $conn2->close();
    $conn3->close();
    return true;
}

function loggedin() {
    //If session is already set, they are logged in
    if (isset($_SESSION['userid']) && isset($_SESSION['tokenid'])) {
        return true;
    }
    
    //If session died but they have a browser cookie, they might be loggable-in
    if (isset($_COOKIE['token'])) {
        return true;
    }

    return false;
}

function isLoggedin() {
    if(loggedin()) {
        return true;
    } else {
        return false;
    }
}
?>