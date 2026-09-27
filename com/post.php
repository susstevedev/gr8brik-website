<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
if ($_POST) {
    $id = $current_user->id;
    $title = trim($_POST['title'] ?? '');
    $post = trim($_POST['description'] ?? '');
    $date = date("Y-m-d H:i:s");
    $now = time();
    $category = 'general';
    $notif_category = 3;

    if (loggedin()) {
        if (!empty($post) && !empty($title)) {
            $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
            $sql = "INSERT INTO messages (userid, title, last_active_time, status, content, timestamp) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("isisss", $id, $title, $now, $category, $post, $date);
                if ($stmt->execute()) {
                    $topicid = $conn->insert_id;
                    $stmt_sub = $conn->prepare("INSERT IGNORE INTO " . DB_NAME . ".subscriptions (userid, content, category, timestamp) VALUES (?, ?, ?, ?)");
                    $stmt_sub->bind_param("iii", $id, $topicid, $notif_category, $now);
                    $stmt_sub->execute();
                    $msg = "Topic posted.";
                } else {
                    $msg = $stmt->error;
                }
                $stmt->close();
            } else {
                $msg = $conn->error;
            }
            $conn->close();
        } else {
            $msg = "Post body or title is empty.";
        }
    } else {
        $msg = "Please login to post.";
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Post topic</title>
    <?php include '../header.php' ?>
</head>
<body class="w3-light-blue w3-container">
    <?php include '../navbar.php' ?>
    <center>
        <h1>Post</h1>
        <form method="post" action="">
            <?php
                if(isset($_POST['title'])) {
                    $title = $_POST['title'];
                } else {
                    $title = "";
                }
                if(isset($_POST['description'])) {
                    $description = $_POST['description'];
                } else {
                    $description = "";
                }
            ?>

            <p><b><?php echo $msg ?? "Post a message to the community forums" ?></b></p>

			<p>
				<label for="title">Title&nbsp;</label>
				<input type="text" name="title" placeholder="Title" value="<?php echo $title ?>" size="50" />
			</p>

            <p>
				<label for="description">Post body&nbsp;</label><br />
				<textarea name="description" rows="4" cols="50" placeholder="Post body"><?php echo $description ?></textarea>
			</p>

			<p>
                <button class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" onclick="history.go(-1)">Back</button>
                <input type="submit" value="Post topic" name="post" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" />
            </p>
        </form>
    </center><br /><br />
    <?php include '../linkbar.php' ?>
</body>
</html>