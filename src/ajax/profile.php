<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/auth.php';
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
    $current_user_id = $current_user->id;

    //selects user follow row(s), selects users actual account, fliters invalid accounts
    $query = "
        SELECT DISTINCT u.id, u.picture, u.username 
        FROM follow f1
        INNER JOIN follow f2 ON f1.userid = f2.profileid
        INNER JOIN users u ON f1.userid = u.id
        LEFT JOIN blacklist blist ON u.username = blist.value AND blist.type = 'username'
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

if(isset($_GET['who_you_follow'])) {
    header('Content-Type: application/json');
    $followed_by = array();
    $sql = "SELECT DISTINCT profileid FROM follow WHERE userid = '$id' ORDER BY id DESC";
    $result = $conn2->query($sql);

    while ($row = $result->fetch_assoc()) {
        $profileid = $row['profileid'];
        $sql2 = "SELECT * FROM users WHERE id = '$profileid'";
        $result2 = $conn2->query($sql2);
        if($result2->num_rows > 0) {
            $row2 = $result2->fetch_assoc();
            $followed_by[] = array(
                'username' => htmlspecialchars($row2['username']),
                'profileid' => $profileid, 
                'random' => uniqid()
            );
            $result2->free();
        }
    }
    $result->free();
    header("HTTP/1.0 200 OK");
    echo json_encode($followed_by);
    exit;
}

if(isset($_GET['who_follows_you'])) {
    header('Content-Type: application/json');
    $followed_by = array();
    $sql = "SELECT DISTINCT userid FROM follow WHERE profileid = '$id' ORDER BY id DESC";
    $result3 = $conn2->query($sql);

    while ($row3 = $result3->fetch_assoc()) {
        $userid = $row3['userid'];
        $sql2 = "SELECT * FROM users WHERE id = '$userid'";
        $result4 = $conn2->query($sql2);
        if($result4->num_rows > 0) {
            $row4 = $result4->fetch_assoc();
            $followed_by[] = array(
                'username' => htmlspecialchars($row4['username']),
                'userid' => $userid
            );
            $result4->free();
        }
    }
    $result3->free();
    header("HTTP/1.0 200 OK");
    echo json_encode($followed_by);
    $conn2->close();
    exit;
}

function user_blocks($profileid, $userid, $username, $db) {
    $you = false;
    $them = false;
    $message = false;

    $block_result = $db->query("SELECT * FROM user_blocks WHERE (userid = '$userid' AND profileid = '$profileid') OR (userid = '$profileid' AND profileid = '$userid')");

    if ($block_result && $block_result->num_rows > 0) {
        while ($row = $block_result->fetch_assoc()) {
            if ($row['userid'] == $userid && $row['profileid'] == $profileid) {
                $you = true;
            } elseif ($row['userid'] == $profileid && $row['profileid'] == $userid) {
                $them = true;
            }
        }

        if ($you && $them) {
            $message = "You blocked " . $username . ", and they blocked you.";
        } elseif ($you) {
            $message = "You blocked " . $username;
        } elseif ($them) {
            $message = "You're blocked from " . $username;
        }

        return $message;
    }
}

function fetch_profile(mixed $profile_id, mixed $csrf, bool $use_name = true) {
    global $token, $current_user;
    $userid = $current_user->id ?? null;

    require_once $_SERVER['DOCUMENT_ROOT'] . '/com/bbcode.php';
    $bbcode = new BBCode();

    if (empty($csrf) || $csrf != $_SESSION['csrf']) {
        return [
            "message" => 'No CSRF token provided, or it is invalid!',
            "error" => 'INVALID_CSRF'
        ];
    }

    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        exit($conn->connect_error);
    }

    function delete_mini_message() {
        header("HTTP/1.0 403 Forbidden");
        return [
            "message" => 'User not found.',
            "error" => 'USR_NOT_FND'
        ];
    }

    if($use_name === true) {
        $profile_name = $profile_id;
        $sql = "SELECT id FROM users WHERE username = ? AND deactive IS NULL";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $profile_name);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $profile_name = null;
            $profile_id = (int)$row['id'] ?? null;
        } else {
            return delete_mini_message();
        }

        $stmt->close();
    } else {
        $profile_id = (int)$profile_id;
    }

    $usero = User::getUser($profile_id);
    $email = $usero->email ?? null;
    $username = $usero->username ?? null;

    if (!isset($usero) || User::isDeleted($profile_id) || AccountManager::isBanned($conn, $email, $username)) {
        return delete_mini_message();
    }
    
    $is_blocking = false;
    $is_following = false;
    if(loggedin()) {
        $blocks = user_blocks($profile_id, $userid, $usero->username , $conn);
        if($blocks) {
            header("HTTP/1.0 403 Forbidden");
            return [
                "message" => htmlspecialchars($blocks),
                "error" => 'ACC_BLOCKED_USR'
            ];
        }

        $stmt = $conn->prepare("SELECT COUNT(*) as following FROM follow WHERE userid = ? AND profileid = ?");
    	$stmt->bind_param("ss", $userid, $profile_id);
    	$stmt->execute();
    	$is_following = $stmt->get_result()->fetch_assoc()['following'];
    	$stmt->close();

        if($current_user->admin == '1') {
            $adm_email = htmlspecialchars($email);
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
        'userid' => $profile_id,
        'username' => htmlspecialchars($username),
        'admin' => (string)$usero->admin,
        'description' => isset($usero->description) ? $bbcode->toHTML($usero->description) : '', 
        'twitter' => htmlspecialchars($usero->twitter),
        'bsky' => htmlspecialchars($usero->bsky),
        'age' => htmlspecialchars($usero->age),
        'picture' => htmlspecialchars($usero->picture),
        'model_count' => (int)$model_count,
        'followers' => (int)$followers,
        'following' => (int)$following,
        'views' => (int)$views,
        'likes' => (int)$likes,
        'is_following' => (bool)$is_following,
        'is_blocking' => $is_blocking,
        'message' => $message,
        'email' => $adm_email ?? ''
    ];

    return $data;
}

if(isset($_GET['user'])) {
    header('Content-Type: application/json');
    $profile_name = $_GET['user'];
    $data = fetch_profile($profile_name, $_SESSION['csrf']);
    echo json_encode($data);
    exit;
}

class UserContent {
    public string $creation;
    public string $userid;

    public function returnModels($userid, $page) {
        $creation_conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

        if ($creation_conn->connect_error) {
            exit("Connection failed: " . $creation_conn->connect_error);
        }

        $limit = 9;
        $offset = ($page - 1) * $limit;

        $stmt = $creation_conn->prepare("SELECT * FROM model WHERE user = ? AND visibility = 'public' AND removed = 0 ORDER BY date DESC LIMIT $limit OFFSET $offset;");
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $result = $stmt->get_result();

        $creations = [];
        while ($creation = $result->fetch_assoc()) {
            $user = User::getUser($creation['user']);
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
        return $creations;
    }

    public function returnLikedModels($userid) {
        $creation_conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

		if ($creation_conn->connect_error) {
            return ['success' => false, 'error' => "Database connection failed"];
        }

        if (!loggedin()) {
            http_response_code(401);
            return ['success' => false, 'error' => "Sign in to view liked models of a user"];
        }

        $stmt = $creation_conn->prepare('SELECT * FROM votes WHERE user = ?');
        $stmt->bind_param('i', $userid);
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
                    $model_user = $row2['user'];

                    $user = User::getUser($model_user);
                    $row2['username'] = $user->username ?? null;
                    $row2['userid'] = $user->id ?? null;
                    
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

        // comments and replies
        $profileid = $userid; // whatever
        $profile_stmt = $conn_creations->prepare("SELECT * FROM comments WHERE user = ? ORDER BY id DESC LIMIT $limit OFFSET $offset;");
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

            $user = User::getUser($userid);

            $comment2['type'] = 'model';
            $comment2['id'] = $comment['id'];
            $comment2['username'] = $user->username ?? null;
            $comment2['userid'] = $user->id ?? null;
            $comment2['content'] = mb_strimwidth($comment['comment'], 0, 33, '...');
            $comment2['parent'] = $parent;
            $comment2['date'] = time_ago(date("D, M d, Y", (int)$comment['date']));

            $creation_replies[] = $comment2;
        }

        $sql = "SELECT * FROM messages WHERE userid = $profileid AND parent != 0 ORDER BY timestamp DESC LIMIT $limit OFFSET $offset;";
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

            $user = User::getUser($userid);
            
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
            'creation_replies' => $creation_replies,
            'forum_replies' => $forum_replies,
        ]);

        $conn_forum->close();
        $conn_creations->close();

        return $arr;
    }
}

if(isset($_GET['getUserBuilds'])) {
    header('Content-Type: application/json');

    if(!isset($_GET['userid'])) {
        echo "User id is missing";
        exit;
    }

    $page = $_GET['page'];
    if(!isset($page) || $page === null || $page < 1) {
        $page = 1;
    }
    
    $UserContent = new UserContent();
    $creations = $UserContent->returnModels($_GET['userid'], $page);

    echo json_encode(['success' => true, 'creations' => $creations]);
    exit;
}

if(isset($_GET['getUserLiked'])){
    if(!isset($_GET['userid'])) {
        echo "User id is missing";
        exit;
    }

    $UserContent = new UserContent();
    $result = $UserContent->returnLikedModels($_GET['userid']);
    echo json_encode($result);
    exit;
}

if(isset($_GET['getUserComments'])) {
    header('Content-Type: application/json');

    if(!isset($_GET['userid'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid user id']);
        exit;
    }

    $page = $_GET['page'];
    if(!isset($page) || $page === null || $page < 1) {
        echo json_encode(['success' => false, 'error' => 'Invalid page number']);
        exit;
    }
    
    $UserContent = new UserContent();
    $comments = $UserContent->returnComments($_GET['userid'], $page);

    $user = User::getUser($_GET['userid']);

    $comments_arr = [];

    foreach($comments[0]['creation_replies'] as $comment) {
        $comments_arr[] = $comment;
    }

    foreach($comments[0]['forum_replies'] as $comment) {
        $comments_arr[] = $comment;
    }

    echo json_encode(['success' => true, 'comments' => $comments_arr]);
    exit;
}
?>