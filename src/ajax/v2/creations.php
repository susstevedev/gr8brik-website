<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/const/creation.php';

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

if (isset($_POST['save_build_v2'])) {
    header('Content-Type: application/json');

    $desc = $_POST['desc'] ?: '';
    $name = $_POST['name'] ?: "Untitled Creation";
    $modelJson = $_POST['creation'] ?: null;
    $visible = $_POST['visibility'] ?: null;
    $build_id = $_POST['build_id'] ?: null;
    $screenshot_data = $_POST['screenshot'] ?: null;
    $can_edit = $_POST['can_edit'] ?: 0;

    echo json_encode(save_creation($build_id, $name, $desc, $modelJson, $visible, $screenshot_data, $can_edit));
    exit;
}

if(isset($_GET['fetch']) || isset($_GET['getCreation'])) {
    header('Content-Type: application/json');

    $model_id = $_GET['buildId'] ?? $_GET['id'];
    $data = fetch_build($model_id, $_SESSION['csrf']);
    echo $data;
    exit;
}

if(isset($_GET['tags'])) {
    header('Content-Type: application/json');
    $model_id = isset($_GET['buildId']) ? (int)$_GET['buildId'] : 0;
    echo json_encode(fetch_tags($model_id));
    exit;
}

if(isset($_GET['comments'])) {
    header('Content-Type: application/json');
    $model_id = isset($_GET['buildId']) ? (int)$_GET['buildId'] : 0;
    $comment_data = fetch_comments($model_id, $_SESSION['csrf']);
    echo json_encode($comment_data);
    exit;
}

if(isset($_POST['comment'])) {
    header('Content-Type: application/json');

    $comment = isset($_POST['commentbox']) ? $_POST['commentbox'] : null;
    $parent = isset($_POST['parent']) ? (int)$_POST['parent'] : 0;
    $csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null;
    $model_id = isset($_POST['buildId']) ? $_POST['buildId'] : null;

    echo json_encode(add_comment($model_id, $csrf, $comment, $parent));
    exit;
}

if (isset($_POST['edit_comment'])) {
    header('Content-Type: application/json');

    $comment_text = isset($_POST['commentbox']) ? $_POST['commentbox'] : null;
    $comment_id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $csrf = $_POST['csrf_token'];

    json_encode(edit_comment($comment_text, $comment_id, $csrf));
    exit;
}

if (isset($_POST['comment_preview']) && isset($_POST['commentbox'])) {
    header('Content-Type: application/json');

    $comment_text = isset($_POST['commentbox']) ? $_POST['commentbox'] : null;
    $csrf = $_POST['csrf_token'];

    json_encode(preview_comment($comment_text, $csrf));
    exit;
}

if (loggedin() && isset($current_user)) {
    $id = $current_user->id ?? 0;

    if (isset($_POST['downvote'])) {
        header('Content-Type: application/json');

        $model_id = isset($_POST['model_id']) ? $_POST['model_id'] : null;
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

        $model_id = isset($_POST['model_id']) ? $_POST['model_id'] : null;
        $likes = $model_user = $vote_count = null;

        $stmt = $conn->prepare("SELECT user, likes FROM model WHERE id = ? AND removed = 0 LIMIT 1");
        $stmt->bind_param("i", $model_id);
        $stmt->execute();
        $stmt->bind_result($model_user, $likes);
        $stmt->fetch();
        $stmt->close();

        if (!$model_id) {
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

        $model_id = isset($_POST['model_id']) ? $_POST['model_id'] : null;
		$type = isset($_POST['type']) ? $_POST['type'] : null;
        $model_user = null;
        $likes = null;

		if($type !== 'comment' && $type !== 'creation_fav') {
            http_response_code(400);
			echo json_encode(['success' => false]);
            exit;
		}

        $stmt = $conn->prepare("SELECT user, likes FROM model WHERE id = ? AND removed = 0 LIMIT 1");
        $stmt->bind_param("i", $model_id);
        $stmt->execute();
        $stmt->bind_result($model_user, $likes);
        $stmt->fetch();
        $stmt->close();

        if (!$model_id) {
            echo json_encode(['success' => false, 'error' => ErrorRegistry::get('creation', 'not_found')]);
            exit;
        }

        if($current_user->verify_token != NULL) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => ErrorRegistry::get('auth', 'unverified')]);
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

        $model_id = isset($_POST['model_id']) ? $_POST['model_id'] : null;
		$type = isset($_POST['type']) ? $_POST['type'] : null;
        $model_user = null;
        $likes = null;

		if($type !== 'comment' && $type !== 'creation_fav') {
			http_response_code(400);
			echo json_encode(['success' => false]);
            exit;
		}

        $stmt = $conn->prepare("SELECT user, likes FROM model WHERE id = ? AND removed = 0 LIMIT 1");
        $stmt->bind_param("i", $model_id);
        $stmt->execute();
        $stmt->bind_result($model_user, $likes);
        $stmt->fetch();
        $stmt->close();

        if (!$model_id) {
            echo json_encode(['success' => false, 'error' => ErrorRegistry::get('creation', 'not_found')]);
            exit;
        }

        if($current_user->verify_token != NULL) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => ErrorRegistry::get('auth', 'unverified')]);
            exit;
        }

        $id = $current_user->id;
        $notifications = new Notifications($conn2);
        $notifications->remove_subscriber($type, $model_id, $id);

		echo json_encode(['success' => true, 'type' => $type, 'text' => "Subscribe to " . $SUB_TYPES[$type], "textParent" => 'Subscribe']);
        exit;
    }

	if(isset($_POST['comment_favs'])) {
		header('Content-Type: application/json');

		if (!isset($_POST['comment_id']) || !is_numeric($_POST['comment_id'])) {
			echo json_encode(['error' => ErrorRegistry::get('comment', 'bad_id')]);
			exit;
		}

        $comment_user = null;
        $model_id = null;
        $comment_id = null;
		$count = 0;

		$stmt = $conn->prepare("SELECT id, user, model FROM comments WHERE id = ? AND hidden = 0 LIMIT 1");
		$stmt->bind_param("i", $_POST['comment_id']);
		$stmt->execute();
		$stmt->bind_result($comment_id, $comment_user, $model_id);
		$stmt->fetch();
		$stmt->close();

		if (!isset($comment_id) || !isset($comment_user) || !isset($model_id)) {
			echo json_encode(['error' => ErrorRegistry::get('comment', 'not_found')]);
			exit;
		}

		if (isset($_POST['fav'])) {
			$alr_stmt = $conn->prepare("SELECT COUNT(*) as vote_count FROM comment_votes WHERE comment_id = ? AND user_id = ?");
			$alr_stmt->bind_param("ii", $comment_id, $id);

			if($alr_stmt->execute()) {
				$alr_res = $alr_stmt->get_result();
				$count = $alr_res->fetch_assoc()['vote_count'] ?? 0;
				$alr_stmt->close();
			}

			if ($count > 0) {
				echo json_encode(['error' => ErrorRegistry::get('comment', 'comment_faved')]);
				exit;
			}

			if($current_user->verify_token != NULL) {
				http_response_code(401);
				echo json_encode(['error' => ErrorRegistry::get('auth', 'unverified')]);
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
				echo json_encode(['error' => ErrorRegistry::get('comment', 'comment_fav_not_allowed')]);
				exit;
			}

			$stmt = $conn->prepare("INSERT INTO comment_votes (comment_id, user_id, model_id) VALUES (?, ?, ?)");
			$stmt->bind_param("iii", $comment_id, $id, $model_id);

			$stmt_upd = $conn->prepare("UPDATE comments SET votes = votes + 1 WHERE id = ?");
			$stmt_upd->bind_param("i", $comment_id);

			if ($stmt->execute() && $stmt_upd->execute()) {
				$stmt_count = $conn->prepare("SELECT votes FROM comments WHERE id = ?");
				$stmt_count->bind_param("i", $comment_id);
				if($stmt_count->execute()) {
					$count_res = $stmt_count->get_result();
					$count = $count_res->fetch_assoc()['votes'] ?? 0;
					$stmt_count->close();
				} else {
					echo json_encode(['success' => false, 'error' => ErrorRegistry::get('db', 'query_fail')]);
					exit;
				}

				echo json_encode(['success' => true, 'message' => 'Comment favorited', 'count' => number_format($count ?? 0)]);
			} else {
				echo json_encode(['success' => false, 'error' => ErrorRegistry::get('comment', 'comment_fav_fail')]);
			}
			$stmt->close();
			exit;
		} else if (isset($_POST['unfav'])) {
			$alr_stmt = $conn->prepare("SELECT COUNT(*) as vote_count FROM comment_votes WHERE comment_id = ? AND user_id = ?");
			$alr_stmt->bind_param("ii", $comment_id, $id);

			if($alr_stmt->execute()) {
				$alr_res = $alr_stmt->get_result();
				$count = $alr_res->fetch_assoc()['vote_count'] ?? 0;
				$alr_stmt->close();
			}

			if ($count === 0) {
				echo json_encode(['success' => false, 'error' => ErrorRegistry::get('comment', 'comment_not_faved')]);
				exit;
			}

			$stmt = $conn->prepare("DELETE FROM comment_votes WHERE comment_id = ? AND user_id = ?");
			$stmt->bind_param("ii", $comment_id, $id);

			$stmt_upd = $conn->prepare("UPDATE comments SET votes = votes - 1 WHERE id = ?");
			$stmt_upd->bind_param("i", $comment_id);

			if ($stmt->execute() && $stmt_upd->execute()) {
				$stmt_count = $conn->prepare("SELECT votes FROM comments WHERE id = ?");
				$stmt_count->bind_param("i", $comment_id);

				if($stmt_count->execute()) {
					$count_res = $stmt_count->get_result();
					$count = $count_res->fetch_assoc()['votes'] ?? 0;
					$stmt_count->close();
				} else {
					echo json_encode(['success' => false, 'error' => ErrorRegistry::get('db', 'query_fail')]);
					exit;
				}

				echo json_encode(['success' => true, 'message' => 'Comment unfavorited', 'count' => number_format($count ?? 0)]);
			} else {
				echo json_encode(['success' => false, 'error' => ErrorRegistry::get('comment', 'comment_unfav_fail')]);
			}
			$stmt->close();
			exit;
		} else {
            echo json_encode(['success' => false, 'error' => ErrorRegistry::get('db', 'query_fail')]);
		    exit;
        }
	}
}
?>