<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/notifications.php';

if (loggedin()) {
    $id = $current_user->id;
}

$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);

$post_id = htmlspecialchars($_GET['id']);

if ($conn->connect_error) {
    exit($conn->connect_error);
}

$stmt = $conn->prepare("SELECT id, userid, username, title, timestamp, edited, views, status FROM messages WHERE id = ? AND (parent IS NULL OR parent = 0) AND deleted_at IS NULL LIMIT 1");
$stmt->bind_param("i", $post_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows <= 0) {
    http_response_code(404);
    $error = "<b>This conversation does not exist.</b>";
}

$row = $result->fetch_assoc();
$stmt->close();

$userid = $row['userid'];
$title = $row['title'];
$date = $row['timestamp'];
$views = $row['views'];
$category = $row['status'];

if (!empty($row['edited'])) {
    $edit_date = $row['edited'];
} else {
    $edit_date = $row['timestamp'];
}

$conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

if (!$row['username']) { //check for anonymous posting
    $user = User::getUser($userid);
    $isAdmin = $user->admin === '1' ? "w3-text-red" : "";
    $username = $user->username;
} else {
    $isAdmin = false;
    $username = $row['username'];
}

if (!isset($_SESSION['viewed_post_ids'])) {
    $_SESSION['viewed_post_ids'] = [];
}

if (!in_array($post_id, $_SESSION['viewed_post_ids'])) {
    $view_stmt = $conn->prepare("UPDATE messages SET views = views + 1 WHERE id = ?");
    $view_stmt->bind_param("i", $post_id);
    $view_stmt->execute();
    $_SESSION['viewed_post_ids'][] = $post_id;
}

if ($category === "deleted") {
    $error = "<b>This conversation has been removed because it violated our <a href='/rules'>rules</a>.</b>";
}

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
    $conn->close();

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

    if (!loggedin()) {
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
    $conn->close();

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
        if (!$loggedin) {
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
    $conn3 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

    $notifications = new Notifications($conn3);
    $notifications->subscribe($id, 'forum_reply', $post_id);
    $notifications->notify_subscribers('forum_reply', $post_id, $id);

    $stmt3->close();

    echo json_encode(['success' => true, 'id' => $reply_id ?? null]);
	exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title><?php echo $title ?></title>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/header.php' ?>
	<script type="text/javascript" src="/lib/forum.js"></script>
</head>

<body class="w3-light-blue w3-container">
    <?php
    include $_SERVER['DOCUMENT_ROOT'] . '/navbar.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . "/com/bbcode.php";
    require_once $_SERVER['DOCUMENT_ROOT'] . "/ajax/time.php";
    $bbcode = new BBCode;

    $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
    $page = $page < 1 ? 1 : (int)$page;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $count_sql = "SELECT COUNT(*) as reply_count FROM messages WHERE parent = '$post_id'";
    $count_result = $conn->query($count_sql);
    $count_row = $count_result->fetch_assoc();
    $reply_count = $count_row['reply_count'];

    $total_pages = ceil($reply_count / $limit);

    if (isset($error)) {
        echo '<div class="message w3-padding w3-round w3-red">' . $error . '</div><br /><br />';
        exit;
    }

    if ($category === "nolist") {
        echo "<b id='unlistedwarning'>This forum is unlisted, only people with the link can view it.</b><br />";
    }
    ?>

    <div class='gr8-theme w3-container w3-light-grey w3-card-4 w3-round-small'>
        <h2><?php echo $title ?></h2>
        <h4>By <a href='/user/<?php echo $userid ?>'><?php echo $username ?></a> on <?php echo $date ?>, edited <?php echo $edit_date . ". " . $views ?> total views.</h4>
        <h4><?php echo $reply_count ?> replies, <?php echo $total_pages ?> pages, on page <?php echo $page ?>, in <?php echo $category ?></h4>
    </div><br />

    <?php
    $sql = "SELECT * FROM messages WHERE (parent = $post_id OR id = $post_id) AND deleted_at IS NULL LIMIT $limit OFFSET $offset";
    $comResult = $conn->query($sql);
    $rows = [];
    $blocked = [];

    if ($comResult->num_rows > 0) {
        while ($row = $comResult->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    if (!empty($rows)) {
        $userIds = array_column($rows, 'user');
        $users = User::getUsers($userIds);

        if (loggedin()) {
            $userId = $current_user->id;
            $result = $conn2->query("SELECT userid, profileid FROM user_blocks WHERE userid = $userId OR profileid = $userId");

            while ($row = $result->fetch_assoc()) {
                $block_userid = (int)$row['userid'];
                $block_profileid = (int)$row['profileid'];

                if ($block_userid === $userId) {
                    $blocked[$block_profileid]['you_blocked'] = true;
                }

                if ($block_profileid === $userId) {
                    $blocked[$userid]['they_blocked'] = true;
                }
            }
        }

        foreach($rows as $row) {
            $c_user = $row['userid'] ?? null;
            $c_comment = $row['content'] ?? '[empty]';
            $c_date = $row['timestamp'] ?? time();
            $decoded_comment = htmlentities($c_comment, ENT_QUOTES, 'UTF-8');
            $c_edited = $row['edited'] ?? null;

            $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
            if ($conn2->connect_error) {
                exit($conn2->connect_error);
            }

            $c_user_o = $users[$c_user] ?? User::getUser($c_user);
            $c_username = htmlspecialchars($c_user_o->username);
            $isAdmin = $c_user_o->admin === 1 ? "w3-text-red" : "";
            $pfp = $c_user_o->picture;

            $c_user_exists = !User::isDeleted($c_user);

			if ($c_user_exists) {
				$user_post_count_result = $conn->query("SELECT COUNT(*) as reply_count FROM messages WHERE userid = '$c_user' AND deleted_at IS NULL");
				$user_post_count = $user_post_count_result->fetch_assoc()['reply_count'] ?? 0;

                if(loggedin()) {
                    //todo add message for if you are also blocking
                    //not that hard will probably be done soon
                    if ($blocked[$c_user]['they_blocked'] ?? false) {
                        continue;
                    }
                }
			}

    ?>

            <div id="comment-<?php echo $row['id'] ?>" class="reply w3-row" style="display:flex;width:100%;">
                <div class="gr8-theme w3-card-2 w3-light-grey w3-padding-small w3-round-small w3-margin-right w3-col m3 l3">
                    <img id="pfp" src="<?php echo $pfp ?>"><br />
                    <?php if ($c_user_exists) { ?>
                        <a href="../user/<?php echo $c_user ?>">
                    <?php } ?>
                        <span class="<?php echo $isAdmin ?>" style="text-overflow: ellipsis;">
                            <?php echo $c_username ?>
                        </span>
                    <?php if ($c_user_exists) { ?>
                        </a>
                    <?php } ?>
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
                    <pre class="comment-text"><?php echo $bbcode->toHTML($bbcode->Smilify($decoded_comment), false, true) ?></pre>
					<form class="edit w3-hide">
						<textarea class="edit-textarea"><?php echo $decoded_comment ?></textarea><br />
						<button class="save-btn w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo">Save</button>
						<button class="cancel-btn w3-btn w3-white w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-grey">Cancel</button>
					</form>
					<span class="w3-display-bottomleft">
						<?php
						if (loggedin()) {
							if ($row['parent'] === $post_id && ($current_user->admin || trim($current_user->id) === trim($c_user))) {
								?>
								<div class="delete gr8-theme w3-hide w3-light-grey w3-round-small w3-padding-small w3-margin-bottom">
									<p>Are you sure you want to delete this comment?</p>
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
        <?php
        }
        ?>
        <br />
    <?php
        echo '<a class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo" href="?id=' . $post_id . '&p=' . ($page - 1) . '">Back</a>&nbsp;';
        echo '<a class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo" href="?id=' . $post_id . '&p=' . ($page + 1) . '">Next</a>';
    } else {
        echo "<p>No replies yet.</p><br />";
        echo '<a class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo" href="?id=' . $post_id . '&p=' . ($page - 1) . '">Back</a>';
    }

    $words = [
        'Spark something about flying cows',
        'Start a MOC contest',
        'Pigs! Its all about pigs!',
        '[color=red]Undefined![/color]',
        '[s]Don\'t use Javascript on the server side![/s]',
        'Oops, I think I changed the padding on that button by ~0.01% of a pixel!',
        'Moderation! Amazing!',
        '[i]Did you ever hear the tragedy of Darth Plagueis "the wise"? I thought not. It\'s not a story the Jedi would tell you. It\'s a Sith legend. Darth Plagueis was a Dark Lord of the Sith, so powerful and so wise he could use the Force to influence the midichlorians to create life... He had such a knowledge of the dark side that he could even keep the ones he cared about from dying. The dark side of the Force is a pathway to many abilities some consider to be unnatural. He became so powerful... the only thing he was afraid of was losing his power, which eventually, of course, he did. Unfortunately, he taught his apprentice everything he knew, then his apprentice killed him in his sleep. It\'s ironic he could save others from death, but not himself.[/i]',
        'Woaw!',
        'Obviously',
        'Sixty five, sixty six, sixty... why do I bother. You already get it.',
        'Dislike 👎😒',
        'Big fan of user @' . rand(1, 20) . '\'s creations',
        '[object Object] said the console!',
        'Rahhhh',
        '"[i]Gr8 b8, m8. I rel8, str8 appreci8, and congratul8. I r8 this b8 an 8/8. Plz no h8, I\'m str8 ir8. Cre8 more, can\'t w8. We should convers8, I won\'t ber8, my number is 8888888, ask for N8. No calls l8 or out of st8. If on a d8, ask K8 to loc8. Even with a full pl8, I always have time to communic8 so don\'t hesit8[/i]"'
    ];

    $randomWord = $words[array_rand($words)];
    $randomWordDisplay = htmlentities($randomWord);
    $randomWordBB = $bbcode->toHTML($randomWord, true, true);

    echo "<br /><hr />";
    echo "<p title='" . $randomWordDisplay . "'>Word of the refresh: " . $randomWordBB . "</p>";

    if ($category === "pinnedLocked" || $category === "locked") {
        echo "<b id='commentboxcontainer'>This conversation is locked. New replies cannot be posted.</b><br />";
    } else if ($category === "nolist") {
        if (loggedin()) {
            $forum_anonymous_username = $current_user->username;
        } else {
            $forum_anonymous_username = $_SESSION['forum_anonymous_username'];
        }

        echo "<b>You can only post anonymous comments on unlisted forums.</b>";
        echo "<br /><form id='commentboxcontainer' method='post' action=''>";
        echo "<input type='text' value='" . $forum_anonymous_username . "' placeholder='Name' name='username' /><br />";
        echo "<textarea name='commentbox' placeholder='" . $randomWordDisplay . "' rows='4' cols='50'></textarea><br />";
        echo "<input type='submit' value='Reply' name='comment' class='w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo' />";
        echo "</form><br />";
    } else {
        if (loggedin()) {
            echo "<a href='#commentboxlink'>permalink to replybox</a>";
            echo "<br /><a id='commentboxlink'><form id='commentboxcontainer' method='post' action=''>";
            echo "<textarea name='commentbox' placeholder='" . $randomWordDisplay . "' rows='4' cols='50'></textarea><br />";
            echo "<input type='submit' value='Reply' name='comment' class='w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo' />";
            echo "</form></a><br />";
        } else {
            echo "<b id='commentboxcontainer'>Please <a href='../acc/login'>login</a> to post a reply.</b><br />";
        }
    }

    include $_SERVER['DOCUMENT_ROOT'] . '/linkbar.php';
    ?>
</body>

</html>