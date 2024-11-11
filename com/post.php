<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';

    if($_POST) {
        require_once '../acc/classes/user.php';

        define('DB_NAME3', 'if0_36019408_forum');
        $title = $conn->real_escape_string($_POST['title']);
        $post = $conn->real_escape_string($_POST['description']);
        $date = date("Y-m-d H:i:s");
        $category = 'general';

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
        echo "Done!";
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Post Topic</title>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="../lib/theme.css">
    <script src="../lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script async defer src="https://cdn.jsdelivr.net/npm/altcha/dist/altcha.min.js" type="module"></script>
    <meta charset="UTF-8">
    <meta name="description" content="Gr8brik is a block building browser game. No download required">
    <meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no"> <!-- ios support -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/img/logo.jpg" />
    <meta name="apple-mobile-web-app-status-bar" content="#f1f1f1" />
    <meta name="theme-color" content="#f1f1f1" />
</head>
<body class="w3-light-blue w3-container">
    <?php include '../navbar.php' ?>
    <center>
        <h1>Post</h1>
        <form method="post" action="">
		
			<p>
				<label for="title">Title:</label>
				<input type="text" class="w3-input" name="title" placeholder="Title your post" size="50" required style="width:30%"/>
			</p>
            <p>
				<label for="description">Post:</label><br />
				<textarea name="description" placeholder="Your post body" rows="4" cols="50" required></textarea>
			</p>
            <altcha-widget challengeurl='https://eu.altcha.org/api/v1/challenge?apiKey=ckey_01d9f4ad018c16287ca6f3938a0f'></altcha-widget>
			<br/>
			<p><input type="submit" value="POST TOPIC" name="post" class="w3-btn w3-blue w3-hover-opacity w3-border w3-mobile" /></p>
			<p><button class="w3-btn w3-blue w3-hover-opacity w3-border w3-mobile" onclick="history.go(-1)">GO BACK</button></p>

        </form>
    </center><br /><br />
    
    <?php include('../linkbar.php'); ?>

</body>
</html>