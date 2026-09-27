<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/notifications.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/com/bbcode.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';
$bbcode = new BBCode;

if (isset($_GET['followed_by'])) {
    header('Content-Type: application/json');

    if (!loggedin()) {
        http_response_code(401);
        echo json_encode(['error' => 'not logged in', 'success' => false]);
        exit;
    }

    $profile_id = $_GET['followed_by'];
    $current_user_id = $current_user->id ?? 0;

    if(User::isDeleted($profile_id) || (User::isPrivate($profile_id) && !User::isFollowing($profile_id))) {
        http_response_code(404);
        echo json_encode(['error' => 'invalid account id', 'success' => false]);
        exit;
    }

    //selects user follow row(s), selects users actual account, fliters invalid accounts
    $query = "
        SELECT DISTINCT u.id, u.picture, u.username, u.email
        FROM follow f1
        INNER JOIN follow f2 ON f1.userid = f2.profileid
        INNER JOIN users u ON f1.userid = u.id
        LEFT JOIN blacklist blist ON (u.username = blist.value AND blist.type = 'username') OR (u.email = blist.value AND blist.type = 'email')
        WHERE f1.profileid = ? 
          AND f2.userid = ?
          AND blist.value IS NULL
          AND u.deactive IS NULL
        ORDER BY u.id DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $profile_id, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $followed_by = [];
    while ($row = $result->fetch_assoc()) {
        $followed_by[] = [
            'url'      => '/user/' . urlencode($row['id']) . '?from=' . urlencode($profile_id),
            'userid'   => $row['id'], 
            'pfp'      => $row['picture'],
            'username' => htmlspecialchars($row['username'])
        ];
    }
    $stmt->close();

    http_response_code(200);
    echo json_encode($followed_by);
    exit;
}

$conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
if ($conn2->connect_error) {
    exit($conn2->connect_error);
}

function user_blocks(int $profileid, mixed $db) {
    global $current_user;

    if(!loggedin()) {
        return;
    }

    $you = false;
    $them = false;
    $type = null;
    $message = false;
    $message_arr = [];
    $userid = $current_user->id ?? 0; //clarification: this is your user id profileid is their userid

    $block_result = $db->query("SELECT * FROM user_blocks WHERE (userid = '$userid' AND profileid = '$profileid') OR (userid = '$profileid' AND profileid = '$userid')");

    if ($block_result && $block_result->num_rows > 0) {
        $block_usero = User::getUser($profileid);

        if(!$block_usero) {
            return;
        }

        $username = $block_usero->username ?? '[user]';

        while ($row = $block_result->fetch_assoc()) {
            if ($row['userid'] == $userid && $row['profileid'] == $profileid) {
                $you = true;
            } elseif ($row['userid'] == $profileid && $row['profileid'] == $userid) {
                $them = true;
            }
        }

        if ($you && $them) {
            $message = "You blocked " . $username . ", and they blocked you.";
            $type = 'both';
        } elseif ($you) {
            $message = "You blocked " . $username;
            $type = 'you';
        } elseif ($them) {
            $message = "You're blocked from " . $username;
            $type = 'them';
        }

        $message_arr = array(
            'message' => $message,
            'type' => $type,
        );

        return $message_arr;
    }
}

function fetch_profile(mixed $profile_id, mixed $csrf, bool $use_name = true) {
    global $current_user;
    $userid = $current_user->id ?? null;
    $bbcode = new BBCode();

    if (empty($csrf) || $csrf != $_SESSION['csrf']) {
        return [
            "success" => false,
            "code" => 'csrf_missing',
            "title" => 'Something something tech-related',
            "message" => 'No CSRF (cross-site-request-forgery) token provided, or it is invalid!',
        ];
    }

    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        exit($conn->connect_error);
    }

    if($use_name === true) {
        $usero = User::getUserByName($profile_id);
        $profile_id = $usero->id;
    } else {
        $profile_id = $profile_id;
        $usero = User::getUser($profile_id);
    }

    if (!isset($usero) || User::isDeleted($profile_id)) {
        http_response_code(404);
        return [
            "success" => false,
            "code" => 'not_found',
            "title" => '404 Not Found',
            "message" => 'User not found. Probably easier to find a flat stud 1x1 in your old unsorted bin of parts.',
        ];
    }

    $banrow = User::isBannedByID($profile_id);

    if ($banrow) {
        http_response_code(403);
        $until = isset($banrow['ignore_at']) ? ('until ' . date("d M Y", strtotime($banrow['ignore_at']))) : 'indefinitely';

        $arr = [
            "success" => false,
            "picture" => $usero->picture_small,
            "code" => 'account_banned',
            "title" => 'Account suspended',
            "message" => 'This account has been suspended ' . $until . '.<br />When an account is suspended, the owner can\'t sign in or interact with content. However, they can appeal the ban.',
        ];

        if(loggedin() && $current_user->admin) {
            $arr['email'] = $usero->email;
        }

        return $arr;
    }

    $bsky = $usero->bsky ?? null;
    $is_blocking = false;
    $is_following = false;
    $is_me = User::isMe($userid);
    $is_private = User::isPrivate($profile_id);

    if(!$is_me && !$is_following && $is_private) {
        http_response_code(403);

        return [
            "success" => false,
            "picture" => $usero->picture_small,
            "code" => 'profile_private',
            "title" => 'Profile private',
            "message" => 'The owner of this account has privated their profile.<br />When an account is private, other users can\'t view the profile unless they are following said user, or said user is following them.',
        ];
    }

    if(loggedin()) {
        $blocks = User::isBlocking($profile_id);

        if($blocks && is_array($blocks)) {
            if($blocks['type'] !== 'you') {
                header("HTTP/1.0 403 Forbidden");
                return [
                    "success" => false,
                    "picture" => $usero->picture_small,
                    "code" => 'user_blocking',
                    "title" => 'User blocked you',
                    "message" => htmlspecialchars($blocks['message']),
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

        if($current_user->admin == '1') {
            $adm_email = isset($usero->email) ? htmlspecialchars($usero->email) : '';
        }
    }

    $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
    if ($conn2->connect_error) {
        exit($conn2->connect_error);
    }

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

    $message = null;
    $data = [
        'success' => true,
        'userid' => $profile_id,
        'username' => htmlspecialchars($usero->username),
        'admin' => (string)$usero->admin,
        'description' => isset($usero->description) ? $bbcode->toHTML($usero->description) : '', 
        'twitter' => isset($usero->twitter) ? htmlspecialchars($usero->twitter) : '',
        'bsky' => $bsky,
        'age' => isset($usero->age) ? htmlspecialchars($usero->age) : '',
        'picture' => htmlspecialchars($usero->picture_small ?? $usero->picture),
        'model_count' => $model_count,
        'followers' => $followers,
        'following' => $following,
        'views' => $views,
        'likes' => $likes,
        'is_following' => (bool)$is_following,
        'is_blocking' => (bool)$is_blocking,
        'is_private' => (bool)$is_private,
        'message' => $message,
        'email' => $adm_email ?? null
    ];

    return $data;
}

//replacement for the old one
if(isset($_GET['profile']) && (isset($_GET['actorId']) || isset($_GET['actorName']))) {
    header('Content-Type: application/json');

    $profile = $_GET['actorId'] ?? $_GET['actorName'] ?? null;
    $use_name = isset($_GET['actorName']) ? true : false;

    $data = fetch_profile($profile, $_SESSION['csrf'], $use_name);
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
        $this->conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
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
            $notification->notify_subscribers('profile', $profile_id, $this->userid);

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
                $queries = [["UPDATE php_sessions SET active = 0 WHERE userid = ?", "i", $profile_id], ["UPDATE sessions SET timestamp = 0 WHERE user = ?", "i", $profile_id]];

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

        if($this->current_user->verify_token !== NULL) {
            $error = "User account is not verified";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        $email = hash('sha256', strtolower(trim($email)));

        $sql_follow = "SELECT id FROM blacklist WHERE (value = ? AND type = 'userid') OR (value = ? AND type = 'email') LIMIT 1";
        $stmt_follow = $this->conn->prepare($sql_follow);
        $stmt_follow->bind_param("ss", $profile_id, $email);
        $stmt_follow->execute();
        $result = $stmt_follow->get_result();

        if($result->num_rows <= 0) {
            $error = "User account is not blacklisted";
            return [
                'success' => false,
                'message' => $error,
                'title' => $error_title
            ];
        }

        if(!isset($error)) {
            $sql_follow = "DELETE FROM blacklist WHERE (type = 'userid' AND value = ?) OR (type = 'email' AND value = ?)";
            $stmt_follow = $this->conn->prepare($sql_follow);
            $stmt_follow->bind_param("ss", $profile_id, $email);
            $result = $stmt_follow->execute();

            if ($result) {
                $stmt_follow->close();

                $message = "Unblacklisted this user with success";
                return [
                    'success' => true,
                    'message' => $message,
                    'title' => 'Success!'
                ];
            } else {
                $error = "An error occurred while unblacklisting this user";
                return [
                    'success' => false,
                    'message' => $error,
                    'title' => $error_title
                ];
            }
        }
    }
}

class UserContent {
    public string $creation;
    public string $userid;

    public function returnModels($userid, $page) {
        $creation_conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

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

        $stmt = $creation_conn->prepare("SELECT * FROM model WHERE user = ? AND visibility = 'public' AND removed = 0 ORDER BY date DESC LIMIT $limit OFFSET $offset");
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
        $creation_conn->close();
        return ['success' => true, 'creations' => $creations];
    }

    public function returnLikedModels($userid, $page) {
        $creation_conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

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

            $stmt2 = $creation_conn->prepare("SELECT * FROM model WHERE id IN (" . implode(',', $liked) . ") AND visibility = 'public' AND removed = 0 ORDER BY date DESC");
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
        $conn_creations = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
        $conn_forum = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);

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
        $profile_stmt = $conn_creations->prepare("SELECT * FROM comments WHERE hidden = 0 AND user = ? ORDER BY id DESC LIMIT $limit OFFSET $offset;");
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

        $sql = "SELECT * FROM messages WHERE userid = $profileid AND deleted_at IS NULL AND parent != 0 ORDER BY timestamp DESC LIMIT $limit OFFSET $offset;";
        $result = $conn_forum->query($sql);

        while ($reply = $result->fetch_assoc()) {
            $parent = $reply['parent'];
            $reply2 = [];

            $stmt = $conn_forum->prepare("SELECT title FROM messages WHERE id = ?");
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

        $conn_forum->close();
        $conn_creations->close();

        return $arr;
    }

    public function returnForums($userid, $page) {
        $conn_forum = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);

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

        $conn_forum->close();
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
    /*if(!isset($page) || $page === null || $page < 1) {
        echo json_encode(['success' => false, 'error' => 'Invalid page number']);
        exit;
    }*/
    
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