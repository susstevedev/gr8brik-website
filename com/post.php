<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';

    if($_POST) {
        require_once '../acc/classes/user.php';

        define('DB_NAME3', 'if0_36019408_forum');
        $title = $conn->real_escape_string($_POST['title']) ?: "Unnamed Topic";
        $post = $conn->real_escape_string($_POST['description']);
        $date = date("Y-m-d H:i:s");
        $category = 'general';

        if(isset($_SESSION['username'])) {
            if(isset($post)) {
                $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
                $sql = "INSERT INTO posts (user, title, category, post, date) VALUES ('$id', '$title', '$category', '$post', '$date')";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    die($conn->error);
                }
                if (!$stmt->execute()) {
                    die($stmt->error);
                }
                $stmt->close();
                $msg = "Done!";
            } else {
                $msg = "Topic body is empty!";
            }
        } else {
            $msg = "Please login to post!";
        }
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Post Topic</title>
    <?php include '../header.php' ?>
</head>
<body class="w3-light-blue w3-container">
    <?php include '../navbar.php' ?>
    <style>
        .altcha-footer, .altcha-logo {
            display: none !important;
        }
        [name=title], [name=description] {
            border: 0px !important;
            width: 45% !important;
            border-radius: 0px !important;
            border: 0 !important;
            outline: 0 !important;
            padding: 5px 5px 5px 5px;
        }
    </style>
    <center>
        <h1>Post</h1>
        <form method="post" action="">
            <?php
                if(isset($_POST['title'])) {
                    $title = $_POST['title'];
                } else {
                    $title = "Title";
                }
                if(isset($_POST['description'])) {
                    $description = $_POST['description'];
                } else {
                    $description = "Description";
                }
            ?>

            <p>
                <span class="error"><?php echo $msg ?: "Post a message to the community forums" ?></p>
            </p>

			<p>
				<label for="title">Title&nbsp;</label>
				<input type="text" name="title" value="<?php echo $title ?: "Title" ?>" size="50" />
			</p>

            <p>
				<label for="description">Post&nbsp;</label><br />
				<textarea name="description" rows="4" cols="50" required><?php echo $description ?: "Description" ?></textarea>
			</p>

            <p>
                <altcha-widget
                    style="--altcha-border-width:0px;"
                    challengeurl='https://us.altcha.org/api/v1/challenge?apiKey=ckey_01d9f4ad018c16287ca6f3938a0f'>
                </altcha-widget>
            </p>

			<p>
                <input type="submit" value="Post topic" name="post" class="w3-btn w3-blue w3-hover-white w3-border w3-border-indigo" />
                <button class="w3-btn w3-blue w3-hover-white w3-border w3-border-indigo" onclick="history.go(-1)">Back</button>
            </p>

        </form>
    </center><br /><br />
    
    <?php include('../linkbar.php'); ?>

</body>
</html>