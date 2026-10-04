<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/notifications.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/numbers.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/bbcode.php';
$bbcode = new BBCode;

$conn = Database::get(DB_NAME2);
$conn2 = Database::get(DB_NAME);

if(isset($_GET['storage'])) {
    header('Content-Type: application/json');

    if(!isset($_GET['user'])) {
        if (!loggedin()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => ErrorRegistry::get('auth', 'unauthed')]);
            exit;
        }

        $user = $current_user->id ?? 0;
        $name_user = $current_user->username ?? null;
    } else {
        $user = (int)$_GET['user'];

        $query = "SELECT username FROM users WHERE id = ? AND deactive IS NULL";
        $stmt = $conn2->prepare($query);
        $stmt->bind_param("i", $user);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows > 0) {
        	$name_user = $result->fetch_assoc()['username'];
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => ErrorRegistry::get('user', 'not_found')]);
            exit;
        }
    }

    $stmt = $conn->prepare("SELECT SUM(size) as total_used FROM model WHERE user = ?");
    $stmt->bind_param("i", $user);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total_used'] ?? 0;
    $stmt->close();

    echo json_encode(['success' => true, 'userid' => $user, 'user' => $name_user, 'total' => (int)$total]);
    exit;
}

$CREATION_SAVE_STRINGS = [
	'NO_LOGIN' => "Please login to save creations.",
	'REQUEST_EMPTY' => "Request is empty.",
	'CREATION_FORMAT_INVALID' => "Invalid creation format.",
	'CREATION_INVALID' => 'Failed to look for creation.',
	'INVALID_VISIBILITY' => "Visibility must be one of: public, unlisted, private.",
	'STORAGE_INVALID' => "Failed to check storage usage.",
	'STORAGE_MAX' => "Storage limit of " . Numbers::filesize(MODEL_STORAGE_LIMIT) . " was reached.",
	'CREATION_SAVE_FAIL' => "Failed to save creation.",
	'CREATION_UPDATE_FAIL' => "Failed to update creation.",
	'THUMBNAIL_INVALID' => "Thumbnail is not a valid image.",
	'THUMBNAIL_BAD_ENCODING' => "Thumbnail must be encoded in Webp or PNG.",
	'THUMBNAIL_SAVE_FAIL' => "Failed to save thumbnail.",
];

if (isset($_POST['save_build_v2'])) {
    header('Content-Type: application/json');

    if (!loggedin() || !isset($current_user)) {
        http_response_code(401);
        echo json_encode(['error' => ErrorRegistry::get('auth', 'unauthed')]);
        exit;
    }

    $user = $current_user->id ?? 0;

    if (!isset($_POST['creation']) || empty($_POST['creation'])) {
        http_response_code(400);
        echo json_encode(['error' => ErrorRegistry::get('creation', 'format_invalid')]);
        exit;
    }

    $desc = $_POST['desc'] ?: '';
    $name = $_POST['name'] ?: "Untitled Creation";
    $modelJson = $_POST['creation'] ?: null;
    $visible = $_POST['visibility'] ?: null;
    $can_edit = $_POST['can_edit'] ?: 0;

    $decoded_json = json_decode($modelJson, true);

    if ($decoded_json === null && json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => ErrorRegistry::get('creation', 'format_invalid')]);
        exit;
    }

    if (!$visible || !in_array($visible, ['public', 'unlisted', 'private'], true)) {
        http_response_code(400);
        echo json_encode(['error' => ErrorRegistry::get('creation', 'visibility_invalid')]);
        exit;
    }

    $updating = false;
    $build_id = null;
    $existing = null;
	
	if($visible === 'public' && $current_user->verify_token !== null) {
		http_response_code(400);
        echo json_encode(['error' => ErrorRegistry::get('creation', 'visibility_unverified')]);
        exit;
	}

    if (isset($_POST['build_id']) && $_POST['build_id'] !== null && $_POST['build_id'] !== "null") {
        $build_id = filter_var($_POST['build_id'], FILTER_VALIDATE_INT);

        if ($build_id === false) {
            http_response_code(400);
            echo json_encode(['error' => ErrorRegistry::get('creation', 'not_found')]);
            exit;
        }

        $stmt = $conn->prepare("SELECT id, user, model, screenshot, size FROM model WHERE id = ? AND user = ? LIMIT 1");

        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => ErrorRegistry::get('creation', 'not_found')]);
            exit;
        }

        $stmt->bind_param("ii", $build_id, $user);
        $stmt->execute();

        $result = $stmt->get_result();
        $existing = $result->fetch_assoc();
        $stmt->close();

        if (!$existing) {
            $updating = false; //so users can save other peoples creations
        } else {
            $updating = true;
        }
    }

    if ($updating) {
        $file_name = ".." . $existing['model'];
        $db_file_name = $existing['model'];

        $old_file_size = (int)$existing['size'];

        if (!empty($existing['screenshot'])) {
            $screenshot_path = ".." . $existing['screenshot'];
            $db_screenshot = $existing['screenshot'];
        } else {
            $screenshot_path = null;
            $db_screenshot = null;
        }
    } else {
        $file_id = bin2hex(random_bytes(16));

        $file_name = "../cre/" . $file_id . ".json";
        $db_file_name = "/cre/" . $file_id . ".json";

        $screenshot_path = "../cre/" . $file_id . ".webp";
        $db_screenshot = "/cre/" . $file_id . ".webp";

        $old_file_size = 0;
    }

    $new_file_size = strlen($modelJson);
    $stmt = $conn->prepare("SELECT COALESCE(SUM(size), 0) AS total_used FROM model WHERE user = ?");

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => ErrorRegistry::get('creation', 'storage_invalid')]);
        exit;
    }

    $stmt->bind_param("i", $user);
    $stmt->execute();

    $result = $stmt->get_result();
    $total = (int)$result->fetch_assoc()['total_used'];

    $stmt->close();

    $new_total = $total - $old_file_size + $new_file_size;

    if ($new_total > MODEL_STORAGE_LIMIT) {
        http_response_code(413);
        echo json_encode(['error' => ErrorRegistry::get('creation', 'storage_max')]);
        exit;
    }

    if (file_put_contents($file_name, $modelJson) === false) {
        http_response_code(500);
        echo json_encode(['error' => ErrorRegistry::get('creation', 'save_fail')]);
        exit;
    }

    $db_file_size = filesize($file_name);

    if (!empty($_POST['screenshot'])) {
        $screenshot_data = $_POST['screenshot'];
        if (strpos($screenshot_data, 'data:image/png;base64,') === 0) {
            $base64_str = substr($screenshot_data, strlen('data:image/png;base64,'));
        } elseif (strpos($screenshot_data, 'data:image/webp;base64,') === 0) {
            $base64_str = substr($screenshot_data, strlen('data:image/webp;base64,'));
        } else {
            http_response_code(400);
            echo json_encode(['error' => ErrorRegistry::get('creation', 'thumbnail_encoding_bad')]);
            exit;
        }

        $decoded_image = base64_decode($base64_str, true);

        if ($decoded_image === false) {
            http_response_code(400);
            echo json_encode(['error' => ErrorRegistry::get('creation', 'thumbnail_encoding_bad')]);
            exit;
        }

        $image = imagecreatefromstring($decoded_image);

        if (!$image) {
            http_response_code(400);
            echo json_encode(['error' => ErrorRegistry::get('creation', 'thumbnail_encoding_bad')]);
            exit;
        }

        if (!$screenshot_path) {
            $file_id = bin2hex(random_bytes(16));

            $screenshot_path = "../cre/" . $file_id . ".webp";
            $db_screenshot = "/cre/" . $file_id . ".webp";
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);
        $saved = imagewebp($image, $screenshot_path, 80);
        imagedestroy($image);

        if (!$saved) {
            http_response_code(500);
            echo json_encode(['error' => ErrorRegistry::get('creation', 'thumbnail_save_fail')]);
            exit;
        }
    }

    $date = date("Y-m-d H:i:s");

    if ($updating) {
        $stmt = $conn->prepare("UPDATE model SET model = ?, description = ?, name = ?, date = ?, size = ?, screenshot = ?, visibility = ?, can_edit = ? WHERE id = ? AND user = ?");

        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => ErrorRegistry::get('creation', 'update_fail')]);
            exit;
        }

        $stmt->bind_param(
            "ssssissiii",
            $db_file_name,
            $desc,
            $name,
            $date,
            $db_file_size,
            $db_screenshot,
            $visible,
            $can_edit,
            $build_id,
            $user
        );

        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(['error' => ErrorRegistry::get('creation', 'update_fail')]);
            exit;
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO model (user, model, description, name, date, size, screenshot, visibility, can_edit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => ErrorRegistry::get('creation', 'save_fail')]);
            exit;
        }

        $stmt->bind_param(
            "issssissi",
            $user,
            $db_file_name,
            $desc,
            $name,
            $date,
            $db_file_size,
            $db_screenshot,
            $visible,
            $can_edit
        );

        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(['error' => ErrorRegistry::get('creation', 'save_fail')]);
            exit;
        }

        $build_id = $conn->insert_id;
    }

    $url_view = $visible !== 'private' ? '/creation?id=' . $build_id : null;
    $url_modeler = $can_edit ? '/modeler?build_id=' . $build_id : null;
    $_SESSION['last_request'] = time();
    $stmt->close();

	$notifications = new Notifications($conn2);
    $notifications->subscribe($current_user->id, 'creation_fav', $build_id);
	$notifications->subscribe($current_user->id, 'comment', $build_id);

    echo json_encode([
        'success' => $updating ? "Your creation was updated successfully!" : "Your creation was saved successfully!",
        'creation' => [
            'id' => $build_id,
            'url' => $url_view,
            'name' => $name,
            'url_edit' => '/acc/creations?edit=' . $build_id,
            'url_modeler' => $url_modeler,
            'visibility' => $visible,
            'can_edit' => $can_edit,
        ],
        'user' => [
            'id' => $current_user->id,
            'name' => $current_user->username,
        ],
    ]);
    exit;
}

function fetch_build($model_id, $csrf) {
    global $current_user;
    global $conn;
    global $conn2;

    require_once $_SERVER['DOCUMENT_ROOT'] . '/com/bbcode.php';
    $bbcode = new BBCode();

    if (empty($csrf) || $csrf != $_SESSION['csrf']) {
        return json_encode(["message" => 'No CSRF token provided, or it is invalid!',]);
    }

    if ($conn->connect_error) {
        exit($conn->connect_error);
    }

    if ($conn2->connect_error) {
        exit($conn2->connect_error);
    }

    if (!is_numeric($model_id)){
        return json_encode(["message" => ErrorRegistry::get('creation', 'bad_id')]);
    }

    $stmt = $conn->prepare("SELECT * FROM model WHERE id = ?");
    $stmt->bind_param("i", $model_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row2 = $result->fetch_assoc();

    if (!$row2) {
        http_response_code(404);
        return json_encode(["message" => ErrorRegistry::get('creation', 'not_found')]);
    }

    $id = loggedin() ? ($current_user->id ?? 0) : 0;
    $is_admin = (isset($current_user->admin) && $current_user->admin === true);
    $is_owner = (loggedin() && isset($current_user) && trim($current_user->id) === trim($row2['user']));

    if (!$is_admin) {
        if ($row2['removed'] === 1) {
            http_response_code(404);
            return json_encode(["message" => ErrorRegistry::get('creation', 'not_found')]);
        }

        if ($row2['visibility'] === 'private' && !$is_owner) {
            http_response_code(404);
            return json_encode(["message" => ErrorRegistry::get('creation', 'not_found')]);
        }
    }

    if($is_admin || $is_owner) {
        $can_edit = true;
    } else {
        $can_edit = $row2['can_edit'];
    }

    $userid = $row2['user'];
    $views = $row2['views'];
    $votes = $row2['likes'];
    $decoded_description = $bbcode->toHTML($row2['description'], true, true);
    $name = $bbcode->toHTML($row2['name'] ?? 'Untited creation', true, true);

    $row = User::getUser($userid);
    $user_name = $row->username ?? '';
    $user_image = $row->picture_small ?? null;

    if (!isset($name) || empty($name)) {
        $name = $user_name . "'s creation";
    }

    $did_track = false;
    if(loggedin()) {
        $cookie = new Cookie($id);
        if($cookie->analytics_creation($conn2, $userid, $name)) {
            $did_track = true;
        }
    }

    $stmt = $conn2->prepare("SELECT COUNT(*) as following FROM follow WHERE profileid = ?");
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $followers = $stmt->get_result()->fetch_assoc()['following'];
    $stmt->close();

    $notifications = new Notifications($conn2);

    if(loggedin()) {
        $sql = "SELECT * FROM user_blocks WHERE userid = ? AND profileid = ? LIMIT 1";
        $stmt = $conn2->prepare($sql);
        $stmt->bind_param("ii", $userid, $id);
        $stmt->execute();
        $result4 = $stmt->get_result();
        $stmt->close();

        if ($result4->num_rows > 0) {
            http_response_code(403);
            return json_encode(["message" => ErrorRegistry::get('creation', 'user_blocked')]);
        }

        $find_votes = $conn->query("SELECT id FROM votes WHERE user = '$id' AND creation = '$model_id' LIMIT 1");
        if ($find_votes->num_rows > 0) {
            $voted = true;
        } else {
            $voted = false;
        }

        $is_subbed = $notifications->is_subscriber('comment', $model_id, $id) || $notifications->is_subscriber('creation_fav', $model_id, $id);
		$is_subbed_comment = $notifications->is_subscriber('comment', $model_id, $id);
		$is_subbed_fav = $notifications->is_subscriber('creation_fav', $model_id, $id);
    } else {
        $voted = false;
    }

    $saved_prefs = Cookie::controls();
    if(in_array('site-prefs', $saved_prefs)) {
        if (!isset($_SESSION['viewed_creation_ids'])) {
            $_SESSION['viewed_creation_ids'] = [];
        }

        if (!in_array($model_id, $_SESSION['viewed_creation_ids'])) {
            $view_stmt = $conn->prepare("UPDATE model SET views = views + 1 WHERE id = ? AND removed = 0");
            $view_stmt->bind_param("i", $model_id);
            $view_stmt->execute();
            $_SESSION['viewed_creation_ids'][] = $model_id;
        }
    }
    
    $model_tags = [];
    $Tag_stmt = $conn->prepare("SELECT * FROM tags WHERE model_id = ?");
    $Tag_stmt->bind_param("i", $model_id);
    $Tag_stmt->execute();
    $Tag_result = $Tag_stmt->get_result();

    while ($tags_row = $Tag_result->fetch_assoc()) {
        $model_tags[] = [
            'name' => $tags_row['tag_name'],
            'display' => true
        ];
    }
    $Tag_stmt->close();

    http_response_code(200);
    $data = [
        'success' => true,
        'userid' => $userid,
        'username' => $user_name,
        'user_image' => $user_image,
        'modelid' => $model_id,
        'model' => $row2['model'],
        'description' => $decoded_description,
        'tags' => $model_tags,
        'name' => $name,
        'date' => date("F j, Y, g:i a", strtotime($row2['date'])),
        'screenshot' => $row2['screenshot'],
        'views' => $views,
        'is_removed' => $row2['removed'],
        'did_track' => $did_track,
        'legacy' => $row2['legacy'],
        'can_edit' => $can_edit,
        'voted' => $voted,
        'is_subbed' => $is_subbed ?? false,
		'is_subbed_comment' => $is_subbed_comment ?? false,
		'is_subbed_fav' => $is_subbed_fav ?? false,
        'likes' => $votes,
        'comments' => $row2['replies'],
        'followers' => $followers,
        'conversation_subbed' => $notifications->get_subscribers('comment', $model_id),
        'model_admin' => $row->admin ?? false,
        'message' => $message ?? null
    ];
    return json_encode($data);
}

if(isset($_GET['fetch'])) {
    header('Content-Type: application/json');
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        header("Refresh:0");
    }

    $model_id = htmlspecialchars((int)$_GET['buildId']);
    $data = fetch_build($model_id, $_SESSION['csrf']);
    exit($data);
}

function fetch_comments($model_id, $csrf) {
    global $conn;
    global $conn2;
    global $current_user;
    global $bbcode;

    if (empty($csrf) || $csrf != $_SESSION['csrf']) {
        return json_encode(["error" => 'Invalid CSRF token provided']);
    }

    $model_id = (int)$model_id;
    $id = loggedin() ? (int)$current_user->id : 0;

    if($current_user->admin !== true) {
	    $sql = "SELECT * FROM comments WHERE model = $model_id AND hidden = 0 ORDER BY id ASC";
    } else {
        $sql = "SELECT * FROM comments WHERE model = $model_id ORDER BY id ASC";
    }

    $result = $conn->query("SELECT * FROM model WHERE id = '$model_id' AND removed = 0");
    if($result->num_rows === 0 && (loggedin() && $current_user->admin != true)) {
        return json_encode(["error" => ErrorRegistry::get('creation', 'not_found')]);
    }

    $comResult = $conn->query($sql);
    $rows = [];
    $comments = [];
    $fav_ids = [];
    $blocked = [];
    $privated = [];
    $suspended = [];

    while ($row = $comResult->fetch_assoc()) {
        $rows[] = $row;
    }

    $userIds = array_column($rows, 'user');
    $users = User::getUsers($userIds);
    $notifications = new Notifications($conn2);

    if (!empty($rows)) {
        $userIds = array_column($rows, 'user');
        $users = User::getUsers($userIds);

        if(loggedin()) {
            $commentIds = array_map('intval', array_column($rows, 'id'));
            $userId = (int)$current_user->id;
            $ids = implode(',', $commentIds);
            $res = $conn->query("SELECT comment_id FROM comment_votes WHERE user_id = $id AND comment_id IN ($ids)");

            while ($fav = $res->fetch_assoc()) {
                $fav_ids[(int)$fav['comment_id']] = true;
            }

            $result = $conn2->query("
                SELECT userid, profileid
                FROM user_blocks
                WHERE userid = $userId
                OR profileid = $userId
            ");

            while ($row = $result->fetch_assoc()) {
                $userid = (int)$row['userid'];
                $profileid = (int)$row['profileid'];

                if ($userid === $userId) {
                    $blocked[$profileid]['you_blocked'] = true;
                }

                if ($profileid === $userId) {
                    $blocked[$userid]['they_blocked'] = true;
                }
            }
        }

        foreach ($users as $c_user) {
            $c_user_id = (int)$c_user->id ?? null;

            if(((bool)$c_user->private_profile && !User::isFollowing($c_user_id) && !User::isMe($c_user_id))) {
                $privated[$c_user_id] = true;
            }

            if(((bool)$c_user->suspended)) {
                $suspended[$c_user_id] = true;
            }
        }
    }

	foreach($rows as $row) {
        $comment_id = $row['id'];
        $comment_votes = Numbers::format($row['votes']);
        $is_op = $row['is_op'];
        $c_user = (int)$row['user'];
        $c_user_removed = false;
        $comment = $bbcode->toHTML($row['comment'], true, true);
        $comment_og = $row['comment'];
        $userRow = $users[$c_user] ?? User::getUser($c_user);
        $date = time_ago(date('Y-m-d H:i:s', is_numeric($row['date']) ? $row['date'] : 0));
        $edited_at = null;
        $c_user_privated = $privated[$c_user] ?? false;
        $c_user_banned = $suspended[$c_user] ?? false;

        if($c_user_banned) {
            $c_user_removed = true;
        }

        if(!$userRow) {
            $c_user_removed = true;
            $message = "The account that had posted this reply has been deleted.";
            $comment = null;
            $userRow = null;
        }

        if($c_user_privated) {
            $c_user_removed = true;
            $message = "The account that had posted this reply is private.";
            $comment = null;
            $userRow = null;
        }

        if((int)$row['edited_at'] !== 0) {
            $edited_at = time_ago(date('Y-m-d H:i:s', is_numeric($row['edited_at']) ? $row['edited_at'] : 0));
        }

        $youBlocked = $blocked[$c_user]['you_blocked'] ?? false;
        $theyBlocked = $blocked[$c_user]['they_blocked'] ?? false;
        if(loggedin()) {
            if ($youBlocked && $theyBlocked) {
                $c_user_removed = true;
                $message = "You blocked @" . $userRow->username . ", and they blocked you. Their comments and profile will not be visible.";
                $comment = null;
            } elseif ($youBlocked) {
                $message = "You blocked @" . $userRow->username . ". Your comments and profile will not be visible to them.";
            } elseif ($theyBlocked) {
                $c_user_removed = true;
                $message = "You're blocked from @" . $userRow->username . ". Their comments and profile will not be visible.";
                $comment = null;
            }
        }

        $comments[] = [
            'message' => $message ?? null,
            'id' => $comment_id,
            'userid' => $c_user,
            'user_removed' => $c_user_removed,
            'user_admin' => $userRow->admin ?? 0,
            'username' => $userRow->username ?? '',
            'is_op' => $is_op,
            'is_hidden' => $row['hidden'],
            'parent' => $row['parent'],
            'picture' => $userRow->picture_small ?? null,
            'comment' => $comment,
            'comment_og' => $comment_og,
            'date' => $date,
            'edited_at' => $edited_at,
            'votes' => $comment_votes,
            'voted' => isset($fav_ids[$comment_id]),
            'conversation_subbed' => $notifications->get_subscribers('comment_reply', $comment_id),
        ];
        $message = null;
    }

    $comResult->free();
    return json_encode($comments); 
}

if(isset($_GET['comments'])) {
    header('Content-Type: application/json');
    $model_id = htmlspecialchars((int)$_GET['buildId']);
    $comment_data = fetch_comments($model_id, $_SESSION['csrf']);
    exit($comment_data);
}

if(isset($_POST['comment'])) {
    header('Content-Type: application/json');

    $comment = isset($_POST['commentbox']) ? $_POST['commentbox'] : 0;
    $parent = isset($_POST['parent']) ? (int)$_POST['parent'] : 0;
    $csrf = $_POST['csrf_token'];
    $model_id = $_POST['buildId'];

    if ($_SESSION['csrf'] !== $_POST['csrf_token']) {
        echo json_encode(['error' => ErrorRegistry::get('auth', 'bad_csrf')]);
        exit;
    }

    if(!loggedin() || !isset($current_user)) {
        echo json_encode(['error' => ErrorRegistry::get('comment', 'unauthed')]);
        exit;
    }

    $id = $current_user->id ?? 0;

    if($current_user->verify_token != NULL) {
        echo json_encode(['error' => ErrorRegistry::get('comment', 'unverified')]);
        exit;
    }

    if(strlen($comment) > 500) {
        echo json_encode(['error' => ErrorRegistry::get('comment', 'too_long')]);
        exit;
    }

    if(empty($comment)) {
        echo json_encode(['error' => ErrorRegistry::get('comment', 'too_short')]);
        exit;
    }

    $stmt4 = $conn->prepare("SELECT user FROM model WHERE id = ? AND removed = 0");
    $stmt4->bind_param("i", $model_id);
    $stmt4->execute();
    $result = $stmt4->get_result();

    if($result->num_rows === 0) {
        echo json_encode(['error' => ErrorRegistry::get('creation', 'not_found')]);
        exit;
    }

    $userid = $result->fetch_assoc()['user'] ?? null;

    $stmt_blocked = $conn2->prepare("SELECT * FROM user_blocks WHERE (userid = ? AND profileid = ?) OR (profileid = ? AND userid = ?)");
    $stmt_blocked->bind_param("iiii", $id, $userid, $id, $userid);
    $stmt_blocked->execute();
    $result = $stmt_blocked->get_result();

    if($result->num_rows !== 0) {
        $row = $result->fetch_assoc();

        if($row['userid'] === $id && $row['profileid'] === $userid) {
            echo json_encode(['error' => ErrorRegistry::get('creation', 'user_blocked')]);
            exit;
        } else if($row['profileid'] === $id && $row['userid'] === $userid) {
            echo json_encode(['error' => ErrorRegistry::get('creation', 'user_blocking')]);
            exit;
        }
    }

    if(!empty($parent)) {
        $stmt_reply = $conn->prepare("SELECT user FROM comments WHERE id = ? AND hidden = 0");
        $stmt_reply->bind_param("i", $parent);
        $stmt_reply->execute();
        $result = $stmt_reply->get_result();

        if($result->num_rows === 0) {
            echo json_encode(['error' => ErrorRegistry::get('creation', 'reply_not_found')]);
            exit;
        }

        $c_user = $result->fetch_assoc()['user'];

        $stmt_blocked = $conn2->prepare("SELECT * FROM user_blocks WHERE (userid = ? AND profileid = ?) OR (profileid = ? AND userid = ?)");
        $stmt_blocked->bind_param("iiii", $id, $c_user, $id, $c_user);
        $stmt_blocked->execute();
        $result = $stmt_blocked->get_result();

        if($result->num_rows !== 0) {
            $row = $result->fetch_assoc();

            if($row['userid'] === $id && $row['profileid'] === $c_user) {
                echo json_encode(['error' => 'You have blocked the user you are trying to reply to.']);
                exit;
            } else if($row['profileid'] === $id && $row['userid'] === $c_user) {
                echo json_encode(['error' => 'The person you are trying to reply to has you blocked.']);
                exit;
            } else {
                echo json_encode(['error' => 'You or the user you are replying to have blocked one or the other.']);
                exit;
            }
        }
    }

	$date = time();
    $is_op = $id === $userid ? 1 : 0;
    $comment = $bbcode->Screennameify($comment);

    $sql = "INSERT INTO comments (user, model, comment, parent, date, is_op) VALUES (?, ?, ?, ?, ?, ?) LIMIT 1";
    $stmt2 = $conn->prepare($sql);
    $stmt2->bind_param("iisisi", $id, $model_id, $comment, $parent, $date, $is_op);

    if ($stmt2->execute()) {
        $last_id = $conn->insert_id;
        $stmt2->close();

        $stmtCountUpd = $conn->prepare("UPDATE model SET replies = replies + 1 WHERE id = ?");
        $stmtCountUpd->bind_param("i", $model_id);
        $stmtCountUpd->execute();
        $stmtCountUpd->close();

        $stmtCountGet = $conn->prepare("SELECT replies FROM model WHERE id = ? AND removed = 0");
        $stmtCountGet->bind_param("i", $model_id);
        $stmtCountGet->execute();
        $result = $stmtCountGet->get_result();
        $reply_count = $result->fetch_assoc()['replies'];

        $stmtFavsGet = $conn->prepare("SELECT votes FROM comments WHERE id = ? AND hidden = 0");
        $stmtFavsGet->bind_param("i", $last_id);
        $stmtFavsGet->execute();
        $result = $stmtFavsGet->get_result();
        $favs_count = $result->fetch_assoc()['votes']; //the rare case that someone favorites a comment right after publish. it is (technically) possible.

        $notifications = new Notifications($conn2);
        $notifications->subscribe($id, 'comment', $model_id);
        $notifications->subscribe($id, 'comment_reply', $last_id);
        $notifications->notify_subscribers('comment', $model_id, $id);

        if($parent) {
            $notifications->subscribe($id, 'comment_reply', $parent);
            $notifications->notify_subscribers('comment_reply', $parent, $id);
        }

        echo json_encode([
            'success' => 'Comment sent.',
            'comment' => [
                'id' => $last_id,
                'text' => $bbcode->toHTML($comment, true, true),
                'username' => $current_user->username ?? null,
                'userid' => $current_user->id ?? null,
                'admin' => $current_user->admin ?? false,
                'op' => $is_op,
                'parent' => $parent,
                'picture' => $current_user->picture,
                'date' => time_ago(date('Y-m-d H:i:s', $date)),
                'replies' => $reply_count,
                'favs' => $favs_count
            ],
        ]);
        exit;
    } else {
        $stmt2->close();
        echo json_encode(['error' => ErrorRegistry::get('comment', 'generic')]);
        exit;
    }
}

if (isset($_POST['edit_comment'])) {
    header('Content-Type: application/json');

    if ($_SESSION['csrf'] === $_POST['csrf_token']) {
        if (loggedin() && isset($current_user)) {
            $id = $current_user->id ?? 0;
            $comment_text = isset($_POST['commentbox']) ? $_POST['commentbox'] : null;
            $comment_id = isset($_POST['id']) ? (int)$_POST['id'] : null;
            $date = time();

            if(!empty($comment_id) || !empty($comment_text)) {
                $sql = "SELECT id, hidden, user, model FROM comments WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $comment_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    if(trim($row['user']) === trim($current_user->id)) {
                        $sql2 = "UPDATE comments SET comment = ?, edited_at = ? WHERE id = ?";
                        $stmt2 = $conn->prepare($sql2);
                        $stmt2->bind_param("ssi", $comment_text, $date, $comment_id);

                        if ($stmt2->execute()) {
                            echo json_encode(['success' => 'Comment edited', 'comment' => ['text' => $bbcode->toHTML($comment_text, true, true), 'edited_at' => time_ago(date('Y-m-d H:i:s', is_numeric($date) ? $date : 0))]]);
                        } else {
                            echo json_encode(['error' => 'Error editing comment']);
                        }
                    } else {
                        echo json_encode(['error' => 'An authentication error has occured']);
                    }
                } else {
                    echo json_encode(['error' => 'Comment not found']);
                }
            } else {
                echo json_encode(['error' => 'No comment Id or comment text provided']);
            }
        } else {
            echo json_encode(['error' => 'Not logged in']);
        }
    } else {
        echo json_encode(['error' => 'Oops! Your CSRF token seems to be invalid.']);
    }
    exit;
}

if (isset($_POST['comment_preview']) && isset($_POST['commentbox'])) {
    header('Content-Type: application/json');

    if ($_SESSION['csrf'] === $_POST['csrf_token']) {
        if (loggedin()) {
            $comment_text = isset($_POST['commentbox']) ? $_POST['commentbox'] : null;
            $date = time();

            echo json_encode(['success' => true, 'comment' => ['text' => $bbcode->toHTML($comment_text, true, true), 'edited_at' => time_ago(date('Y-m-d H:i:s', is_numeric($date) ? $date : 0))]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Oops! Your CSRF token seems to be invalid.']);
    }
    exit;
}

if (loggedin() && isset($current_user)) {
    $id = $current_user->id ?? 0;

    if (isset($_POST['downvote'])) {
        header('Content-Type: application/json');

        if (!isset($_POST['model_id']) || !is_numeric($_POST['model_id'])) {
            echo json_encode(['error' => 'Invalid model id provided']);
            exit;
        }

        $model_id = (int)$_POST['model_id'];
        $likes = $vote_count = null;

        $stmt = $conn->prepare("SELECT likes FROM model WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $model_id);
        $stmt->execute();
        $stmt->bind_result($likes);
        $stmt->fetch();
        $stmt->close();
        
        $stmt = $conn->prepare("SELECT COUNT(*) FROM votes WHERE creation = ? AND user = ?");
        $stmt->bind_param("ii", $model_id, $id);
        $stmt->execute();
        $stmt->bind_result($vote_count);
        $stmt->fetch();
        $stmt->close();

        if ($vote_count === 0) {
            echo json_encode(['success' => false, 'error' => 'You have not favorited this creation']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE model SET likes = likes - 1 WHERE id = ?");
        $stmt->bind_param("i", $model_id);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'error' => 'Failed to favorite creation']);
            exit;
        }
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM votes WHERE user = ? AND creation = ?");
        $stmt->bind_param("ii", $id, $model_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => 'Unfavorited creation', 'text' => 'Favorite (' . $likes - 1 . ')']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to unfavorite creation']);
        }
        $stmt->close();
    }

    if (isset($_POST['upvote'])) {
        header('Content-Type: application/json');

        if (!isset($_POST['model_id']) || !is_numeric($_POST['model_id'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid creation id provided']);
            exit;
        }

        $model_id = (int)$_POST['model_id'];
        $likes = $model_user = $vote_count = null;

        $stmt = $conn->prepare("SELECT user, likes FROM model WHERE id = ? AND removed = 0 LIMIT 1");
        $stmt->bind_param("i", $model_id);
        $stmt->execute();
        $stmt->bind_result($model_user, $likes);
        $stmt->fetch();
        $stmt->close();

        if ($model_id === 0) {
            echo json_encode(['error' => 'Creation does not exist in the database']);
            exit;
        }

        $stmt = $conn->prepare("SELECT COUNT(*) FROM votes WHERE creation = ? AND user = ?");
        $stmt->bind_param("ii", $model_id, $id);
        $stmt->execute();
        $stmt->bind_result($vote_count);
        $stmt->fetch();
        $stmt->close();

        if ($vote_count > 0) {
            echo json_encode(['success' => false, 'error' => 'You have already favorited this creation']);
            exit;
        }

        if($current_user->verify_token != NULL) {
            http_response_code(401);
            echo json_encode(['error' => "Please verify your account to continue this action."]);
            exit;
        }

        $reporttype = 'creation';
        $stmt_check = $conn->prepare("SELECT * FROM reports WHERE reporter_user_id = ? AND reportable_id = ? AND reportable_type = ?");
        $stmt_check->bind_param("iis", $id, $model_id, $reporttype);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if($result->num_rows !== 0) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'An unknown error has occured. Please try again later.']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO votes (creation, user) VALUES (?, ?)");
        $stmt->bind_param("ii", $model_id, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => 'Favorited creation', 'text' => 'Unfavorite (' . $likes + 1 . ')']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'An unknown error has occured. Please try again later.']);
        }

        $stmt = $conn->prepare("UPDATE model SET likes = likes + 1 WHERE id = ?");
        $stmt->bind_param("i", $model_id);

        if ($stmt->execute()) {
            $id = $current_user->id;

            $notifications = new Notifications($conn2);
            $notifications->notify_subscribers('creation_fav', $model_id, $id);
        }

        $stmt->close();
        exit;
    }
	
	$SUB_TYPES = [
		'creation_fav' => 'favorites',
		'comment' => 'comments'
	];

    if (isset($_POST['subscribe'])) {
        header('Content-Type: application/json');

        if (!isset($_POST['model_id']) || !is_numeric($_POST['model_id'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid creation id provided']);
            exit;
        }

        $model_id = (int)$_POST['model_id'];
		$type = $_POST['type'];

		if($type !== 'comment' && $type !== 'creation_fav') {
			echo json_encode(['error' => 'Invalid type of method']);
            exit;
		}

        $stmt = $conn->prepare("SELECT user, likes FROM model WHERE id = ? AND removed = 0 LIMIT 1");
        $stmt->bind_param("i", $model_id);
        $stmt->execute();
        $stmt->bind_result($model_user, $likes);
        $stmt->fetch();
        $stmt->close();

        if ($model_id === 0) {
            echo json_encode(['error' => 'Creation does not exist in the database']);
            exit;
        }

        if($current_user->verify_token != NULL) {
            http_response_code(401);
            echo json_encode(['error' => "Please verify your account to continue this action."]);
            exit;
        }

        $id = $current_user->id;
        $notifications = new Notifications($conn2);
        $notifications->subscribe($id, $type, $model_id);

        echo json_encode(['success' => true, 'type' => $type, 'text' => "Unsubscribe from " . $SUB_TYPES[$type], "textParent" => 'Unsubscribe']);
        exit;
    }

    if (isset($_POST['unsubscribe'])) {
        header('Content-Type: application/json');

        if (!isset($_POST['model_id']) || !is_numeric($_POST['model_id'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid creation id provided']);
            exit;
        }

        $model_id = (int)$_POST['model_id'];
		$type = $_POST['type'];

		if($type !== 'comment' && $type !== 'creation_fav') {
			echo json_encode(['error' => 'Invalid type of method']);
            exit;
		}

        $stmt = $conn->prepare("SELECT user, likes FROM model WHERE id = ? AND removed = 0 LIMIT 1");
        $stmt->bind_param("i", $model_id);
        $stmt->execute();
        $stmt->bind_result($model_user, $likes);
        $stmt->fetch();
        $stmt->close();

        if ($model_id === 0) {
            echo json_encode(['error' => 'Creation does not exist in the database']);
            exit;
        }

        if($current_user->verify_token != NULL) {
            http_response_code(401);
            echo json_encode(['error' => "Please verify your account to continue this action."]);
            exit;
        }

        $id = $current_user->id;
        $notifications = new Notifications($conn2);
        $notifications->remove_subscriber($type, $model_id, $id);

		echo json_encode(['success' => true, 'type' => $type, 'text' => "Subscribe to " . $SUB_TYPES[$type], "textParent" => 'Subscribe']);
        exit;
    }

    if (isset($_POST['upvote_comment'])) {
        header('Content-Type: application/json');

        if (!isset($_POST['comment_id']) || !is_numeric($_POST['comment_id'])) {
            echo json_encode(['error' => 'Invalid comment id provided']);
            exit;
        }
        
        $username = htmlspecialchars($current_user->username);
        $comment_id = (int)$_POST['comment_id'];

        $stmt = $conn->prepare("SELECT id, user FROM comments WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();
        $stmt->bind_result($comment_id, $comment_user);
        $stmt->fetch();
        $stmt->close();

        if (!$comment_id) {
            echo json_encode(['error' => 'Comment does not exist in the database']);
            exit;
        }

        $stmt = $conn->prepare("SELECT COUNT(*) FROM comment_votes WHERE comment_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $comment_id, $id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            echo json_encode(['error' => 'You have already favorited this comment']);
            exit;
        }
        
        if($current_user->verify_token != NULL) {
            http_response_code(401);
            echo json_encode(['error' => "Please verify your account to continue this action."]);
            exit;
        }

        $youBlocked = false;
        $theyBlocked = false;
        $blockResult = $conn2->query("SELECT * FROM user_blocks WHERE (userid = '$comment_user' AND profileid = '$id') OR (userid = '$id' AND profileid = '$comment_user')");
        if ($blockResult && $blockResult->num_rows > 0) {
            while ($row_block = $blockResult->fetch_assoc()) {
                if ($row_block['userid'] == $id && $row_block['profileid'] == $comment_user) {
                    $youBlocked = true;
                } elseif ($row_block['userid'] == $comment_user && $row_block['profileid'] == $id) {
                    $theyBlocked = true;
                }
            }
        }

        if ($theyBlocked) {
            echo json_encode(['error' => 'You cannot favorite this comment']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO comment_votes (comment_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $comment_id, $id);

        $stmt_upd = $conn->prepare("UPDATE comments SET votes = votes + 1 WHERE id = ?");
        $stmt_upd->bind_param("i", $comment_id);
    
        if ($stmt->execute() && $stmt_upd->execute()) {
            $stmt_count = $conn->prepare("SELECT votes FROM comments WHERE id = ?");
            $stmt_count->bind_param("i", $comment_id);
            $stmt_count->execute();
            $stmt_count->bind_result($count);
            $stmt_count->fetch();
            $stmt_count->close();

            echo json_encode([
                'success' => true,
                'message' => 'Comment favorited',
                'count' => Numbers::format($count),
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to favorite comment']);
        }
        $stmt->close();
    }

    if (isset($_POST['downvote_comment'])) {
        header('Content-Type: application/json');

        if (!isset($_POST['comment_id']) || !is_numeric($_POST['comment_id'])) {
            echo json_encode(['error' => 'Invalid comment id provided']);
            exit;
        }

        $comment_id = (int) $_POST['comment_id'];

        $stmt = $conn->prepare("SELECT COUNT(*) as vote_count FROM comment_votes WHERE comment_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $comment_id, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $vote_count = (int)$row['vote_count']; 
        $stmt->close();

        if ($vote_count === 0) {
            echo json_encode(['error' => 'You have not favorited this comment']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM comment_votes WHERE comment_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $comment_id, $id);

        $stmt_upd = $conn->prepare("UPDATE comments SET votes = votes - 1 WHERE id = ?");
        $stmt_upd->bind_param("i", $comment_id);

        if ($stmt->execute() && $stmt_upd->execute()) {
            $stmt_count = $conn->prepare("SELECT votes FROM comments WHERE id = ?");
            $stmt_count->bind_param("i", $comment_id);
            $stmt_count->execute();
            $stmt_count->bind_result($count);
            $stmt_count->fetch();
            $stmt_count->close();

            echo json_encode(['success' => true, 'message' => 'Comment unfavorited', 'count' => Numbers::format($count)]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to unfavorite']);
        }
        $stmt->close();
    }
}
?>