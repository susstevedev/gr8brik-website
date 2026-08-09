<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/numbers.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/com/bbcode.php';
$bbcode = new BBCode;

$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
$conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

if(isset($_GET['storage'])) {
    header('Content-Type: application/json');

    if(!isset($_GET['user'])) {
        if (!loggedin()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Please login to view this data.']);
            exit;
        }

        $user = $current_user->id;
        $name_user = $current_user->id;
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
            echo json_encode(['success' => false, 'message' => 'user not found']);
            exit;
        }
    }

    $stmt = $conn->prepare("SELECT SUM(size) as total_used FROM model WHERE user = ?");
    $stmt->bind_param("i", $user);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total_used'] ?? 0;
    $stmt->close();

    echo json_encode(['success' => true, 'userid' => $user, 'user' => $name_user, 't' => time(), 'total' => (int)$total]);
    exit;
}

if (isset($_POST['save_build'])) {
    header('Content-Type: application/json');
    if(!isset($_POST['build_id']) || $_POST['build_id'] === null || $_POST['build_id'] === "null") {
        $modelJson = $_POST['creation'];
        $visible = $_POST['visibility'];

        if (empty($modelJson)) {
            http_response_code(400);
            echo json_encode(['error' => "Request is empty."]);
            exit;
        }

        $decoded_json = json_decode($modelJson, true);
        if ($modelJson === null && json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['error' => "Invalid creation format."]);
            exit;
        }

        if (!loggedin()) {
            http_response_code(401);
            echo json_encode(['error' => "Please login to save models."]);
            exit;
        }

        $min_supported_model = "1.2.1.2";
        $min_supported_modeler = "2026.07.25";
        $user_file_version = $decoded_json['metadata']['file_version'] ?? '0.0.0.0';

        if (version_compare($min_supported_model, $user_file_version, '>')) {
            http_response_code(400);
            echo json_encode(['error' => "File version is not supported. Please update your modeler version to at least " . $min_supported_model]);
            exit;
        }

        if(!$visible || empty($visible) || $visible != 'public' && $visible != 'unlisted' && $visible != 'private') {
            http_response_code(400);
            echo json_encode(['error' => "Visibility must be of string values: public, unlisted, private"]);
            exit;
        }

        $user = $current_user->id ?? 0;

        if (User::isDeleted($user)) {
            http_response_code(401);
            echo json_encode(['error' => "Invalid login"]);
            exit;
        }

        $stmt = $conn->prepare("SELECT SUM(size) as total_used FROM model WHERE user = ?");
        $stmt->bind_param("i", $user);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result->fetch_assoc()['total_used'] ?? 0;
        $stmt->close();

        $file_id = uniqid();
        $file_name = "../cre/" . $file_id . ".json";
        $db_file_name = "/cre/" . $file_id . ".json";
        $desc = htmlspecialchars($_POST['desc']);
        $name = htmlspecialchars($_POST['name']);
        $date = date("Y-m-d H:i:s");
        $screenshot_path = null;
        $db_screenshot = null;
        $db_file_size = strlen($modelJson);

        if (($total + $db_file_size) > MODEL_STORAGE_LIMIT) {
            http_response_code(413);
            echo json_encode(['error' => "Storage limit of " . Numbers::filesize(MODEL_STORAGE_LIMIT) . " was reached. Please delete older creations to save new ones."]);
            exit;
        }

        if (!empty($_POST['screenshot'])) {
            $screenshot_data = $_POST['screenshot'];

            if (strpos($screenshot_data, 'data:image/png;base64,') === 0) {
                $base64_str = substr($screenshot_data, strlen('data:image/png;base64,'));
            } elseif (strpos($screenshot_data, 'data:image/webp;base64,') === 0) {
                $base64_str = substr($screenshot_data, strlen('data:image/webp;base64,'));
            } else {
                http_response_code(400);
                echo json_encode(['error' => "Screenshot must be encoded in WebP or PNG."]);
                exit;
            }

            $image = imagecreatefromstring(base64_decode($base64_str, true));
            if (!$image) {
                http_response_code(400);
                echo json_encode(['error' => "Thumbnail is not a valid image."]);
                exit;
            }

            $screenshot_path = "../cre/" . $file_id . ".webp";
            $db_screenshot = "/cre/" . $file_id . ".webp";

            imagealphablending($image, false);
            imagesavealpha($image, true);
            $saved = imagewebp($image, $screenshot_path, 80);

            if (!$saved) {
                http_response_code(500);
                echo json_encode(['error' => "Failed to save screenshot."]);
                exit;
            }
        }

        if (file_put_contents($file_name, $modelJson) === false) {
            http_response_code(500);
            echo json_encode(['error' => "Failed to save creation JSON to filesystem."]);
            exit;
        }

        $db_file_size = filesize($file_name);

        if ($conn->connect_error) {
            http_response_code(500);
            echo json_encode(['error' => "Database connection failed."]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO model (user, model, description, name, date, size, screenshot, visibility) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => "Failed to save your creation to the database."]);
            exit;
        }
        $stmt->bind_param("issssiss", $user, $db_file_name, $desc, $name, $date, $db_file_size, $db_screenshot, $visible);

        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(['error' => "Failed to save your creation to the database."]);
            exit;
        }

        $_SESSION['last_request'] = time();
        $stmt->close();
        $conn->close();

        echo json_encode(['success' => "Your creation was saved successfully!", 'screenshot' => $screenshot_path]);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['error' => "Could not save: {0}"]);
        exit;
    }
}

function truncateStr($mystr) {
    $truncatedName = substr($mystr, 0, 30);
    if (strlen($mystr) >= 30) {
        $truncatedName .= '...';
    }
}

// Model screenshot to PNG
function convert_webp_to_png($source_webp_file) {
    clearstatcache();
    $webp_file = str_replace('/cre/', '', $source_webp_file);
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/cre/' . $webp_file) && exif_imagetype($_SERVER['DOCUMENT_ROOT'] . '/cre/' . $webp_file) === IMAGETYPE_WEBP) {
        $image = imagecreatefromwebp($_SERVER['DOCUMENT_ROOT'] . '/cre/' . $webp_file);
        
        if ($image !== false) {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
            
            ob_start();
			imagepng($image);
			$image_data = ob_get_contents();
			ob_end_clean();
             
            $base64_data = base64_encode($image_data);
            return $base64_data;
        } else {
            return false;
        }
    } else {
        return "Not found";
    }
}

function fetch_build($model_id, $csrf) {
    global $current_user;
    global $conn;
    global $conn2;

    require_once $_SERVER['DOCUMENT_ROOT'] . '/com/bbcode.php';
    $bbcode = new BBCode();

    if (empty($csrf) || $csrf != $_SESSION['csrf']) {
        return json_encode([
            "message" => 'No CSRF token provided, or it is invalid!',
            "error" => 'INVALID_CSRF'
        ]);
    }
    
    if ($conn->connect_error) {
        exit($conn->connect_error);
    }

    if (!is_numeric($model_id)){
        return json_encode([
            "message" => 'Invalid ID provided for creation',
            "error" => 'INVALID_ID'
        ]);
    }

    $sql = "SELECT * FROM model WHERE id = '$model_id' AND visibility != 'private' AND removed = 0";
    if(loggedin()) {
        if($current_user->admin === true) {
            $sql = "SELECT * FROM model WHERE id = '$model_id'";
        }
    }

    $result = $conn->query($sql);
    $row2 = $result->fetch_assoc();

    if($result->num_rows === 0 || empty($row2['id'])) {
        http_response_code(404);
        return json_encode([
            "message" => 'Creation not found',
            "error" => '404'
        ]);
    }

    $userid = $row2['user'];
    $views = $row2['views'];
    $votes = $row2['likes'];
    $decoded_description = $bbcode->toHTML($row2['description'], true, true);
       
    if ($conn2->connect_error) {
        exit($conn2->connect_error);
    }

    $row = User::getUser($userid);
    $username = $row->username;

    $did_track = false;
    if(loggedin()) {
        if(Cookie::analytics_user($conn2, $userid, $current_user->id)) {
            $did_track = true;
        }
    }
    
    $stmt = $conn2->prepare("SELECT COUNT(*) as following FROM follow WHERE profileid = ?");
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $followers = $stmt->get_result()->fetch_assoc()['following'];
    $stmt->close();
    
    $result3 = $conn2->query("SELECT * FROM bans WHERE user = $userid");
    $row3 = $result3->fetch_assoc();

    while ($row3 = $result3->fetch_assoc()) {
        if ($result3->num_rows > 0 && $row3['end_date'] >= time()) {
            http_response_code(400);
            return json_encode([
                "message" => 'Creation could not load as the account that made it has been banned',
                "error" => 'ACC_BANNED'
            ]);
        }
    }

    if(loggedin()) {
        $id = $current_user->id;

        $sql = "SELECT * FROM user_blocks WHERE userid = ? AND profileid = ? LIMIT 1";
        $stmt = $conn2->prepare($sql);
        $stmt->bind_param("ii", $userid, $id);
        $stmt->execute();
        $result4 = $stmt->get_result();
        $stmt->close();

        if ($result4->num_rows > 0) {
            http_response_code(403);
            return json_encode([
                "message" => htmlspecialchars($row->username) . " has blocked you.",
                "error" => 'ACC_BLOCKING'
            ]);
        }

        $find_votes = $conn->query("SELECT * FROM votes WHERE user = '$id' AND creation = '$model_id' LIMIT 1");
        if ($find_votes->num_rows > 0) {
            $voted = true;
        } else {
            $voted = false;
        }
    } else {
        $voted = false;
    }

    $saved_prefs = Cookie::controls();
    if(in_array('site-prefs', $saved_prefs)) {
        if (!isset($_SESSION['viewed_creation_ids'])) {
            $_SESSION['viewed_creation_ids'] = [];
        }

        if (!in_array($model_id, $_SESSION['viewed_creation_ids'])) {
            $view_stmt = $conn->prepare("UPDATE model SET views = views + 1 WHERE id = ?");
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
        'modelid' => $model_id,
        'model' => $row2['model'],
        'description' => $decoded_description,
        'tags' => $model_tags,
        'name' => htmlspecialchars($row2['name']),
        'date' => date("F j, Y, g:i a", strtotime($row2['date'])),
        'screenshot' => $row2['screenshot'],
        'views' => $views,
        'is_removed' => $row2['removed'],
        'did_track' => $did_track,
        'legacy' => $row2['legacy'],
        'voted' => $voted,
        'likes' => $votes,
        'comments' => $row2['replies'],
        'username' => $username,
        'followers' => $followers,
        'model_admin' => $row->admin,
        'message' => $message ?? null
    ];
    return json_encode($data);
}

if(isset($_GET['fetch'])) {
    header('Content-Type: application/json');
    $model_id = htmlspecialchars((int)$_GET['buildId']);
    $data = fetch_build($model_id, $_SESSION['csrf']);
    exit($data);
}

function fetch_comments($model_id, $csrf) {
    global $conn;
    global $conn2;
    global $current_user;

    require_once $_SERVER['DOCUMENT_ROOT'] . '/com/bbcode.php';
    $bbcode = new BBCode();

    if (empty($csrf) || $csrf != $_SESSION['csrf']) {
        return json_encode([
            "message" => 'Invalid CSRF token provided',
            "error" => 'INVALID_CSRF'
        ]);
    }

    if(loggedin()) {
        $id = $current_user->id;
    }

    $message = null;

	$sql = "SELECT * FROM comments WHERE model = $model_id AND hidden = 0 ORDER BY id ASC";
	$comResult = $conn->query($sql);

    $comments = [];
	while ($row = $comResult->fetch_assoc()) {
        $comment_id = $row['id'];

        $voted_query = $conn->query("SELECT * FROM comment_votes WHERE comment_id = '$comment_id'");
        $comment_votes = 0;
        $is_voted = false;
        $is_op = $row['is_op'];
        while ($voted_row = $voted_query->fetch_assoc()) {
            $comment_votes++;

            if (loggedin() && (int)$voted_row['user_id'] === (int)$id) {
                $is_voted = true;
            }
        }
        
        $c_user = $row['user'];
        $userRow = User::getUser($c_user);

        $youBlocked = false;
        $theyBlocked = false;
        if(loggedin()) {
            $blockResult = $conn2->query("SELECT * FROM user_blocks WHERE (userid = '$c_user' AND profileid = '$id') OR (userid = '$id' AND profileid = '$c_user')");
            if ($blockResult && $blockResult->num_rows > 0) {
                while ($row_block = $blockResult->fetch_assoc()) {
                    if ($row_block['userid'] == $id && $row_block['profileid'] == $c_user) {
                        $youBlocked = true;
                    } elseif ($row_block['userid'] == $c_user && $row_block['profileid'] == $id) {
                        $theyBlocked = true;
                    }
                }
                if ($youBlocked && $theyBlocked) {
                    $message = "You blocked @" . $userRow->username . ", and they blocked you. Their comments and profile will not be visible.";
                } elseif ($youBlocked) {
                    $message = "You blocked @" . $userRow->username . ". Your comments and profile will not be visible to them.";
                } elseif ($theyBlocked) {
                    $message = "You're blocked from @" . $userRow->username . ". Their comments and profile will not be visible.";
                }
            }
        }

        if (!is_numeric($row['date'])) {
            $row['date'] = 0;
        }

        $comment = $bbcode->toHTML($row['comment'], true, true);

        $comments[] = [
            'id' => $comment_id,
            'userid' => $c_user,
            'user_admin' => $userRow->admin || 0,
            'username' => $userRow->username,
            'is_op' => $is_op,
            'picture' => $userRow->picture,
            'comment' => $comment,
            'date' => time_ago(date('Y-m-d H:i:s', $row['date'])),
            'votes' => $comment_votes,
            'voted' => $is_voted,
            'message' => $message
        ];
        $message = null;
    }
    $comResult->free();
    return json_encode($comments); 
}

if(isset($_GET['build_comments'])) {
    header('Content-Type: application/json');
    $model_id = htmlspecialchars((int)$_GET['buildId']);
    $comment_data = fetch_comments($model_id, $_SESSION['csrf']);
    exit($comment_data);
}

if(isset($_POST['comment'])) {
    header('Content-Type: application/json');

    $id = $current_user->id;
	$comment = $_POST['commentbox'];
    $csrf = $_POST['csrf_token'];
    $model_id = $_POST['buildId'];
	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
    $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

    if ($_SESSION['csrf'] !== $_POST['csrf_token']) {
        http_response_code(401);
        echo json_encode(['error' => 'Your cross-site-request-forgery token seems to be invalid.']);
        exit;
    }

    if(!loggedin()) {
        http_response_code(401);
        echo json_encode(['error' => 'Please login to comment.']);
        exit;
    }

    if($current_user->verify_token != NULL) {
        http_response_code(401);
        echo json_encode(['error' => 'Please verify your account to comment.']);
        exit;
    }

    if(strlen($comment) > 500) {
        http_response_code(400);
        echo json_encode(['error' => 'Comment must be less than 500 characters.']);
        exit;
    }

    if(empty($comment)) {
        http_response_code(400);
        echo json_encode(['error' => 'Comment must contain text.']);
        exit;
    }

    $stmt4 = $conn->prepare("SELECT user FROM model WHERE id = ?");
    $stmt4->bind_param("i", $model_id);
    $stmt4->execute();
    $result = $stmt4->get_result();
    $userid = (int)$result->fetch_assoc()['user'];
		
	$date = time();
    $is_op = $_SESSION['userid'] === $userid ? 1 : 0;
    $id = $current_user->id;
    
    $sql = "INSERT INTO comments (user, model, comment, date, is_op) VALUES (?, ?, ?, ?, ?) LIMIT 1";
    $stmt2 = $conn->prepare($sql);
    $stmt2->bind_param("iissi", $id, $model_id, $comment, $date, $is_op);

    $category = 2; //2 = comment on model
    $stmt = $conn2->prepare("INSERT IGNORE INTO subscriptions (userid, category, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $id, $category, $model_id);
    $stmt->execute();
    
    if ($stmt2->execute()) {
        $stmt2->close();

        $stmtCountUpd = $conn->prepare("UPDATE model SET replies = replies + 1 WHERE id = ?");
        $stmtCountUpd->bind_param("i", $model_id);
        $stmtCountUpd->execute();
        $stmtCountUpd->close();

        $sub_stmt = $conn2->prepare("SELECT userid FROM subscriptions WHERE content = ? AND category = ? AND userid != ?");
        $sub_stmt->bind_param("iii", $model_id, $category, $id);
        $sub_stmt->execute();
        $result = $sub_stmt->get_result();

        $subscribers = [];
        while ($row = $result->fetch_assoc()) {
            $subscribers[] = $row['userid'];
        }
        $sub_stmt->close();

        if (!empty($subscribers)) {
            $notif_stmt = $conn2->prepare("INSERT INTO notifications (user, profile, timestamp, content, category) VALUES (?, ?, ?, ?, ?)");
            $alert_stmt = $conn2->prepare("UPDATE users SET alert = alert + 1 WHERE id = ?");

            foreach ($subscribers as $subscriber_id) {
                $notif_stmt->bind_param("iisii", $subscriber_id, $id, $date, $model_id, $category);
                $notif_stmt->execute();

                $alert_stmt->bind_param("i", $subscriber_id);
                $alert_stmt->execute();
            }

            $notif_stmt->close();
            $alert_stmt->close();
        }

        $conn->close();
        http_response_code(200);
        echo json_encode(['success' => 'Comment sent.']);
        exit;
    } else {
        $stmt2->close();
        $conn->close();
        http_response_code(500);
        echo json_encode(['error' => 'Could not send comment. Please try again later.']);
        exit;
    }
}

if (loggedin()) {
    $id = $current_user->id;

    if (isset($_POST['downvote'])) {
        header('Content-Type: application/json');

        if (!isset($_POST['model_id']) || !is_numeric($_POST['model_id'])) {
            echo json_encode(['error' => 'Invalid model id provided']);
            exit;
        }

        $model_id = (int)$_POST['model_id'];

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
            $time = time();
            $content = $model_id;
            $id = $current_user->id;
            $category = 5;
            $sql = "INSERT INTO notifications (user, profile, timestamp, content, category) VALUES (?, ?, ?, ?, ?)";

            $stmt2 = $conn2->prepare($sql);
            $stmt2->bind_param("iisii", $model_user, $id, $time, $content, $category);
            $stmt2->execute();
            $stmt2->close();

            $sql = "SELECT alert FROM users WHERE id = ? LIMIT 1";
            $stmt3 = $conn2->prepare($sql);
            $stmt3->bind_param("i", $model_user);
            $stmt3->bind_result($alert);
            $stmt3->execute();
            $stmt3->fetch();
            $stmt3->close();

            $alertnum = $alert + 1;
            $stmt2 = $conn2->prepare("UPDATE users SET alert = ? WHERE id = ?");
            $stmt2->bind_param("ii", $alertnum, $model_user);
            $stmt2->execute();

            $stmt2->close();
            $conn->close();
        }

        $stmt->close();
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

        if ($comment_id === 0) {
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
        
        $comm_data_arr = array(
            'success' => true,
            'message' => 'Comment favorited',
            'comment_user' => $comment_user,
            'comment_id' => $comment_id
        );

        $stmt = $conn->prepare("INSERT INTO comment_votes (comment_id, user_id, user_name) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $comment_id, $id, $username);
        if ($stmt->execute()) {
            echo json_encode($comm_data_arr);
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
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Comment unfavorited']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to unfavorite']);
        }
        $stmt->close();
    }
}
?>