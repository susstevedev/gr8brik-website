<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/notifications.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/com/bbcode.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/jbroadway/Slimdown.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';
$bbcode = new BBCode;

if (isset($_GET['followed_by'])) {
    header('Content-Type: application/json');

    if (!loggedin()) {
        http_response_code(401);
        echo json_encode(['error' => 'not logged in', 'success' => false]);
        exit;
    }

    $profile_id = isset($_GET['userid']) ? $_GET['userid'] : (int)$_GET['followed_by'];
    $current_user_id = $current_user->id ?? 0;
    $followed_by = [];
    $rows = [];

    if(!User::getUser($profile_id) && (User::isPrivate($profile_id) && !User::isFollowing($profile_id))) {
        http_response_code(404);
        echo json_encode(['error' => 'invalid account id', 'success' => false]);
        exit;
    }

    $conn = Database::get(DB_NAME);

    //selects user follow row(s), selects users actual account, fliters invalid accounts
    $query = "
        SELECT DISTINCT u.id, u.username, p.picture
        FROM follow f1
        INNER JOIN follow f2 ON f1.userid = f2.profileid
        INNER JOIN users u ON f1.userid = u.id
        LEFT JOIN user_profiles p ON f1.userid = p.userid
        WHERE f1.profileid = ? 
        AND f2.userid = ?
        AND u.deactive IS NULL
        AND u.suspended = 0
        ORDER BY u.id DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $profile_id, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $followed_by = [];
    $rows = $result->fetch_assoc();

    foreach($rows as $row) {
        if(!$rows || (isset($_SESSION['fb'][$profile_id]))) {
            http_response_code(301);
            unset($_SESSION['fb']);
            exit;
        }

        if(!isset($row['id'])) continue;

        $followed_by[] = [
            'url' => '/@' . urlencode($row['username'] ?? ''),
            'userid' => $row['id'], 
            'pfp' => $row['picture'] ?? '/img/no_image.png',
            'username' => htmlspecialchars($row['username'] ?? '')
        ];
    }

    $stmt->close();

    http_response_code(200);
    echo json_encode($followed_by);
    exit;
}

class UserProfile {
    /**
     * Raw user table row
    */
    private static function raw_user(mixed $identifier, $usename = true) {
        $conn = Database::get(DB_NAME);

        switch ($usename) {
            case true:
                $u_stmt = $conn->prepare("SELECT id, username, github_id, google_id, email, password, verify_token, admin, deactive, suspended, age, changed FROM users WHERE username = ?");
                $u_stmt->bind_param("s", $identifier);
                break;
            case false:
                $u_stmt = $conn->prepare("SELECT id, username, github_id, google_id, email, password, verify_token, admin, deactive, suspended, age, changed FROM users WHERE id = ?");
                $u_stmt->bind_param("i", $identifier);
                break;
        }

        $u_stmt->execute();
        $user = $u_stmt->get_result()->fetch_assoc() ?? [];

        if($user) {
            $userid = $user['id'] ?? null;
            $u_stmt->close();

            $p_stmt = $conn->prepare("SELECT picture, picture_small, banner, twitter, bsky, private_profile, description FROM user_profiles WHERE userid = ?");
            $p_stmt->bind_param("i", $userid);
            $p_stmt->execute();
            $profile = $p_stmt->get_result()->fetch_assoc() ?? [];
            $p_stmt->close();

            return array(
                'id' => $userid,
                'user' => $user,
                'profile' => $profile
            );
        }

        return null;
    }

	/**
		* Array of raw user table(s)
	*/
	public static function multi_raw_users(array $ids): array {
        $conn = Database::get(DB_NAME);

        $ids = array_unique(array_map('intval', $ids));
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));

		$final = [];
        $profiles = [];
        $users = [];

        $u_stmt = $conn->prepare("SELECT id, username, github_id, google_id, email, password, verify_token, admin, deactive, suspended, age, changed FROM users WHERE id IN ($placeholders)");
        $types = str_repeat('i', count($ids));
        $u_stmt->bind_param($types, ...$ids);
        $u_stmt->execute();
        $u_res = $u_stmt->get_result();

        while ($r = $u_res->fetch_assoc()) {
            $users[$r['id']] = $r;
        }

        $p_stmt = $conn->prepare("SELECT userid, picture, picture_small, banner, twitter, bsky, private_profile, description FROM user_profiles WHERE userid IN ($placeholders)");
        $types = str_repeat('i', count($ids));
        $p_stmt->bind_param($types, ...$ids);
        $p_stmt->execute();
        $p_res = $p_stmt->get_result();

        while ($r = $p_res->fetch_assoc()) {
            $profiles[$r['userid']] = $r;
        }

        foreach ($ids as $id) {
            $final[$id] = [
                'id' => $id,
                'user' => $users[$id] ?? [],
                'profile' => $profiles[$id] ?? []
            ];
        }

        $u_stmt->close();
        $p_stmt->close();
        return $final;
    }

	/**
     * Constucts a Gravatar image URL from an email
     */
    public static function gravatar(?string $email, ?int $size = 50) {
        if(empty($email)) {
            return null;
        }

        $hash = hash('sha256', strtolower(trim($email)));
        $params = ['s' => $size,'d' => 'identicon','r' => 'pg'];

        return "https://www.gravatar.com/avatar/" . $hash . "?" . http_build_query($params);
    }

    /**
     * Returns public user profile
    */
    public static function get(mixed $identifier, mixed $csrf, bool $use_name = true) {
        global $current_user;
        $userid = $current_user->id ?? null;

        if (empty($csrf) || $csrf != $_SESSION['csrf']) {
            return [
                "success" => false,
                "code" => 'csrf_missing',
                "title" => 'Something tech-related',
                "message" => 'No CSRF (cross-site-request-forgery) token provided, or it is invalid!',
            ];
        }

        $conn = Database::get(DB_NAME);
        $user_arr = self::raw_user($identifier, $use_name);
        $profile_id = $user_arr['user']['id'];

        if (!isset($user_arr) || !$user_arr) {
            http_response_code(404);
            return [
                "success" => false,
                "userid" => $profile_id,
                "code" => 'not_found',
                "title" => '404 Not Found',
                "message" => 'This profile wasn\'t found. Maybe you typed the username wrong?',
            ];
        }

        if ($user_arr['user']['suspended'] || $user_arr['user']['deactive']) {
            http_response_code(403);
            $banrow = User::isBannedByID($profile_id);

            if($banrow) {
                $until = isset($banrow['ignore_at']) ? ('until ' . date("d M Y", strtotime($banrow['ignore_at']))) : 'indefinitely';

                $arr = [
                    "success" => false,
                    "userid" => $profile_id,
                    "code" => 'account_banned',
                    "title" => 'Account suspended',
                    "message" => 'This account has been suspended ' . $until . '.<br />When an account is suspended, the owner can\'t sign in or interact with content. However, they can appeal the ban.',
                ];
            } else {
                $arr = [
                    "success" => false,
                    "userid" => $profile_id,
                    "code" => 'not_found',
                    "title" => 'Something went wrong',
                    "message" => 'Please try again later',
                ];
            }

            if(loggedin() && $current_user->admin) {
                $arr['email'] = $user_arr['user']['email'];
            }

            return $arr;
        }

        $bsky = $user_arr['profile']['bsky'] ?? null;
        $is_blocking = false;
        $is_following = false;
        $is_me = false;
        $is_admin = false;
        $is_private = $user_arr['profile']['private_profile'] ?? false;

        if(loggedin()) {
            $blocks = User::isBlocking($profile_id);

            if($blocks && is_array($blocks)) {
                if($blocks['type'] !== 'you') {
                    $reason = $blocks['message'];

                    if(!empty($blocks['reason'])) {
                        $reason .= '\n The reason for the block:';
                        $reason .= '\n' . $blocks['reason'];
                    }

                    http_response_code(403);
                    return [
                        "success" => false,
                        "userid" => $profile_id,
                        "code" => 'user_blocking',
                        "title" => 'User blocked you',
                        "message" => $reason,
                    ];
                } else {
                    $is_blocking = true;
                }
            }

            $stmt = $conn->prepare("SELECT COUNT(*) as following FROM follow WHERE userid = ? AND profileid = ?");
            $stmt->bind_param("ii", $userid, $profile_id);
            $stmt->execute();
            $is_following = $stmt->get_result()->fetch_assoc()['following'];
            $stmt->close();

            if((int)$userid === (int)$profile_id) {
                $is_me = true;
            }

            if($current_user->admin) {
                $adm_email = isset($user_arr['user']['email']) ? htmlspecialchars($user_arr['user']['email']) : '';
                $is_admin = true;
            }
        }

        if(!$is_me && !$is_admin && !$is_following && $is_private) {
            http_response_code(403);

            return [
                "success" => false,
                "userid" => $profile_id,
                "code" => 'profile_private',
                "title" => 'Profile private',
                "message" => 'The owner of this account has privated their profile.<br />When an account is private, other users can\'t view the profile unless they are following said user, or said user is following them.',
            ];
        }

        $conn2 = Database::get(DB_NAME2);
        $conn3 = Database::get(DB_NAME3);

        $stmt = $conn2->prepare("SELECT COUNT(*) as all_models FROM model WHERE user = ?");
        $stmt->bind_param("s", $profile_id);
        $stmt->execute();
        $model_count = $stmt->get_result()->fetch_assoc()['all_models'] ?? 0;
        $stmt->close();

        $stmt = $conn2->prepare("SELECT SUM(views) as total_views FROM model WHERE user = ?");
        $stmt->bind_param("s", $profile_id);
        $stmt->execute();
        $views = $stmt->get_result()->fetch_assoc()['total_views'] ?? 0;
        $stmt->close();

        $stmt = $conn2->prepare("SELECT SUM(likes) as total_likes FROM model WHERE user = ?");
        $stmt->bind_param("s", $profile_id);
        $stmt->execute();
        $likes = $stmt->get_result()->fetch_assoc()['total_likes'] ?? 0;
        $stmt->close();

        $stmt = $conn->prepare("SELECT COUNT(*) as following FROM follow WHERE profileid = ?");
        $stmt->bind_param("s", $profile_id);
        $stmt->execute();
        $followers = $stmt->get_result()->fetch_assoc()['following'] ?? 0;
        $stmt->close();

        $stmt = $conn->prepare("SELECT COUNT(*) as following FROM follow WHERE userid = ?");
        $stmt->bind_param("s", $profile_id);
        $stmt->execute();
        $following = $stmt->get_result()->fetch_assoc()['following'] ?? 0;
        $stmt->close();

        $stmt = $conn3->prepare("SELECT COUNT(*) as reply_count FROM messages WHERE userid = ? AND deleted_at IS NULL");
        $stmt->bind_param("i", $profile_id);
        $stmt->execute();
        $user_post_count = $stmt->get_result()->fetch_assoc()['reply_count'] ?? 0;
        $stmt->close();

        $message = null;
        $data = [
            'success' => true,
            'userid' => $profile_id,
            'username' => htmlspecialchars($user_arr['user']['username'] ?? ''),
            'admin' => (string)$user_arr['user']['admin'],
            'description' => isset($user_arr['profile']['description']) ? Slimdown::render($user_arr['profile']['description']) : '', 
            'twitter' => isset($user_arr['profile']['twitter']) ? htmlspecialchars($user_arr['profile']['twitter']) : '',
            'bsky' => $bsky,
            'age' => isset($user_arr['user']['age']) ? htmlspecialchars($user_arr['user']['age']) : '',
            'picture' => htmlspecialchars($user_arr['profile']['picture_small'] ?? $user_arr['profile']['picture']),
            'banner' => isset($user_arr['profile']['banner']) ? htmlspecialchars($user_arr['profile']['banner']) : null,
            'stats' => [
                'creation_count' => $model_count,
                'followers' => $followers,
                'following' => $following,
                'forum_posts' => $user_post_count,
                'views' => $views,
                'likes' => $likes,
            ],
            'is_following' => (bool)$is_following,
            'is_blocking' => (bool)$is_blocking,
            'is_private' => (bool)$is_private,
            'message' => $message,
        ];

        if($is_admin) {
            $data['email'] = $adm_email ?? null;
        }

        return $data;
    }
}

//replacement for the old one
if(isset($_GET['profile']) && (isset($_GET['actorId']) || isset($_GET['actorName']))) {
    header('Content-Type: application/json');

    $profile = $_GET['actorId'] ?? $_GET['actorName'] ?? null;
    $use_name = isset($_GET['actorName']) ? true : false;

    $data = UserProfile::get($profile, $_SESSION['csrf'], $use_name);
    echo json_encode($data);
    exit;
}

/**
 * User related interactions (blocking, following, etc)
 */
class UserInteractions {
    public int $userid;
    public object $current_user;
    public mysqli $conn;

    public function __construct() {
        global $current_user;

        if(!loggedin() || !isset($current_user)) {
            return;
        }

        $this->userid = $current_user->id;
        $this->current_user = $current_user;
        $this->conn = Database::get(DB_NAME);
    }

    /**
     * Follows a user
     */
    public function followUser(int $profile_id) {
        $error_title = 'Follow user';

        if(!loggedin()) {
            $error = "Please login to follow this user";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if(User::isDeleted($profile_id)) {
            $error = "No user found";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        $time = time();

        if((int)$this->userid === $profile_id) {
            $error = "You cannot follow yourself";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if($this->current_user->verify_token !== NULL) {
            $error = "User account is not verified";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if(!isset($error)) {
            $sql_follow = "INSERT INTO follow (userid, profileid, date) VALUES (?, ?, ?)";
            $stmt_follow = $this->conn->prepare($sql_follow);
            $stmt_follow->bind_param("iii", $this->userid, $profile_id, $time);
            $result = $stmt_follow->execute();

            $notification = new Notifications($this->conn);
            $notification->subscribe($this->userid, 'follow', $profile_id);
            $notification->notify_subscribers('follow', $profile_id, $this->userid);

            if ($result) {
                $stmt_follow->close();

                $message = "Followed this user with success";
                return [
                    'success' => true,
                    'message' => $message,
                    'title' => 'Success!'
                ];
            } else {
                $error = "An error occured while following this user";
                return [
                    'success' => false,
                    'message' => $error,
                    'title' => $error_title
                ];
            }
        }
    }

    /**
     * Unfollows a user
     */
    public function unfollowUser(int $profile_id) {
        $error_title = 'Unfollow user';

        if(!loggedin()) {
            $error = "Please login to unfollow this user";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if(User::isDeleted($profile_id)) {
            $error = "No user found";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if((int)$this->userid === $profile_id) {
            $error = "You cannot unfollow yourself";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if($this->current_user->verify_token !== NULL) {
            $error = "User account is not verified";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if(!isset($error)) {
            $sql_follow = "DELETE FROM follow WHERE userid = ? AND profileid = ?";
            $stmt_follow = $this->conn->prepare($sql_follow);
            $stmt_follow->bind_param("ii", $this->userid, $profile_id);
            $result = $stmt_follow->execute();

            $notification = new Notifications($this->conn);
            $notification->remove_subscriber('follow', $profile_id, $this->userid);

            if ($result) {
                $stmt_follow->close();

                $message = "Unollowed this user with success";
                return [
                    'success' => true,
                    'message' => $message,
                    'title' => 'Success!'
                ];
            } else {
                $error = "An error occured while unfollowing this user";
                return [
                    'success' => false,
                    'message' => $error,
                    'title' => $error_title
                ];
            }
        }
    }

    /**
     * Blocks a user
     */
    public function blockUser(int $profile_id) {
        $error_title = 'Block user';
        $time = time();

        if(!loggedin()) {
            $error = "Please login to block this user";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if(User::isDeleted($profile_id)) {
            $error = "No user found";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if((int)$this->userid === $profile_id) {
            $error = "You cannot block yourself";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if($this->current_user->verify_token !== NULL) {
            $error = "User account is not verified";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if(!isset($error)) {
            $sql_follow = "DELETE FROM follow WHERE userid = ? AND profileid = ?";
            $stmt_follow = $this->conn->prepare($sql_follow);
            $stmt_follow->bind_param("ii", $this->userid, $profile_id);
            $result = $stmt_follow->execute();

            if ($result) {
                $stmt_follow->close();

                $sql_block = "INSERT INTO user_blocks (userid, profileid, date) VALUES (?, ?, ?)";
                $stmt_block = $this->conn->prepare($sql_block);
                $stmt_block->bind_param("iii", $userid, $profile_id, $time);
                $result = $stmt_block->execute();
                $stmt_block->close();

                if ($result) {
                    $message = "Blocked this user with success";
                    return [
                        'success' => true,
                        'message' => $message,
                        'title' => 'Success!'
                    ];
                } else {
                    $error = "An error has happened while blocking this user";
                    return [
                        'success' => true,
                        'message' => $error,
                        'title' => 'Success!'
                    ];
                }
            } else {
                $error = "An error occurred while unfollowing this user";
                return [
                    'success' => false,
                    'message' => $error,
                    'title' => $error_title
                ];
            }
        }
    }

    /**
     * Unblocks a user
     */
    public function unblockUser(int $profile_id) {
        $error_title = 'Unblock user';

        if(!loggedin()) {
            $error = "Please login to unblock this user";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if(User::isDeleted($profile_id)) {
            $error = "No user found";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if((int)$this->userid === $profile_id) {
            $error = "You cannot unblock yourself";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if($this->current_user->verify_token !== NULL) {
            $error = "User account is not verified";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if(!isset($error)) {
            $sql_follow = "DELETE FROM user_blocks WHERE userid = ? AND profileid = ?";
            $stmt_follow = $this->conn->prepare($sql_follow);
            $stmt_follow->bind_param("ii", $this->userid, $profile_id);
            $result = $stmt_follow->execute();

            if ($result) {
                $stmt_follow->close();

                $message = "Unblocked this user with success";
                return [
                    'success' => true,
                    'message' => $message,
                    'title' => 'Success!'
                ];
            } else {
                $error = "An error occurred while unblocking this user";
                return [
                    'success' => false,
                    'message' => $error,
                    'title' => $error_title
                ];
            }
        }
    }
}

/**
 * UserInteractions but for site admins, basically
 */
class UserAdmin {
    public int $userid;
    public object $current_user;
    public mysqli $conn;

    public function __construct() {
        global $current_user;

        if(!loggedin() || !isset($current_user)) {
            return;
        }

        if(!$current_user->admin) {
            return;
        }

        $this->userid = $current_user->id;
        $this->current_user = $current_user;
        $this->conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    }

    /**
     * Warns a user
     */
    public function warnUser(int $profile_id, string $reason) {
        $error_title = 'Warn user';

        if(!loggedin()) {
            $error = "Please login to warn this user";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if(!isset($this->current_user) || !$this->current_user->admin || $this->current_user->verify_token !== NULL) {
            $error = "An authentication error has occurred";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if(User::isDeleted($profile_id)) {
            $error = "No user found";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        $time = time();
        $reason = isset($reason) ? $reason : null;

        if((int)$this->userid === $profile_id) {
            $error = "You cannot warn yourself";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if(!$reason) {
            $error = "No reason has been provided";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if(!isset($error)) {
            $sql_warn = "INSERT INTO warnings (user, reason, timestamp) VALUES (?, ?, ?)";
            $stmt_warn = $this->conn->prepare($sql_warn);
            $stmt_warn->bind_param("iii", $profile_id, $reason, $time);
            $result = $stmt_warn->execute();

            if ($result) {
                $stmt_warn->close();

                $message = "Warned this user with success";
                return [
                    'success' => true,
                    'message' => $message,
                    'title' => 'Success!'
                ];
            } else {
                $error = "An error occured while warning this user";
                return [
                    'success' => false,
                    'message' => $error,
                    'title' => $error_title
                ];
            }
        }
    }

    /**
     * Blacklists a user's details, preventing them from using the platform
     */
    public function removeUser(int $profile_id, bool $ignore, bool $use_email, ?string $until, ?string $reason, ?string $email) {
        $error_title = 'Remove user';
        $reason = isset($reason) ? trim($reason) : 'banned by admin request';
        $until = isset($until) ? $until : null;

        if(!loggedin()) {
            $error = "Please login to remove this user";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if(!isset($this->current_user) || !$this->current_user->admin || $this->current_user->verify_token !== NULL) {
            $error = "An authentication error has occurred";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if(User::isDeleted($profile_id)) {
            $error = "No user found";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if((int)$this->userid === $profile_id) {
            $error = "You cannot unremove yourself";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if (!empty($until) && !$ignore) {
            $date = new DateTime($until);

            if ($date) {
                $until = $date->format('Y-m-d H:i:s');
            } else {
                $error = "Invalid ban until date provided by client";
                return [
                    'success' => false,
                    'message' => $error,
                    'title' => $error_title
                ];
            }
        } else {
            $until = null;
        }

        if($use_email && !empty($email)) {
            $identifier = hash('sha256', strtolower(trim($email)));
            $type = 'email';
        } else {
            $identifier = $profile_id;
            $type = 'userid';
        }

        if(!isset($error)) {
            $stmt_block = $this->conn->prepare("INSERT IGNORE INTO blacklist (value, type, reason, ignore_at) VALUES (?, ?, ?, ?)");
            $stmt_block->bind_param("ssss", $identifier, $type, $reason, $until);
            $result_block = $stmt_block->execute();
            $stmt_block->close();

            if(!$result_block) {
                $error = "Couldn't ban this user at this time";
                return [
                    'success' => false,
                    'message' => $error,
                    'title' => $error_title
                ];
            } else {
                $queries = [["UPDATE php_sessions SET active = 0 WHERE userid = ?", "i", $profile_id], ["UPDATE sessions SET timestamp = 0 WHERE user = ?", "i", $profile_id], ["UPDATE users SET suspended = 1 WHERE id = ?", "i", $profile_id]];

                if($use_email && !empty($email)) {
                    $queries[] = ["UPDATE users SET deactive = '9999-12-31' WHERE id = ?", "i", $profile_id];
                }

                foreach ($queries as [$sql, $type, $id]) {
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param($type, $id);

                    if (!$stmt->execute()) {
                        $stmt->close();

                        return [
                            'success' => false,
                            'message' => "Couldn't log this user out when banning the account",
                            'title' => $error_title
                        ];
                    }

                    $stmt->close();
                }

                $message = "Blacklisted this user with success";
                return [
                    'success' => true,
                    'message' => $message,
                    'title' => 'Success!'
                ];
            }
        }
    }

    /**
     * Undoes what removeUser does
     */
    public function unblacklistUser(int $profile_id, string $email) {
        $error_title = 'Unblacklist user';

        if(!loggedin()) {
            $error = "Please login to unremove this user";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if(!isset($this->current_user) || !$this->current_user->admin || $this->current_user->verify_token !== NULL) {
            $error = "An authentication error has occurred";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        $email = hash('sha256', strtolower(trim($email)));
        $usero = User::getUser($profile_id);

        if(!$usero) {
            $error = "User account does not exist";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        $sql_follow = "SELECT id FROM blacklist WHERE (value = ? AND type = 'userid') OR (value = ? AND type = 'email') LIMIT 1";
        $stmt_follow = $this->conn->prepare($sql_follow);
        $stmt_follow->bind_param("ss", $profile_id, $email);
        $stmt_follow->execute();
        $result = $stmt_follow->get_result();

        if($result->num_rows <= 0 || !$usero->suspended) {
            $error = "User account is not blacklisted";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if(!isset($error)) {
            $queries = [["DELETE FROM blacklist WHERE (type = 'userid' AND value = ?) OR (type = 'email' AND value = ?)", "ss", [$profile_id, $email]], ["UPDATE users SET deactive = NULL AND suspended = 0 WHERE id = ?", "i", [$profile_id]]];

            foreach ($queries as [$sql, $type, $vals]) {
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param($type, ...$vals);

                if (!$stmt->execute()) {
                    $stmt->close();

                    return [
                        'success' => false,
                        'message' => "An error occurred while unblacklisting this user",
                        'title' => $error_title
                    ];
                }

                $stmt->close();
            }

            $message = "Unblacklisted this user with success";
            return [
                'success' => true,
                'message' => $message,
                'title' => 'Success!'
            ];
        }
    }
}

class UserContent {
    public string $creation;
    public string $userid;

    public function returnModels($userid, $page) {
        $creation_conn = Database::get(DB_NAME2);

        if ($creation_conn->connect_error) {
            return ['success' => false, 'error' => "Database connection failed"];
        }

        if($page < 1) {
            return ['success' => false, 'error' => 'Invalid page number'];
        }

        if (!loggedin()) {
            return ['success' => false, 'error' => "Sign in to view creations of a user"];
        }

        $limit = 9;
        $offset = ($page - 1) * $limit;
        $user = User::getUser($userid);

        if(!$user || User::isDeleted($userid)) {
            return ['success' => false, 'error' => "What user is this?"];
        }

        if(!User::isMe($userid) && !User::isFollowing($userid) && User::isPrivate($userid)) {
            return ['success' => false, 'error' => "This profile is private."];
        }

        $stmt = $creation_conn->prepare("SELECT id, name, user, date, screenshot, views, likes, replies FROM model WHERE user = ? AND visibility = 'public' AND removed = 0 ORDER BY date DESC LIMIT $limit OFFSET $offset");
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $result = $stmt->get_result();

        $creations = [];
        while ($creation = $result->fetch_assoc()) {
            $creation['username'] = $user->username ?? null;
            $creation['user'] = $user->id ?? null;
                    
            if (empty($creation['name'])) {
                $creation['name'] = $creation['username'] . "'s creation";
            }

            $creation['date'] = time_ago($creation['date']);

            $truncatedName = htmlspecialchars(substr($creation['name'], 0, 30));
            if (strlen($creation['name']) >= 30) {
                $truncatedName .= '...';
                $creation['name'] = $truncatedName;
            }

            $creations[] = $creation;
        }
        $stmt->close();
        return ['success' => true, 'creations' => $creations];
    }

    public function returnLikedModels($userid, $page) {
        $creation_conn = Database::get(DB_NAME2);

		if ($creation_conn->connect_error) {
            return ['success' => false, 'error' => "Database connection failed"];
        }

        if($page < 1) {
            return ['success' => false, 'error' => 'Invalid page number'];
        }

        if (!loggedin()) {
            return ['success' => false, 'error' => "Sign in to view liked creations of a user"];
        }

        $limit = 9;
        $offset = ($page - 1) * $limit;
        $user = User::getUser($userid);

        $user = User::getUser($userid);

        if(!$user || User::isDeleted($userid)) {
            return ['success' => false, 'error' => "What user is this?"];
        }

        if(!User::isMe($userid) && !User::isFollowing($userid) && User::isPrivate($userid)) {
            return ['success' => false, 'error' => "This profile is private."];
        }

        $stmt = $creation_conn->prepare('SELECT * FROM votes WHERE user = ? ORDER BY id DESC LIMIT ? OFFSET ?');
        $stmt->bind_param('iss', $userid, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $liked = [];

        $creations = [];
        if ($result->num_rows != 0) {
            while ($row = $result->fetch_assoc()) {
                $liked[] = $row['creation'];
            }

            $stmt2 = $creation_conn->prepare("SELECT id, name, user, date, screenshot, views, likes, replies FROM model WHERE id IN (" . implode(',', $liked) . ") AND visibility = 'public' AND removed = 0 ORDER BY date DESC");
            $stmt2->execute();
            $result2 = $stmt2->get_result();

            if ($result2->num_rows != 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $model_user_id = $row2['user'] ?? null;
                    $usero = User::getUser($model_user_id);
                    $row2['username'] = $usero->username ?? null;

                    if(User::isDeleted($model_user_id) || User::isBannedByID($model_user_id) || (User::isPrivate($model_user_id) && !User::isFollowing($model_user_id) && !User::isMe($model_user_id))) {
                        continue;
                    }

                    if (empty($row2['name'])) {
                        $row2['name'] = $row2['username'] . "'s creation";
                    }

                    $row2['date'] = time_ago($row2['date']);

                    $row2['name'] = htmlspecialchars(mb_strimwidth($row2['name'], 0, 33, '...'));
                    $creations[] = $row2;
                }
                return ['success' => true, 'creations' => $creations];
            }
        } else {
            return ['success' => false, 'creations' => null, 'error' => 'No creations found for the selected query'];
        }
        return ['success' => false, 'error' => "Couldn't load creations that this user liked"];
    }

    public function returnComments($userid, $page) {
        $conn_creations = Database::get(DB_NAME2);
        $conn_forum = Database::get(DB_NAME3);

        $limit = 8;
        $offset = ($page - 1) * $limit;

        $creation_replies = [];
        $forum_replies = [];

        if ($conn_forum->connect_error) {
            echo $conn_forum->connect_error;
            exit;
        }

        if ($conn_creations->connect_error) {
            echo $conn_creations->connect_error;
            exit;
        }

        if($page < 1) {
            return ['success' => false, 'error' => 'Invalid page number'];
        }

        if (!loggedin()) {
            return ['success' => false, 'error' => "Sign in to view comments of a user"];
        }

        $user = User::getUser($userid);

        if(!$user || User::isDeleted($userid)) {
            return ['success' => false, 'error' => "What user is this?"];
        }

        if(!User::isMe($userid) && !User::isFollowing($userid) && User::isPrivate($userid)) {
            return ['success' => false, 'error' => "This profile is private."];
        }

        // comments and replies
        $profileid = $userid; // whatever
        $profile_stmt = $conn_creations->prepare("SELECT id, user, comment, model, date FROM comments WHERE hidden = 0 AND user = ? ORDER BY id DESC LIMIT $limit OFFSET $offset;");
        $profile_stmt->bind_param("s", $profileid);
        $profile_stmt->execute();
        $result = $profile_stmt->get_result();

        while ($comment = $result->fetch_assoc()) {
            $parent = $comment['model'];
            $comment2 = [];

            $stmt = $conn_creations->prepare("SELECT name FROM model WHERE id = ?");
            $stmt->bind_param("i", $parent);
            $stmt->execute();
            $result_parent = $stmt->get_result();

            if($result_parent->num_rows != 0) {
                $parent_name = $result_parent->fetch_assoc()['name'];
                $comment2['parent_name'] = mb_strimwidth($parent_name, 0, 33, '...');
            } else {
                $comment2['parent_name'] = "a model";
            }

            $comment2['type'] = 'model';
            $comment2['id'] = $comment['id'];
            $comment2['username'] = $user->username ?? null;
            $comment2['userid'] = $user->id ?? null;
            $comment2['content'] = mb_strimwidth($comment['comment'], 0, 33, '...');
            $comment2['parent'] = $parent;
            $comment2['date'] = time_ago(date("D, M d, Y", (int)$comment['date']));

            $creation_replies[] = $comment2;
        }

        $sql = "SELECT id, userid, deleted_at, content, parent, timestamp FROM messages WHERE userid = $profileid AND deleted_at IS NULL AND parent != 0 ORDER BY timestamp DESC LIMIT $limit OFFSET $offset;";
        $result = $conn_forum->query($sql);

        while ($reply = $result->fetch_assoc()) {
            $parent = $reply['parent'];
            $reply2 = [];

            $stmt = $conn_forum->prepare("SELECT title FROM messages WHERE id = ? AND deleted_at IS NULL");
            $stmt->bind_param("i", $parent);
            $stmt->execute();

            $result_parent = $stmt->get_result();
            if($result_parent->num_rows != 0) {
                $parent_name = $result_parent->fetch_assoc()['title'];
                $parent_name = mb_strimwidth($parent_name, 0, 33, '...');
            } else {
                $parent_name = "a forum topic";
            }

            $reply2['type'] = 'forum';
            $reply2['id'] = $reply['id'];
            $reply2['username'] = $user->username ?? null;
            $reply2['userid'] = $user->id ?? null;
            $reply2['content'] = mb_strimwidth($reply['content'], 0, 33, '...');
            $reply2['parent'] = $parent;
            $reply2['parent_name'] = $parent_name;
            $reply2['date'] = time_ago(date("D, M d, Y", strtotime($reply['timestamp'])));

            $forum_replies[] = $reply2;
        }

        $arr = array([
            'success' => true,
            'creation_replies' => $creation_replies,
            'forum_replies' => $forum_replies,
        ]);

        return $arr;
    }

    public function returnForums($userid, $page) {
        $conn_forum = Database::get(DB_NAME3);

        $limit = 12;
        $offset = ($page - 1) * $limit;

        $posts = [];

        if ($conn_forum->connect_error) {
            echo $conn_forum->connect_error;
            exit;
        }

        if($page < 1) {
            return ['success' => false, 'error' => 'Invalid page number'];
        }

        if (!loggedin()) {
            return ['success' => false, 'error' => "Sign in to forum posts of a user"];
        }

        $user = User::getUser($userid);

        if(!$user || User::isDeleted($userid)) {
            return ['success' => false, 'error' => "What user is this?"];
        }

        if(!User::isMe($userid) && !User::isFollowing($userid) && User::isPrivate($userid)) {
            return ['success' => false, 'error' => "This profile is private."];
        }

        $profile_stmt = $conn_forum->prepare("SELECT * FROM messages WHERE userid = ? AND (parent = 0 OR parent IS NULL) ORDER BY id DESC LIMIT $limit OFFSET $offset;");
        $profile_stmt->bind_param("s", $userid);
        $profile_stmt->execute();
        $result = $profile_stmt->get_result();

        while ($p = $result->fetch_assoc()) {
            $p2 = [];

            $p2['id'] = $p['id'];
            $p2['username'] = $user->username ?? null;
            $p2['userid'] = $user->id ?? null;
            $p2['title'] = mb_strimwidth($p['title'], 0, 33, '...');
            $p2['date'] = time_ago(date("D, M d, Y", strtotime($p['timestamp'])));

            $posts[] = $p2;
        }

        return ['success' => true, 'posts' => $posts];
    }
}

if(isset($_GET['getUserBuilds'])) {
    header('Content-Type: application/json');

    if(!isset($_GET['userid'])) {
        echo "User id is missing";
        exit;
    }

    $page = $_GET['page'] ?? 0;
    $UserContent = new UserContent();
    $creations = $UserContent->returnModels($_GET['userid'], $page);

    echo json_encode($creations);
    exit;
}

if(isset($_GET['getUserForums'])) {
    header('Content-Type: application/json');

    if(!isset($_GET['userid'])) {
        echo "User id is missing";
        exit;
    }

    $page = $_GET['page'] ?? 0;
    $UserContent = new UserContent();
    $posts = $UserContent->returnForums($_GET['userid'], $page);

    echo json_encode($posts);
    exit;
}

if(isset($_GET['getUserLiked'])){
    if(!isset($_GET['userid'])) {
        echo "User id is missing";
        exit;
    }

    $page = $_GET['page'] ?? 0;
    $UserContent = new UserContent();
    $result = $UserContent->returnLikedModels($_GET['userid'], $page);
    echo json_encode($result);
    exit;
}

if(isset($_GET['getUserComments'])) {
    header('Content-Type: application/json');

    if(!isset($_GET['userid'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid user id']);
        exit;
    }

    $page = $_GET['page'] ?? 0;
    $UserContent = new UserContent();
    $comments = $UserContent->returnComments($_GET['userid'], $page);
    $comments_arr = [];
    $json = null;

    if(isset($comments['success']) && $comments['success'] !== true) {
        $json = json_encode($comments);
    } else {
        foreach($comments[0]['creation_replies'] as $comment) {
            $comments_arr[] = $comment;
        }

        foreach($comments[0]['forum_replies'] as $comment) {
            $comments_arr[] = $comment;
        }

        $json = json_encode(['success' => true, 'comments' => $comments_arr]);
    }

    echo $json;
    exit;
}
?>