<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: /acc/login.php');
    exit();
}

require_once '../acc/classes/user.php';

if ($_POST) {
    $description = htmlspecialchars($_POST['description']);
    $title = htmlspecialchars($_POST['title']);
    $time = date("Y-m-d H:i:s");

    // Define the file path directly in the /posts folder
    $filePath = "posts/" . uniqid() . '.xml';

    // Open the file
    $handle = fopen($filePath, "a");

    if ($handle) {
        fwrite($handle, "<code><title>" . $title . "</title><post>" . $description . "</post><uname>" . $id . "</uname><date>" . $time . "</date><status>Active</status></code>");
        fclose($handle);
        echo "File created successfully.";
    } else {
        die("Failed to open file for writing: " . $filePath);
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>GR8BRIK community forums</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="../lib/theme.css">
    <script src="../lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <meta charset="UTF-8">
    <meta name="description" content="Gr8brik is a block building browser game. No download required">
    <meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"><meta charset="UTF-8">    
</head>
<body class="w3-light-blue w3-container">
    <?php include '../navbar.php' ?>
    <center>
        <h1>Post</h1>
        <form method="post" action="">
		
			<p>
				<label for="title">Title:</label>
				<input type="text" class="w3-input" name="title" placeholder="title" size="50" required style="width:30%"/>
			</p>
            <p>
				<label for="description">Description:</label><br />
				<textarea name="description" placeholder="description" rows="4" cols="50" required></textarea>
			</p>
			<br/>
			<p><input type="submit" value="POST TOPIC" name="post" class="w3-btn w3-blue w3-hover-opacity" /></p>
			<p><button class="w3-btn w3-round w3-hover-opacity" onclick="history.go(-1)">go back</button></p>

        </form>
    </center><br /><br />
    
    <?php include('../linkbar.php'); ?>

</body>
</html>