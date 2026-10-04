<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/config.php';
$conn = Database::get(DB_NAME);

if(isset($_GET['ajax'])) {
    error_reporting(0);
}

function login() {
    global $id;
    if (!loggedin()) {
        return false;
    }

    $conn = Database::get(DB_NAME);

    $token_raw = $_SESSION['tokenid'] ?? $_COOKIE['token'];
    $token_hashed = hash('sha256', $token_raw);

    $session_stmt = $conn->prepare("SELECT user, timestamp FROM sessions WHERE id = ? LIMIT 1");
    $session_stmt->bind_param("s", $token_hashed);
    $session_stmt->execute();
    $session_res = $session_stmt->get_result();

    if ($session_res->num_rows <= 0) {
        $session_stmt->close();
        logout(false);
        return false;
    }

    $id = $session_res->fetch_assoc()['user'] ?? null;
    $user = User::getUser($id);

    if(!$user) {
        $session_stmt->close();
        logout(false);
        return false;
    }

    $user->id = $id;
    $_SESSION['tokenid'] = $token_raw;
    $_SESSION['userid'] = $id;

    $session_stmt->close();
    return $user;
}

//doing $current_user->username
//is faster than
//doing login()->username
//i think
$current_user = login();

if (loggedin()) {
    $lock_file = __DIR__ . '/.cleanup_lock';

    if (rand(1, 20) <= 1) {
        regenerate_session();
        clearstatcache(true, $lock_file);
    }

    if (!file_exists($lock_file) || (time() - filemtime($lock_file) > 3600)) {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/cleanup.php';
        $cleaner = new Cleaner(Database::get(DB_NAME), Database::get(DB_NAME2), Database::get(DB_NAME3));

        $cleaner->delete_inactive_users();
        $cleaner->delete_old_sessions();
        Cookie::del_old_analytics($conn, 3, 3600);

        @touch($lock_file);
    }
}

/**
 * The, well, "masterclass" of classes. Handles some helpful user/profile related logic to save on code elsewhere.
 */
class User {
    public ?int $id;
    public ?string $email;
    public ?string $github_id;
    public ?string $google_id;
    public ?string $username;
    public ?string $picture;
    public ?string $picture_small;
    public ?string $banner;
    public ?string $description;
    public ?string $twitter;
    public ?string $bsky;
    public ?bool $admin;
    public ?int $alert;
    public ?string $age;
    public ?string $verify_token;
    public ?bool $suspended;
    public ?bool $private_profile;
    public ?string $deactive;
    public ?int $changed;
    public ?string $ip;

    private const FIELDS = [
        'id',
        'email',
        'github_id',
        'google_id',
        'username',
        'picture',
        'picture_small',
        'banner',
        'description',
        'twitter',
        'bsky',
        'admin',
        'alert',
        'age',
        'verify_token',
        'private_profile',
        'suspended',
        'deactive',
        'changed'
    ];

    /**
     * Constuct user from database array using the defined public variables
     * Sets value as null if not found in array
    */
    public function __construct(?array $data = [])
    {
        foreach (self::FIELDS as $field) {
            $this->$field = $data[$field] ?? null;
        }

        $this->picture ??= $this->email ? $this->userGravatar($this->email, 200) : '/img/no_image.png';
        $this->picture_small ??= $this->picture;
    }

    /**
     * Checks if a users account doesn't exist or is marked for later deletion
     * @deprecated, please check if $usero->deactive is not null instead
     */
    public static function isDeleted(?int $id): bool {
        $conn = Database::get(DB_NAME);

        if (empty($id)) {
            return true;
        }

        $stmt = $conn->prepare("
            SELECT deactive
            FROM users
            WHERE id = ?
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            $stmt->close();
            return true; 
        }
        $row = $res->fetch_assoc();
        $stmt->close();

        if (empty($row)) {
            return true;
        }

        if ($row['deactive'] !== null) {
            return true;
        }

        return false;
    }

    /**
     * Check if a users profile is private
     */
    public static function isPrivate(?int $id): bool {
        $conn = Database::get(DB_NAME);

        if (empty($id)) {
            return false;
        }

        $stmt = $conn->prepare("SELECT private_profile FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $res = $stmt->get_result();
        $row = $res->fetch_assoc()['private_profile'];
        $stmt->close();

        if ((bool)$row === true) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the current user is being followed by a user OR if a user is following the current user
     */
    public static function isFollowing(?int $id): bool {
        global $current_user;
        $conn = Database::get(DB_NAME);

        if (empty($id) || !loggedin()) {
            return false;
        }

        $me = $current_user->id ?? 0;

        $stmt = $conn->prepare("SELECT 1 FROM follow WHERE (userid = ? AND profileid = ?) OR (profileid = ? AND userid = ?) LIMIT 1");
    	$stmt->bind_param("iiii", $me, $id, $me, $id);
    	$stmt->execute();
    	$res = $stmt->get_result();
    	$stmt->close();

        if ($res->num_rows !== 0) {
            return true;
        }

        return false;
    }

    /**
     * Like self::isFollowing , but AND in the sql query to check if they both follow each other
     */
    public static function areMutuals(?int $id): bool {
        global $current_user;
        $conn = Database::get(DB_NAME);

        if (empty($id) || !loggedin()) {
            return false;
        }

        $me = $current_user->id ?? 0;

        $stmt = $conn->prepare("SELECT 1 FROM follow WHERE (userid = ? AND profileid = ?) AND (profileid = ? AND userid = ?)");
    	$stmt->bind_param("iiii", $me, $id, $me, $id);
    	$stmt->execute();
    	$res = $stmt->get_result();
    	$stmt->close();

        if ($res->num_rows !== 0) {
            return true;
        }

        return false;
    }

    /**
     * Checks if your userID is the same as another
     * @deprecated, just check if $current_user->id is the same as the id, it doesn't take a genius to do that
     */
    public static function isMe(?int $id): bool {
        global $current_user;

        if (empty($id) || !loggedin()) {
            return false;
        }

        $me = (int)$current_user->id ?? 0;

        if ($id === $me || $id == $me) {
            return true;
        }

        return false;
    }

    /**
     * Checks block relations of users
     * Returns false or an array with data
     */
    public static function isBlocking(?int $profileid) {
        global $current_user, $conn;

        if(!loggedin()) {
            return false;
        }

        $you = false;
        $them = false;
        $type = null;
        $userid = $current_user->id ?? 0; //clarification: this is your user id profileid is their userid

        $stmt = $conn->prepare("SELECT userid, profileid FROM user_blocks WHERE (userid = ? AND profileid = ?) OR (userid = ? AND profileid = ?) LIMIT 1");
    	$stmt->bind_param("iiii", $userid, $profileid, $profileid, $userid);
    	$stmt->execute();
        $block_result = $stmt->get_result();

        if ($block_result && $block_result->num_rows > 0) {
            $stmt->close();

            while ($row = $block_result->fetch_assoc()) {
                if ($row['userid'] === $userid && $row['profileid'] === $profileid) {
                    $you = true;
                } elseif ($row['userid'] === $profileid && $row['profileid'] === $userid) {
                    $them = true;
                }
            }

            if ($you && $them) {
                $message = "You blocked this user, and they blocked you.";
                $type = 'both';
            } elseif ($you) {
                $message = "You blocked this user.";
                $type = 'you';
            } elseif ($them) {
                $message = "You're blocked from this user.";
                $type = 'them';
            } else {
                return false;
            }

            return array('message' => $message, 'type' => $type);
        }

        return false;
    }

    /**
     * Mini version of the ban helper from auth.php
     * I was tired of including it so much
     */
    public static function isBanned(?string $value = null, ?string $type = 'username') {
        if (empty($value)) { return false; }

        $conn = Database::get(DB_NAME);

        if ($type === 'email') {
            $value = hash('sha256', strtolower(trim($value)));
        } elseif ($type === 'username') {
            $value = strtolower(trim($value));
        } elseif ($type !== 'userid') {
            return false;
        }

        $sql = "SELECT * FROM blacklist 
                WHERE value = ? AND type = ? 
                AND (created_at IS NULL OR created_at <= CURRENT_TIMESTAMP()) 
                AND (ignore_at IS NULL OR ignore_at >= CURRENT_TIMESTAMP()) 
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("ss", $value, $type);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();

        $stmt->close();

        if ($row) {
            $row['reason'] = !empty($row['reason']) ? $row['reason'] : null;
            return $row;
        }

        return false;
    }

    /**
     * Like isBanned, but with the User's ID
     * This is helpful if the user has an existing account
     */
    public static function isBannedByID(int $id) {
        $conn = Database::get(DB_NAME);

        $stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ? AND deactive IS NULL");
        $stmt->bind_param("i", $id);

        if(!$stmt->execute()) {
            return false;
        }

        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if ($res->num_rows !== 0) {
            $email = strtolower(trim($user['email']));
            $email_hash = hash('sha256', $email);
            $username = strtolower(trim($user['username']));

            $conditions = [
                "(value = ? AND type = 'userid')",
                "(value = ? AND type = 'email')",
                "(value = ? AND type = 'email')",
                "(value = ? AND type = 'username')"
            ];

            $params = [(string)$id, $email, $email_hash, $username];
            $paramTypes = "ssss";

            $sql = "SELECT * FROM blacklist WHERE (" . implode(" OR ", $conditions) . ") 
                    AND (created_at IS NULL OR created_at <= CURRENT_TIMESTAMP()) 
                    AND (ignore_at IS NULL OR ignore_at >= CURRENT_TIMESTAMP()) 
                    LIMIT 1";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return false;
            }

            $stmt->bind_param($paramTypes, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();

            if ($row) {
                $row['reason'] = !empty($row['reason']) ? $row['reason'] : null;
                return $row;
            }
        }

        return false;
    }

    /**
     * Grabs and constucts a user object from an ID
     */
    public static function getUser(?int $id = 0) {
        $conn = Database::get(DB_NAME);

        $u_stmt = $conn->prepare("SELECT id, username, github_id, google_id, email, password, verify_token, admin, deactive, suspended, age, changed FROM users WHERE id = ? AND deactive IS NULL");
        $u_stmt->bind_param("i", $id);
        $u_stmt->execute();
        $user = $u_stmt->get_result()->fetch_assoc() ?? [];
        $u_stmt->close();

        if($user) {
            $p_stmt = $conn->prepare("SELECT picture, picture_small, banner, twitter, bsky, private_profile, description FROM user_profiles WHERE userid = ?");
            $p_stmt->bind_param("i", $id);
            $p_stmt->execute();
            $profile = $p_stmt->get_result()->fetch_assoc() ?? [];
            $p_stmt->close();

            return new User(array_merge($user, $profile));
        }

        return null;
    }

    /**
     * Like getUser, but with a name instead of an ID
     */
    public static function getUserByName(?string $username) {
        $conn = Database::get(DB_NAME);

        $u_stmt = $conn->prepare("SELECT id, username, github_id, google_id, email, password, verify_token, admin, deactive, suspended, age, changed FROM users WHERE username = ? AND deactive IS NULL");
        $u_stmt->bind_param("s", $username);
        $u_stmt->execute();
        $user = $u_stmt->get_result()->fetch_assoc() ?? [];
        $userid = $user['id'];
        $u_stmt->close();

        if($user) {
            $p_stmt = $conn->prepare("SELECT picture, picture_small, banner, twitter, bsky, private_profile, description FROM user_profiles WHERE userid = ?");
            $p_stmt->bind_param("i", $userid);
            $p_stmt->execute();
            $profile = $p_stmt->get_result()->fetch_assoc() ?? [];
            $p_stmt->close();

            return new User(array_merge($user, $profile));
        }

        return null;
    }

    /**
     * Bulk select of users from an array of IDs
     * Outputs an array containing the userid as a key and the user object as a value
     */
    public static function getUsers(?array $ids): array {
        if (empty($ids)) {
            return [];
        }

        $conn = Database::get(DB_NAME);
        $ids = array_unique(array_map('intval', $ids));
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));

        $profiles = [];
        $users = [];
        $users = [];

        $u_stmt = $conn->prepare("SELECT id, username, github_id, google_id, email, password, verify_token, admin, deactive, suspended, age, changed FROM users WHERE id IN ($placeholders) AND deactive IS NULL");
        $types = str_repeat('i', count($ids));
        $u_stmt->bind_param($types, ...$ids);
        $u_stmt->execute();
        $u_res = $u_stmt->get_result();

        while ($row = $u_res->fetch_assoc()) {
            $users[$row['id']] = $row;
        }

        $p_stmt = $conn->prepare("SELECT userid, picture, picture_small, banner, twitter, bsky, private_profile, description FROM user_profiles WHERE userid IN ($placeholders)");
        $types = str_repeat('i', count($ids));
        $p_stmt->bind_param($types, ...$ids);
        $p_stmt->execute();
        $p_res = $p_stmt->get_result();

        while ($row = $p_res->fetch_assoc()) {
            $profiles[$row['userid']] = $row;
        }

        foreach ($ids as $id) {
            $user = $users[$id] ?? [];
            $profile = $profiles[$id] ?? [];

            $usero = new User(array_merge($user, $profile));
            $users[$usero->id] = $usero;
        }

        $u_stmt->close();
        $p_stmt->close();
        return $users;
    }

    /**
     * Constucts a Gravatar image URL from an email
     */
    private function userGravatar(?string $email, ?int $size = 50) {
        if(empty($email)) {
            return null;
        }

        $hash = hash('sha256', strtolower(trim($email)));
        $params = ['s' => $size,'d' => 'identicon','r' => 'pg'];

        return "https://www.gravatar.com/avatar/" . $hash . "?" . http_build_query($params);
    }

    public static function get_alert(?int $userId) {
        $conn = Database::get(DB_NAME);

        if(!isset($userId)) {
            return 0;
        }

        $stmt = $conn->prepare("SELECT COUNT(*) as alert FROM notifications WHERE user = ? AND is_read = 0 AND category2 IS NOT NULL");
        $stmt->bind_param("i", $userId);

        if($stmt->execute()) {
            $alert = $stmt->get_result()->fetch_assoc()['alert'] ?? 0;
            $stmt->close();

            return $alert;
        }

        return 0;
    }

    /**
     * Check if an account is email verified
     * @deprecated, check if verify_token is null instead
     */
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
    $conn = Database::get(DB_NAME);

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

    $conn = Database::get(DB_NAME);
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

if(basename($_SERVER['PHP_SELF']) === "user.php") {
    if(isset($_GET['get_warn_status'])) {
        header("Content-type: application/json");
        echo json_encode(get_warn_status());
        exit;
    }

    if(isset($_GET['seen_warn_status'])) {
        header("Content-type: application/json");
        echo json_encode(seen_warn_status());
        exit;
    }

    if (isset($_GET['ajax'])) {
        header('Content-type: application/json');

        if(!loggedin()) {
            echo json_encode(['error' => 'User is not authenticated']);
            exit;
        }
        $id = $current_user->id;

        $followers_stmt = $conn->prepare("SELECT COUNT(*) as count FROM follow WHERE profileid = ? LIMIT 1");
        $followers_stmt->bind_param("i", $id);
        $followers_stmt->execute();
        $res = $followers_stmt->get_result();
        $followers_count = $res->fetch_assoc()['count'] ?? 0;

        $following_stmt = $conn->prepare("SELECT COUNT(*) as count FROM follow WHERE userid = ? LIMIT 1");
        $following_stmt->bind_param("i", $id);
        $following_stmt->execute();
        $res = $following_stmt->get_result();
        $following_count = $res->fetch_assoc()['count'] ?? 0;

        $logindata = json_encode([
            'success' => true,
            'id' => $current_user->id,
            'pfp' => $current_user->picture_small,
            'user' => $current_user->username,
            'alert' => User::get_alert($id),
            'is_verified' => empty($current_user->verify_token) ? true : false,
            'stats' => [
                'followers' => $followers_count ?? 0,
                'following' => $following_count ?? 0,
            ]
        ]);
        echo $logindata;
        exit;
    }
}

function logout(?bool $redirect = false) {
    global $conn;

    $token_raw = $_SESSION['tokenid'] ?? $_COOKIE['token'] ?? null;

    if ($token_raw !== null) {
        $token_hashed = hash('sha256', $token_raw);

        if (!$conn->connect_error) {
            $stmt = $conn->prepare("DELETE FROM sessions WHERE id = ? LIMIT 1");
            $stmt->bind_param("s", $token_hashed);
            $stmt->execute();
            $stmt->close();
        }
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
    }

    if (isset($_COOKIE['token'])) {
        setcookie('token', '');
        unset($_COOKIE['token']);
    }

    if ($redirect === true) {
        header('Location: /index.php');
        exit;
    }
}

function regenerate_session() {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/what_browser.php';
    global $conn;

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
            return logout(false);
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

    $stmt = $conn->prepare("UPDATE sessions SET id = ?, timestamp = ?, login_from = ?, user_agent = ? WHERE id = ? AND remember = 1");
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
        $stmt->close();
        return false;
    }
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

/**
* @deprecated, use if statement with loggedin()
*/
function isLoggedin() {
    if(loggedin()) {
        return true;
    } else {
        return false;
    }
}
?>