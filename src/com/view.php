<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/profile.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/notifications.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/const/forum.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/const/toMarkdown.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/const/utils/time.php";

if (loggedin()) {
    $id = $current_user->id ?? 0;
}

$conn = Database::get(DB_NAME3);
$conn2 = Database::get(DB_NAME);
$post_id = htmlspecialchars($_GET['id']);

$post_row = gettopic($conn, $post_id);

if(isset($post_row['success']) && $post_row['success'] === true) {
	$title = $post_row['title'] ?? 'untitled';
	$userid = $post_row['user']['id'] ?? 0;
	$username = $post_row['user']['name'] ?? '[deleted]';
	$date = $post_row['date'] ?? 0;
	$views = $post_row['views'] ?? 0;
	$category = $post_row['category'] ?? null;
	$edit_date = $post_row['edit_date'] ?? null;

	if (isset($_POST['comment_edit'])) {
		header('Content-Type: Application/JSON');

		if ($conn->connect_error) {
			exit("Database connection failed.");
		}

		$commentid = (int)$_POST['commentid'];
		if (!isset($commentid)) {
			echo json_encode(['success' => false, 'message' => 'Invalid message ID.']);
			exit;
		}

		$content = $_POST['content'];
		if (!isset($content)) {
			echo json_encode(['success' => false, 'message' => 'Message shall contain text.']);
			exit;
		}

		$sql = "SELECT userid FROM messages WHERE id = ? AND deleted_at IS NULL LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("i", $commentid);
		$stmt->execute();
		$result = $stmt->get_result();
		$mrow = $result->fetch_assoc();
		$stmt->close();

		if (!$mrow) {
			echo json_encode(['success' => false, 'message' => 'Message with that ID does not exist.']);
			exit;
		}

		if (!loggedin()) {
			echo json_encode(['success' => false, 'message' => 'You are not logged in.']);
			exit;
		}

		if ((int)$mrow['userid'] !== (int)$id) {
			echo json_encode(['success' => false, 'message' => 'You are not allowed to edit this message.']);
			exit;
		}

		$date = date('Y-m-d H:i:s', time());
		$edit_sql = "UPDATE messages SET content = ?, edited = ? WHERE id = ? LIMIT 1";
		$edit_stmt = $conn->prepare($edit_sql);
		$edit_stmt->bind_param("ssi", $content, $date, $commentid);
		$edit_result = $edit_stmt->execute();
		$edit_stmt->close();

		if ($edit_result) {
			echo json_encode(['success' => true]);
			exit;
		}
	}

	if (isset($_POST['comment_delete'])) {
		if ($conn->connect_error) {
			exit("Database connection failed.");
		}

		$commentid = (int)$_POST['commentid'];
		if (!isset($commentid)) {
			echo json_encode(['success' => false, 'message' => 'Invalid message ID.']);
			exit;
		}

		$sql = "SELECT userid FROM messages WHERE id = ? AND (parent IS NOT NULL AND parent != 0) LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("i", $commentid);
		$stmt->execute();
		$result = $stmt->get_result();
		$mrow = $result->fetch_assoc();
		$stmt->close();

		if (!$mrow) {
			echo json_encode(['success' => false, 'message' => 'Message with that ID does not exist.']);
			exit;
		}

		if (!loggedin() || !isset($current_user)) {
			echo json_encode(['success' => false, 'message' => 'You are not logged in.']);
			exit;
		}

		if (!$current_user->admin || (int)$mrow['userid'] !== (int)$id) {
			echo json_encode(['success' => false, 'message' => 'You are not allowed to delete this message.']);
			exit;
		}

		$delete_sql = "UPDATE messages SET deleted_at = NOW() WHERE id = ? LIMIT 1";
		$delete_stmt = $conn->prepare($delete_sql);
		$delete_stmt->bind_param("i", $commentid);
		$delete_result = $delete_stmt->execute();
		$delete_stmt->close();

		if ($delete_result) {
			echo json_encode(['success' => true]);
			exit;
		}
	}

	if (isset($_POST['comment'])) {
		header('Content-Type: application/json');

		$comment = isset($_POST['commentbox']) ? $_POST['commentbox'] : null;
		if ($conn->connect_error) {
			echo json_encode(['success' => false, 'message' => 'Database connection has failed']);
			exit;
		}

		if ($comment === "" || $comment === null) {
			echo json_encode(['success' => false, 'message' => 'Message shall contain text.']);
			exit;
		}

		$stmt = $conn->prepare("SELECT 1 FROM messages WHERE id = ? AND (parent IS NULL OR parent = 0) LIMIT 1");
		$stmt->bind_param("i", $post_id);
		$stmt->execute();
		$result = $stmt->get_result();
		$mrow = $result->fetch_assoc();
		$stmt->close();

		if (!$mrow) {
			echo json_encode(['success' => false, 'message' => 'Message with that ID does not exist.']);
			exit;
		}

		$username = isset($_POST['username']) ? trim($_POST['username']) : null;

		if ($username) {
			$_SESSION['forum_anonymous_username'] = htmlspecialchars($username);
		}

		if (isset($category) && $category == "nolist" && loggedin() === false) {
			if (!$username) {
				echo json_encode(['success' => false, 'message' => 'No username provided.']);
				exit;
			}

			$date = date("Y-m-d H:i:s");
			$sql = "INSERT INTO messages (username, parent, content, timestamp) VALUES (?, ?, ?, ?)";
			$stmt2 = $conn->prepare($sql);
			$stmt2->bind_param("siss", $username, $post_id, $comment, $date);
		} else {
			if (!loggedin()) {
				echo json_encode(['success' => false, 'message' => 'You are not logged in.']);
				exit;
			}

			$date = date("Y-m-d H:i:s");
			$sql = "INSERT INTO messages (userid, parent, content, timestamp) VALUES (?, ?, ?, ?)";
			$stmt2 = $conn->prepare($sql);
			$stmt2->bind_param("iiss", $id, $post_id, $comment, $date);
		}

		if (!$stmt2->execute()) {
			echo json_encode(['success' => false, 'message' => 'An error has occured. Please try again later.']);
			exit;
		}

		$reply_id = $conn->insert_id;
		$stmt2->close();

		$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
		$offset = ($page - 1) * 10;
		$count_result = $conn->query("SELECT COUNT(*) as reply_count FROM messages WHERE parent = '$post_id'");
		$reply_count = $count_result->fetch_assoc()['reply_count'];
		$total_pages = ceil($reply_count / 10);

		$time = time();
		$sql = "UPDATE messages SET last_active_time = ?, last_page = ?, last_posted = ? WHERE id = ?";
		$stmt3 = $conn->prepare($sql);
		$stmt3->bind_param("iiii", $time, $total_pages, $id, $post_id);

		$notifications = new Notifications($conn2);
		$notifications->subscribe($id, 'forum_reply', $post_id);
		$notifications->notify_subscribers('forum_reply', $post_id, $id);

		$stmt3->close();

		echo json_encode(['success' => true, 'id' => $reply_id ?? null]);
		exit;
	}
}

	$pageTitle = $title;
	$pageDesc = 'Talk on the Gr8Brik community forums';
	$pageCanonical = appSpace->__urls->app . '/topic/' . ($post_id ?? null);
	require_once $_SERVER['DOCUMENT_ROOT'] . '/const/includes/header.php';
?>

		<script type="text/javascript" src="/lib/forum.js"></script>

<?php
    include $_SERVER['DOCUMENT_ROOT'] . '/navbar.php';

    $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
    $page = $page < 1 ? 1 : (int)$page;
    $limit = 10;
    $offset = ($page - 1) * $limit;

	$counts = getreplycount($post_id);
	$reply_count = $counts['replies'];
	$total_pages = $counts['total_pages'];

    $randomWord = RandomWord();
?>

    <?php if (isset($post_row['error'])) : ?>
        <div class="message w3-padding w3-round w3-red"><?= $post_row['error'] ?></div><br /><br />
        <?php exit; ?>
    <?php endif; ?>

	<?php if ($category === "nolist") : ?>
       	<p id='unlistedwarning'>This forum is unlisted, only people with the link can view it.</p><br />
    <?php endif; ?>

    <div class='gr8-theme w3-container w3-light-grey w3-card-4 w3-round-small'>
        <h2><?= $title ?></h2>
        <h4>By <a href='/@<?= urlencode($username) ?>'>@<?= $username ?></a> on <?= $date ?><?= $edit_date ? ", edited {$edit_date}" : null ?>. <?= $views ?> total views.</h4>
        <h4><?= $reply_count ?> replies, <?= $total_pages ?> pages, on page <?= $page ?>, in <?= $category ?></h4>
    </div><br />

    <?php
		$replies = getreplies($post_id, $page);
		$rows = $replies['rows'] ?? [];
		$blocked = $replies['blocked'] ?? [];

		if (!empty($rows)) {
			foreach($rows as $row) {
				$c_comment = $row['content'] ?? '[empty]';
				$c_date = $row['timestamp'] ?? time();
				$decoded_comment = htmlspecialchars($c_comment);
				$c_edited = $row['edited'] ?? null;

				$c_user = $row['userid'] ?? null;
				$c_username = htmlspecialchars($row['username'] ?? '[deleted]');

				$c_user_exists = true;
				if(!$c_user) {
					$c_user_exists = false;
					$isAdmin = false;
					$hasBadge = false;
					$pfp = '/img/no_image.png';
				} else {
					$isAdmin = $row['user_admin'] ?? false;
					$hasBadge = $row['verified_badge'] ?? false;
					$pfp = $row['picture'] ?? '/img/no_image.png';
				}

				if ($c_user_exists) {
					$user_post_count = userreplycount($c_user) ?? 0;
					$c_user_blocked = isset($blocked[$c_user]['they_blocked']) ? true : false;

					if ($c_user_blocked) {
						echo "<p>" . ErrorRegistry::get('topic_reply', 'user_blocked') . "</p>";
						continue;
					}
				}
    		?>

			<div id="comment-<?php echo $row['id'] ?>" class="reply w3-row" style="display:flex;width:100%;">
				<div class="gr8-theme w3-card-2 w3-light-grey w3-padding-small w3-round-small w3-margin-right w3-col m3 l3">
					<img id="pfp" class="w3-round w3-border w3-border-grey" src="<?php echo $pfp ?>"><br />
					<?php if ($c_user_exists) { ?>
						<a href="/@<?php echo urlencode($c_username) ?>">
					<?php } ?>
						<span class="<?php echo $isAdmin ? "w3-text-red" : null ?>" style="text-overflow: ellipsis;">
							<?php echo $c_username ?>
						</span>
					<?php if ($c_user_exists) { ?>
						</a>
					<?php } ?>
					<?php echo $hasBadge ? "&check;" : null ?>
					<br />
					<time title="<?php echo $c_date ?>" datetime="<?php echo $c_date ?>">Posted <?php echo time_ago($c_date) ?></time><br />
					<?php if(!empty($c_edited)) { ?>
						<time title="<?php echo $c_edited ?>" datetime="<?php echo $c_edited ?>">Edited <?php echo time_ago($c_edited) ?></time><br />
					<?php }
					if ($c_user_exists) { ?>
						<span><?php echo $user_post_count ?> total posts</span>
					<?php } ?>
				</div>
				<div class="gr8-theme w3-display-container w3-card-2 w3-light-grey w3-padding-small w3-round-small w3-col m9 l9">
					<pre class="comment-text"><?php echo Formatter::render($decoded_comment) ?></pre><br />
					<form class="edit w3-hide">
						<textarea class="edit-textarea"><?php echo $decoded_comment ?></textarea><br />
						<button class="save-btn w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo">Save</button>
						<button class="cancel-btn w3-btn w3-white w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-grey">Cancel</button>
					</form>
					<span class="w3-display-bottomleft">
						<?php
						if (loggedin() && isset($current_user)) {
							if ($row['parent'] === (int)$post_id && ($current_user->admin || trim($current_user->id) === trim($c_user))) {
								?>
								<div class="delete gr8-theme w3-hide w3-card-2 w3-light-grey w3-round-small w3-padding-small w3-margin-bottom w3-border w3-border-grey">
									<p>Are you sure you want to delete this reply?</p>
									<button class="confirm-delete-btn w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink" data-id="<?php echo $row['id'] ?>">Yes</button>
									<button class="cancel-delete-btn w3-btn w3-white w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-grey">Cancel</button>
								</div>
								<button class="delete-btn w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink" data-id="<?php echo $row['id'] ?>"><i class="fa fa-trash" aria-hidden="true"></i></button>
								<?php
							}

							if (trim($current_user->id) === trim($c_user)) {
								echo '<button class="edit-btn w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" data-id="' .  $row['id'] . '"><i class="fa fa-pencil" aria-hidden="true"></i></button>';
							}
						}
						?>
					</span>
				</div>
			</div><br />

        <?php } ?>
			<a class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo" href="?p=<?= $page - 1 ?>">Back</a>
			<a class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo" href="?p=<?= $page + 1 ?>">Next</a>
	<?php } else { ?>
		<p>No replies yet.</p><br />
		<a class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo" href="?p=<?= $page - 1 ?>">Back</a>
	<?php } ?>

    <hr />

    <?php if ($category === "pinnedLocked" || $category === "locked") : ?>
        <b id='commentboxcontainer'>This conversation is locked. New replies cannot be posted.</b><br />
	<?php elseif (loggedin()) : ?>
		<a href='#commentboxcontainer'>permalink to replybox</a><br />
		<form id='commentboxcontainer' method='post' action=''>
			<textarea name='commentbox' placeholder='<?php echo $randomWord->Display ?>' rows='4' cols='50'></textarea><br />
		</form>

		<button id="post-reply" name='comment' class='w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo'>
			<span id="post-reply-text"><i class="fa fa-comment-o" aria-hidden="true"></i> Reply</span>
		</button>

		<button id="attach-image" class="w3-btn w3-white w3-hover-opacity w3-round-small w3-border w3-border-grey">
			<span id="attach-image-btn-text"><i class="fa fa-image" aria-hidden="true"></i> Attach image</span>
		</button>
		<br />

		<form id="attach-upload" method="post" action="">
			<input type="file" name="imagefile" id="imagefile">
		</form>
	<?php else : ?>
		<b id='commentboxcontainer'>Please <a href='../acc/login'>login</a> to post a reply.</b><br />
	<?php endif; ?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/const/includes/linkbar.php' ?>