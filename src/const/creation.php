<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/const/utils/time.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/notifications.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/const/utils/numbers.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/const/toMarkdown.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/const/utils/safe_url_id.php';

$conn = Database::get(DB_NAME2);
$conn2 = Database::get(DB_NAME);

/**
 * Validate creation json
 */
function valid_json_creation(array $decoded_json): bool {
    if ($decoded_json === null || json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }

    if(!isset($decoded_json['metadata'])) {
        return false;
    }

    if(!isset($decoded_json['camera'])) {
        return false;
    }

    if(!isset($decoded_json['camera']['x']) || !isset($decoded_json['camera']['y']) || !isset($decoded_json['camera']['z'])) {
        return false;
    }

    if(!isset($decoded_json['metadata']['file_version'])) {
        return false;
    }

    if(!is_int($decoded_json['metadata']['file_version']) || $decoded_json['metadata']['file_version'] < 1.2) {
        return false;
    }

    if(!isset($decoded_json['blocks'])) {
        return false;
    }

    return true;
}

/**
 * Save creation to database
 */
function save_creation($build_id, $name, $desc, $modelJson, $visible, $screenshot_data, $can_edit) {
    global $current_user, $conn, $conn2;

    if (!loggedin() || !isset($current_user)) {
        http_response_code(401);
        return ['error' => ErrorRegistry::get('auth', 'unauthed')];
    }

    $user = $current_user->id ?? 0;

    if (empty($modelJson)) {
        http_response_code(400);
        return ['error' => ErrorRegistry::get('creation', 'format_invalid')];
    }

    $desc = Formatter::prepare($desc ?: '');
    $name = Formatter::prepare($name ?: "Untitled Creation");
    $name_trim = preg_replace('/[^A-Za-z0-9]/', '_', $name);

    $visible = $visible ?: null;
    $build_id = $build_id ?: null;
    $can_edit = $can_edit ?: 0;

    $modelJson = $modelJson ?: null;
    $screenshot_data = $screenshot_data ?: null;
    $decoded_json = json_decode($modelJson, true);

    if(!valid_json_creation($decoded_json)) {
        http_response_code(400);
        return ['error' => ErrorRegistry::get('creation', 'format_invalid')];
    }

    if (!$visible || !in_array($visible, ['public', 'unlisted', 'private'], true)) {
        http_response_code(400);
        return ['error' => ErrorRegistry::get('creation', 'visibility_invalid')];
    }

    $updating = false;
    $existing = null;
	
	if($visible === 'public' && $current_user->verify_token !== null) {
		http_response_code(400);
        return ['error' => ErrorRegistry::get('creation', 'visibility_unverified')];
	}

    if (isset($_POST['build_id']) && $_POST['build_id'] !== null && $_POST['build_id'] !== "null") {
        $build_id = $_POST['build_id'];

        if ($build_id === false) {
            http_response_code(400);
            return ['error' => ErrorRegistry::get('creation', 'not_found')];
        }

        $stmt = $conn->prepare("SELECT id, num_id, user, model, screenshot, size FROM model WHERE id = ? AND user = ? LIMIT 1");

        if (!$stmt) {
            http_response_code(500);
            return ['error' => ErrorRegistry::get('creation', 'not_found')];
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

    $db_files_path = '/cre/';

    if ($updating) {
        $num_id = $existing['num_id'] ?? $existing['id'];
        $file_name = ".." . $existing['model'];
        $db_file_name = $existing['model'];

        $old_file_size = (int)$existing['size'];

        if (!empty($existing['screenshot'])) {
            $screenshot_path = ".." . $existing['screenshot'];
            $db_screenshot = $existing['screenshot'];
        } else {
            $db_screenshot = $db_files_path . $num_id . ".webp";
            $screenshot_path = '../' . $db_screenshot;
        }
    } else {
        $num_id = random_url_safe();
        $file_id = $name_trim . '_' . $num_id;
        $old_file_size = 0;

        $db_file_name = $db_files_path . $file_id . ".json";
        $file_name = '../' . $db_file_name;
        $db_screenshot = $db_files_path . $file_id . ".webp";
        $screenshot_path = '../' . $db_screenshot;

        if (!is_dir('../' . $db_files_path)) {
            if(!mkdir('../' . $db_files_path)) {
                http_response_code(500);
                return ['error' => ErrorRegistry::get('creation', 'save_fail')];
            }
        }
    }

    $db_file_size = strlen($modelJson);
    $stmt = $conn->prepare("SELECT COALESCE(SUM(size), 0) AS total_used FROM model WHERE user = ? AND removed = 0");

    if (!$stmt) {
        http_response_code(500);
        return ['error' => ErrorRegistry::get('creation', 'storage_invalid')];
    }

    $stmt->bind_param("i", $user);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = (int)$result->fetch_assoc()['total_used'];
    $stmt->close();

    $new_total = $total - $old_file_size + $db_file_size;

    if ($new_total > MODEL_STORAGE_LIMIT) {
        http_response_code(413);
        return ['error' => ErrorRegistry::get('creation', 'storage_max')];
    }

    if (file_put_contents($file_name, $modelJson) === false) {
        http_response_code(500);
        return ['error' => ErrorRegistry::get('creation', 'save_fail')];
    }

    if(empty($screenshot_data)) {
        http_response_code(400);
        return ['error' => ErrorRegistry::get('creation', 'thumbnail_encoding_bad')];
    } else if (!$screenshot_path) {
        http_response_code(500);
        return ['error' => ErrorRegistry::get('creation', 'thumbnail_save_fail')];
    } else {
        if (strpos($screenshot_data, 'data:image/png;base64,') === 0) {
            $base64_str = substr($screenshot_data, strlen('data:image/png;base64,'));
        } elseif (strpos($screenshot_data, 'data:image/webp;base64,') === 0) {
            $base64_str = substr($screenshot_data, strlen('data:image/webp;base64,'));
        } else {
            http_response_code(400);
            return ['error' => ErrorRegistry::get('creation', 'thumbnail_encoding_bad')];
        }

        $decoded_image = base64_decode($base64_str, true);

        if ($decoded_image === false) {
            http_response_code(400);
            return ['error' => ErrorRegistry::get('creation', 'thumbnail_encoding_bad')];
        }

        $image = imagecreatefromstring($decoded_image);

        if (!$image) {
            http_response_code(400);
            return ['error' => ErrorRegistry::get('creation', 'thumbnail_encoding_bad')];
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagedestroy($image);

        if (!imagewebp($image, $screenshot_path, APP_FEATURES['CREATION']['THUMBNAIL_MAX_QUALITY'])) {
            http_response_code(500);
            return ['error' => ErrorRegistry::get('creation', 'thumbnail_save_fail')];
        }
        imagedestroy($image);
    }

    $date = date("Y-m-d H:i:s");

    if ($updating) {
        $stmt = $conn->prepare(
            "UPDATE model SET
            model = ?, description = ?, name = ?, size = ?, screenshot = ?, visibility = ?, can_edit = ?
            WHERE id = ?
            AND user = ?"
        );

        if (!$stmt) {
            http_response_code(500);
            return ['error' => ErrorRegistry::get('creation', 'update_fail')];
        }

        $stmt->bind_param(
            "sssissiii",
            $db_file_name,
            $desc,
            $name,
            $db_file_size,
            $db_screenshot,
            $visible,
            $can_edit,
            $build_id,
            $user
        );

        if (!$stmt->execute()) {
            http_response_code(500);
            return ['error' => ErrorRegistry::get('creation', 'update_fail')];
        }
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO model
            (num_id, user, model, description, name, date, size, screenshot, visibility, can_edit)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            http_response_code(500);
            return ['error' => ErrorRegistry::get('creation', 'save_fail')];
        }

        $stmt->bind_param(
            "sissssissi",
            $num_id,
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
            return ['error' => ErrorRegistry::get('creation', 'save_fail')];
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

    return [
        'success' => $updating ? "Your creation was updated successfully!" : "Your creation was saved successfully!",
        'message' => $updating ? "Your Creation was updated successfully!" : "Your Creation was saved successfully!",
        'creation' => [
            'id' => $build_id,
            'num_id' => $num_id,
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
            'url' => '/@' . urlencode($current_user->username),
        ],
    ];
}

function fetch_build($model_id, $csrf) {
    global $current_user, $conn, $conn2;

    if (empty($csrf) || !valid_csrf($csrf)) {
        return json_encode(["success" => false, "message" => ErrorRegistry::get('auth', 'bad_csrf')]);
    }

    if (!is_numeric($model_id)) {
        return json_encode(["success" => false, "message" => ErrorRegistry::get('creation', 'bad_id')]);
    }

    $stmt = $conn->prepare("SELECT * FROM model WHERE id = ?");
    $stmt->bind_param("i", $model_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row2 = $result->fetch_assoc();

    if (!$row2) {
        http_response_code(404);
        return json_encode(["success" => false, "message" => ErrorRegistry::get('creation', 'not_found')]);
    }

    $id = loggedin() ? ($current_user->id ?? 0) : 0;
    $is_admin = (isset($current_user->admin) && $current_user->admin === true);
    $is_owner = (loggedin() && isset($current_user) && trim($current_user->id) === trim($row2['user']));
    $soft_message = '';

    if ($row2['removed'] === 1) {
        if(!$is_admin) {
            http_response_code(404);
            return json_encode(["success" => false, "message" => ErrorRegistry::get('creation', 'not_found')]);
        } else {
            $soft_message .= "This creation has been **removed** by an admin. This means that it\'s not visible to regular users.";
        }
    }

    if ($row2['visibility'] === 'private') {
        if (!$is_admin && !$is_owner) {
            http_response_code(404);
            return json_encode(["success" => false, "message" => ErrorRegistry::get('creation', 'not_found')]);
        } else {
            $soft_message .= "This creation is **private**, meaning only the owner and site administratiors can view it.";
        }
    }

    if($is_admin || $is_owner) {
        $can_edit = true;
    } else {
        $can_edit = $row2['can_edit'];
    }

    $num_id = $row2['num_id'];
    $userid = $row2['user'];
    $views = number_format($row2['views'] ?? 0);
    $favs = number_format($row2['likes'] ?? 0);
    $replies = number_format($row2['replies'] ?? 0);
    $description_markdown = Formatter::render($row2['description']);
    $name = htmlentities($row2['name'] ?? 'Untited creation');

	$usero = User::getUser($userid);
    $user_exists = true;
    $counts = [];

    if(!isset($usero)) {
        $user_exists = false;
    } else if($usero->suspended !== false) {
        $user_exists = false;
    } else if($usero->deactive !== null) {
        $user_exists = false;
    }

    if (!isset($name) || empty($name)) {
        $name = ($row->username ?? '') . "'s creation";
    }

    $stmt = $conn2->prepare(
        "SELECT SUM(CASE WHEN userid = ? THEN 1 ELSE 0 END) AS following,
        SUM(CASE WHEN profileid = ? THEN 1 ELSE 0 END) AS followers
        FROM follow WHERE userid = ? OR profileid = ?"
    );
    $stmt->execute([$userid, $userid, $userid, $userid]);
    $row = $stmt->get_result()->fetch_assoc();
    $counts['followers'] = number_format($row['followers'] ?? 0);
    $counts['following'] = number_format($row['following'] ?? 0);
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) AS creations, SUM(likes) AS favs, SUM(views) AS views FROM model WHERE user = ? AND removed = 0");
    $stmt->execute([$userid]);
    $row = $stmt->get_result()->fetch_assoc();
    $counts['favorites'] = number_format($row['favs'] ?? 0);
    $counts['creations'] = number_format($row['creations'] ?? 0);
    $counts['views'] = number_format($row['views'] ?? 0);
    $stmt->close();
    unset($row, $stmt);

    $notifications = new Notifications($conn2);

    if(loggedin()) {
        $sql = "SELECT * FROM user_blocks WHERE userid = ? AND profileid = ? LIMIT 1";
        $stmt = $conn2->prepare($sql);
        $stmt->bind_param("ii", $userid, $id);
        $stmt->execute();
        $result4 = $stmt->get_result();
        $stmt->close();

        if ($result4->num_rows > 0) {
            if(!$is_admin) {
                http_response_code(403);
                return json_encode(["success" => false, "message" => ErrorRegistry::get('creation', 'user_blocked')]);
            } else {
                $soft_message .= "\n" . ErrorRegistry::get('creation', 'user_blocked');
            }
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

    $soft_message = isset($soft_message) ? Formatter::render($soft_message) : null;
    http_response_code(200);
    $data = [
        'success' => true,
        'user' => [
            'exists' => $user_exists,
            'id' => $userid ?? 0,
            'name' => $usero->username ?? '[deleted]',
            'image' => $usero->profile->picture_small ?? '/img/no_image.png',
            'admin' => $usero->admin ?? false,
            'stats' => $counts,
        ],
        'num_id' => $num_id,
        'modelid' => $model_id,
        'model' => $row2['model'],
        'description_raw' => $row2['description'],
        'description_markdown' => $description_markdown,
        'name' => $name,
        'date' => date("F j, Y, g:i a", strtotime($row2['date'])),
        'screenshot' => $row2['screenshot'],
        'views' => $views,
        'is_removed' => $row2['removed'],
        'legacy' => $row2['legacy'],
        'can_edit' => $can_edit,
        'is_owner' => $is_owner,
        'voted' => $voted,
        'is_subbed' => $is_subbed ?? false,
		'is_subbed_comment' => $is_subbed_comment ?? false,
		'is_subbed_fav' => $is_subbed_fav ?? false,
        'likes' => $favs,
        'comments' => $replies,
        'soft_message' => $soft_message,
    ];
    return json_encode($data);
}

function view_creation(int $cid) {
    $con = Database::get(DB_NAME2);

    if (!isset($_SESSION['viewed_creation_ids'])) {
        $_SESSION['viewed_creation_ids'] = [];
    }

    if (!in_array($cid, $_SESSION['viewed_creation_ids'])) {
        $view_stmt = $con->prepare("UPDATE model SET views = views + 1 WHERE id = ? AND removed = 0");
        $view_stmt->execute([$cid]);
        $_SESSION['viewed_creation_ids'][] = $cid;
    }
}

function fetch_tags(int $cid) {
    $con = Database::get(DB_NAME2);
    $tags = [];

    $Tag_stmt = $con->prepare
    ("SELECT t.id, t.tag_name
    FROM tags t
    LEFT JOIN model_tags mt ON t.id = mt.tag_id
    WHERE mt.model_id = ?
    ");

    $Tag_stmt->execute([$cid]);
    $Tag_result = $Tag_stmt->get_result();

    while ($t = $Tag_result->fetch_assoc()) {
        $tags[$t['id']] = [
            'id' => $t['id'],
            'name' => $t['tag_name'],
        ];
    }

    $Tag_stmt->close();
    return $tags;
}

function fetch_comments(int $model_id, string $csrf): mixed {
    global $conn;
    global $conn2;
    global $current_user;

    if (empty($csrf) || !valid_csrf($csrf)) {
        return json_encode(["error" => ErrorRegistry::get('auth', 'bad_csrf')]);
    }

    $model_id = (int)$model_id;
    $id = loggedin() ? ((int)$current_user->id ?? 0) : false;
	$is_admin = loggedin() ? ((bool)$current_user->admin ?? false) : false;
	$notifications = new Notifications($conn2);

    if($id && $current_user->admin !== true) {
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
	$tree = [];
    $lookup = [];
    $fav_ids = [];

    $blocked = [];
    $privated = [];

    while ($row = $comResult->fetch_assoc()) {
        $rows[$row['id']] = $row;
    }

    if (!empty($rows)) {
        $userIds = array_column($rows, 'user');
        $users = User::getUsers($userIds);

        if(loggedin()) {
            //comment favs
            $stmtFavs = $conn->prepare("SELECT comment_id FROM comment_votes WHERE user_id = ? AND model_id = ?");
            $stmtFavs->bind_param("ii", $id, $model_id);
            $stmtFavs->execute();
            $favsRes = $stmtFavs->get_result();

            while ($fav = $favsRes->fetch_assoc()) {
                $fav_ids[(int)$fav['comment_id']] = true;
            }

            //user blocks
            $result = $conn2->query(
                "SELECT userid, profileid
                FROM user_blocks
                WHERE userid = $id
                OR profileid = $id"
            );

            while ($row = $result->fetch_assoc()) {
                $userid = (int)$row['userid'];
                $profileid = (int)$row['profileid'];

                if ($userid === $id) {
                    $blocked[$profileid]['you_blocked'] = true;
                }

                if ($profileid === $id) {
                    $blocked[$userid]['they_blocked'] = true;
                }
            }
        }

        foreach ($users as $c_user_id => $c_user) {
			if(!empty($c_user->profile)) {
				if($c_user->profile->private_profile && !User::isFollowing($c_user_id) && $c_user_id !== $id) {
					$privated[$c_user_id] = true;
				}
			}
        }
    }

	foreach($rows as $row) {
		$actions = [
			'reply' => false,
			'fav' => false,
			'edit' => false,
			'remove' => false,
			'report' => false,
		];

        $comment_id = $row['id'];
        $comment_votes = Numbers::format($row['votes']);
        $comment_og = htmlspecialchars($row['comment']);
        $comment_md = Formatter::render($comment_og);

		$c_user_id = (int)$row['user'] ?? 0;
        $userRow = $users[$c_user_id] ?? [];

		if(loggedin()) {
			$actions = [
				'reply' => true,
				'fav' => true,
				'edit' => trim($id) === trim($c_user_id) ? true : false,
				'remove' => (trim($id) === trim($c_user_id) || $is_admin) ? true : false,
				'report' => (trim($id) === trim($c_user_id) && !$is_admin) ? true : false,
			];
		}

        $date = time_ago(date('Y-m-d H:i:s', is_numeric($row['date']) ? $row['date'] : 0));
        $edited_at = null;

        //c_user_blablabla got annoying
        $c_user_data = [
            'name' => $userRow->username ?? '[deleted]',
            'admin' => $userRow->admin ?? false,
            'verified' => $userRow->profile->verified_badge ?? false,
            'is_op' => $row['is_op'] ?? 0,
            'picture' => $userRow->profile->picture_small ?? '/img/no_image.png',
            'banned' => $userRow->suspended ?? false,
            'removed' => !$userRow ? true : false,
            'private' => $privated[$c_user_id] ?? false,
			'you_blocked' => $blocked[$c_user_id]['you_blocked'] ?? false,
			'they_blocked' => $blocked[$c_user_id]['they_blocked'] ?? false,
        ];

        if(!$userRow || $userRow->deactive !== null) {
			$message = ErrorRegistry::get('comment', 'owner_not_found');
            $c_user_data['removed'] = true;
            $c_user_data['picture'] = null;

			$actions['reply'] = false;
			$actions['fav'] = false;
        }

		if($c_user_data['banned']) {
			// Dude. The guy that wrote that comment is fucked up bruah
            $message = ErrorRegistry::get('comment', 'owner_banned');
            $c_user_data['removed'] = true;
            $c_user_data['picture'] = null;

			$actions['reply'] = false;
			$actions['fav'] = false;
        }

        if($c_user_data['private']) {
            continue;
        }

        if((int)$row['edited_at'] !== 0) {
            $edited_at = time_ago(date('Y-m-d H:i:s', is_numeric($row['edited_at']) ? $row['edited_at'] : 0));
        }

        if(loggedin()) {
            if ($c_user_data['you_blocked'] && $c_user_data['they_blocked']) {
                $c_user_data['removed'] = true;
				$c_user_data['picture'] = null;
                $message = ErrorRegistry::get('comment', 'user_blocked_blocking');

				$actions['reply'] = false;
				$actions['fav'] = false;
            } elseif ($c_user_data['you_blocked']) {
                $message = ErrorRegistry::get('comment', 'user_blocking');
            } elseif ($c_user_data['they_blocked']) {
                $c_user_data['removed'] = true;
				$c_user_data['picture'] = null;
                $message = ErrorRegistry::get('comment', 'user_blocked');

				$actions['reply'] = false;
				$actions['fav'] = false;
            }
        }

        $valid = true;

        if($valid) {
            $lookup[$comment_id] = [
                'success' => true,
                'message' => $message ?? null,
                'id' => $comment_id,
                'user' => [
                    'id' => $c_user_id,
                    'removed' => $c_user_data['removed'],
                    'admin' => $c_user_data['admin'],
                    'verified' => $c_user_data['verified'],
                    'name' => $c_user_data['name'],
                    'is_op' => $c_user_data['is_op'],
                    'picture' => $c_user_data['picture'],
                ],
                'is_hidden' => $row['hidden'],
                'parent' => $row['parent'],
                'children' => [],
                'comment_md' => !$c_user_data['they_blocked'] ? $comment_md : null,
                'comment_og' => $comment_og,
                'date' => $date,
                'edited_at' => $edited_at,
                'votes' => $comment_votes,
                'voted' => isset($fav_ids[$comment_id]),
                'conversation_subbed' => $notifications->get_subscribers('comment_reply', $comment_id),
                'actions' => $actions,
            ];
        } else {
            $lookup[$comment_id] = [
                'success' => false,
                'message' => $message ?? null,
                'id' => $comment_id,
                'parent' => $row['parent'],
                'children' => [],
                'conversation_subbed' => $notifications->get_subscribers('comment_reply', $comment_id),
            ];
        }
        $message = null;
        $valid = null;
    }

    foreach ($lookup as $id => $comment) {
        if (!$comment['parent']) {
            $tree[$id] = &$lookup[$id];
        } else {
            if (isset($lookup[$comment['parent']])) {
                $lookup[$comment['parent']]['children'][] = &$lookup[$id];
            }
        }
    }

    $comResult->free();
    return json_encode($tree); 
}

/**
 * Finds comment by ID in threaded comment array
 */
function comment_find_by_id(array $comments, int $parent_id) {
    foreach ($comments as $comment) {
        if ($comment['id'] == $parent_id) {
            return $comment;
        }

        if (!empty($comment['children'])) {
            $found = comment_find_by_id($comment['children'], $parent_id);
            if ($found !== null) {
                return $found;
            }
        }
    }
    return null;
}

/**
 * Returns array of subscribers for comment
 */
function comment_subscribers(int $model_id) {
    global $conn2;
    $notifications = new Notifications($conn2);

    $subbed = $notifications->get_subscribers('comment', $model_id);
    $data = ['subbed' => $subbed];

    return $data;
}

function add_comment($model_id, $csrf, $comment, $parent) {
    global $current_user;

    $conn = Database::get(DB_NAME2);
    $conn2 = Database::get(DB_NAME);

    if (!valid_csrf($csrf)) {
        return ['success' => false, 'error' => ErrorRegistry::get('auth', 'bad_csrf')];
    }

    if(!loggedin() || !isset($current_user)) {
        return ['success' => false, 'error' => ErrorRegistry::get('comment', 'unauthed')];
    }

    $id = $current_user->id ?? 0;

    if($current_user->verify_token != NULL) {
        return ['success' => false, 'error' => ErrorRegistry::get('comment', 'unverified')];
    }

    if(!isset($_SESSION['comment'])) {
        $_SESSION['comment'] = [];
    }

    if(!isset($_SESSION['comment']['last'])) {
        $_SESSION['comment']['last'] = [];
    }

    if(isset($_SESSION['comment']['last']['time'])) {
        $lt = $_SESSION['comment']['last']['time'];
        $cd = 120;
        if((time() - $lt) < $cd) {
            $remaining = $cd - (time() - $lt);
            return ['success' => false, 'error' => ErrorRegistry::get('comment', 'cooldown_active', [$remaining])];
        }
    }

    if(strlen($comment) > 500) {
        return ['success' => false, 'error' => ErrorRegistry::get('comment', 'too_long')];
    }

    if(empty($comment)) {
        return ['success' => false, 'error' => ErrorRegistry::get('comment', 'too_short')];
    }

    $stmt4 = $conn->prepare("SELECT user FROM model WHERE id = ? AND removed = 0");
    $stmt4->bind_param("i", $model_id);
    $stmt4->execute();
    $result = $stmt4->get_result();

    if($result->num_rows === 0) {
        return ['success' => false, 'error' => ErrorRegistry::get('creation', 'not_found')];
    }

    $userid = $result->fetch_assoc()['user'] ?? null;

    $stmt_blocked = $conn2->prepare("SELECT * FROM user_blocks WHERE (userid = ? AND profileid = ?) OR (profileid = ? AND userid = ?)");
    $stmt_blocked->bind_param("iiii", $id, $userid, $id, $userid);
    $stmt_blocked->execute();
    $result = $stmt_blocked->get_result();

    if($result->num_rows !== 0) {
        $row = $result->fetch_assoc();

        if($row['userid'] === $id && $row['profileid'] === $userid) {
            return ['success' => false, 'error' => ErrorRegistry::get('creation', 'user_blocked')];
        } else if($row['profileid'] === $id && $row['userid'] === $userid) {
            return ['success' => false, 'error' => ErrorRegistry::get('creation', 'user_blocking')];
        }
    }

    if(!empty($parent)) {
        $stmt_reply = $conn->prepare("SELECT user FROM comments WHERE id = ? AND hidden = 0");
        $stmt_reply->bind_param("i", $parent);
        $stmt_reply->execute();
        $result = $stmt_reply->get_result();

        if($result->num_rows === 0) {
            return ['success' => false, 'error' => ErrorRegistry::get('comment', 'reply_not_found')];
        }

        $c_user = $result->fetch_assoc()['user'];

        $stmt_blocked = $conn2->prepare("SELECT * FROM user_blocks WHERE (userid = ? AND profileid = ?) OR (profileid = ? AND userid = ?)");
        $stmt_blocked->bind_param("iiii", $id, $c_user, $id, $c_user);
        $stmt_blocked->execute();
        $result = $stmt_blocked->get_result();

        if($result->num_rows !== 0) {
            $row = $result->fetch_assoc();

            if($row['userid'] === $id && $row['profileid'] === $c_user) {
                return ['success' => false, 'error' => 'You have blocked the user you are trying to reply to.'];
            } else if($row['profileid'] === $id && $row['userid'] === $c_user) {
                return ['success' => false, 'error' => 'The person you are trying to reply to has you blocked.'];
            } else {
                return ['success' => false, 'error' => 'You or the user you are replying to have blocked one or the other.'];
            }
        }
    }

	$date = time();
    $is_op = $id === $userid ? 1 : 0;
    $comment = Formatter::prepare($comment);

    $sql = "INSERT INTO comments (user, model, comment, parent, date, is_op) VALUES (?, ?, ?, ?, ?, ?) LIMIT 1";
    $stmt2 = $conn->prepare($sql);
    $stmt2->bind_param("iisisi", $id, $model_id, $comment, $parent, $date, $is_op);

    if ($stmt2->execute()) {
        $last_id = $conn->insert_id;
        $_SESSION['comment']['last']['id'] = $last_id;
        $_SESSION['comment']['last']['time'] = $date;
        $stmt2->close();

        $stmtCountUpd = $conn->prepare("UPDATE model SET replies = replies + 1 WHERE id = ?");
        $stmtCountUpd->bind_param("i", $model_id);
        $stmtCountUpd->execute();
        $stmtCountUpd->close();

        $stmtCountGet = $conn->prepare(
            "SELECT m.replies, c.votes
            FROM model m
            INNER JOIN comments c ON c.id = ?
            WHERE m.id = ?
            AND m.removed = 0
            AND c.hidden = 0"
        );

        $stmtCountGet->bind_param("ii", $last_id, $model_id);
        $stmtCountGet->execute();
        $result = $stmtCountGet->get_result();
        $reply_count = $result->fetch_assoc()['replies'] ?? 0;
        $favs_count = $result->fetch_assoc()['votes'] ?? 0; //the rare case that someone favorites a comment right after publish. it is (technically) possible.

        $notifications = new Notifications($conn2);
        $notifications->subscribe($id, 'comment', $model_id);
        $notifications->subscribe($id, 'comment_reply', $last_id);
        $notifications->notify_subscribers('comment', $model_id, $id);

        if($parent) {
            $notifications->subscribe($id, 'comment_reply', $parent);
            $notifications->notify_subscribers('comment_reply', $parent, $id);
        }

        return [
            'success' => true,
            'message' => 'Comment sent!',
            'comment' => [
                'id' => $last_id,
                'text' => Formatter::render($comment),
                'text_og' => htmlspecialchars($comment),
                'model' => $model_id,
                'parent' => $parent,
                'date' => time_ago(date('Y-m-d H:i:s', $date)),
                'favs' => $favs_count
            ],
            'thread' => [
                'replies' => $reply_count,
                'creation' => [
                    'comments' => json_decode(fetch_comments($model_id, $_SESSION['csrf']), true),
                ],
            ],
            'user' => [
                'id' => $id,
                'picture' => $current_user->profile->picture,
                'name' => $current_user->username ?? null,
                'admin' => $current_user->admin ?? false,
                'op' => $is_op,
            ],
        ];
    } else {
        $stmt2->close();
        return ['success' => false, 'error' => ErrorRegistry::get('comment', 'generic')];
    }
}

function edit_comment($comment_text, $comment_id, $csrf) {
    global $current_user, $conn;
    if (valid_csrf($csrf)) {
        if (loggedin() && isset($current_user)) {
            $comment_text = isset($comment_text) ? Formatter::prepare($comment_text) : null;
            $comment_id = isset($comment_id) ? $comment_id : null;

            if(!empty($comment_id) || !empty($comment_text)) {
                $stmt = $conn->prepare("SELECT id, hidden, user, model FROM comments WHERE id = ? AND hidden = 0");
                $stmt->bind_param("i", $comment_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    if(trim($row['user']) === trim($current_user->id)) {
                        $date = time();
                        $stmt2 = $conn->prepare("UPDATE comments SET comment = ?, edited_at = ? WHERE id = ?");
                        $stmt2->bind_param("ssi", $comment_text, $date, $comment_id);

                        if ($stmt2->execute()) {
                            echo json_encode([
                                'success' => true,
                                'comment' => [
                                    'text' => Formatter::render($comment_text),
                                    'text_og' => $comment_text,
                                    'edited_at' => time_ago(date('Y-m-d H:i:s', is_numeric($date) ? $date : 0)),
                                ],
                            ]);
                        } else {
                            echo json_encode(['success' => false, 'error' => ErrorRegistry::get('db', 'query_fail')]);
                        }
                    } else {
                        echo json_encode(['success' => false, 'error' => ErrorRegistry::get('auth', 'bad_auth')]);
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => ErrorRegistry::get('comment', 'not_found')]);
                }
            } else {
                echo json_encode(['success' => false, 'error' => ErrorRegistry::get('comment', 'bad_id')]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => ErrorRegistry::get('auth', 'unauthed')]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => ErrorRegistry::get('auth', 'bad_csrf')]);
    }
    exit;
}

function preview_comment($comment_text, $csrf) {
    global $current_user;
    if (valid_csrf($csrf)) {
        if (loggedin() && isset($current_user)) {
            $comment_text = isset($comment_text) ? Formatter::prepare($comment_text) : null;

            if(!empty($comment_text)) {
                echo json_encode([
                    'success' => true,
                    'comment' => [
                        'text' => Formatter::render($comment_text),
                        'edited_at' => 'Now',
                        'favs' => 1,
                    ],
                    'user' => [
                        'id' => $current_user->id,
                        'url' => '/@' . urlencode($current_user->username),
                        'name' => $current_user->username,
                        'picture' => $current_user->profile->picture,
                        'admin' => $current_user->admin,
                    ],
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => ErrorRegistry::get('comment', 'too_short')]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => ErrorRegistry::get('auth', 'unauthed')]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => ErrorRegistry::get('auth', 'bad_csrf')]);
    }
    exit;
}
?>