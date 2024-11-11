<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';

if(!isset($_SESSION['username'])){

    header('Location: login.php');

}

if(isset($_POST['clear'])) {
	// Create connection
	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

	// Check connection
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	// Fetch the current password from the database
	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
	$stmt = $conn->prepare("SELECT alert FROM users WHERE id = ?");
	$username = $_SESSION['username'];
	$stmt->bind_param("i", $username);
	$stmt->execute();
	$stmt->bind_result($db_alert);
	$stmt->fetch();
	$stmt->close();
	$alertnum = '0';
	$stmt_2 = $conn->prepare("update users SET alert = ? WHERE id = ?");
	$stmt_2->bind_param("ss", $alertnum, $username);
	if ($stmt_2->execute()) {
		header("Location: new.php");
	} else {
		echo "An error has occurred";
	}
	$stmt->close();
	die;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Notifications</title>
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no"><!-- ios support -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/img/logo.jpg" />
    <meta name="apple-mobile-web-app-status-bar" content="#f1f1f1" />
    <meta name="theme-color" content="#f1f1f1" />
</head>
<body class="w3-light-blue w3-container">

    <?php 
        include('../navbar.php');
        include('panel.php');
    ?>

        <form id="clearForm" method="post" action="">
            <input class="w3-btn w3-blue w3-hover-white" type="submit" value="Clear Inbox" name="clear">
        </form>

		<?php
            $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
            if ($conn2->connect_error) {
                exit($conn2->connect_error);
            }

            $result3 = $conn2->query("SELECT COUNT(*) as num FROM notifications WHERE user = $id");
            $row3 = $result3->fetch_assoc();
            echo '<div class="w3-card-2 w3-light-grey w3-padding-tiny"><img src="../img/info.jpg" style="width:20px;height=20px;"><b>A new notifications system is rolling out now.</b></div>';
            echo '<h2>' . $row3['num'] . ' total notifications sense ' . $age . '.</h2>';

			$sql = "SELECT * FROM notifications WHERE user = $id ORDER BY timestamp DESC";
            $result = $conn2->query($sql);

			while ($row = $result->fetch_assoc()) {
                $profile = $row['profile'];
                $sql2 = "SELECT * FROM users WHERE id = $profile";
                $result2 = $conn2->query($sql2);
                $row2 = $result2->fetch_assoc();
                if(empty($row2['username'])) {
                    $row2['username'] = "[deleted]";
                }

                if ($row['category'] === "1") {
                    $url = "/@" . urlencode($row2['username']);
                    $post = " followed you";
                } elseif ($row['category'] === "2") {
                    $url = "/build/" . $row['content'];
                    $post = " commented on your creation";
                } elseif ($row['category'] === "3") {
                    $url = "/topic/" . $row['content'];
                    $post = " commented on your topic";
                } else {
                    $url = $row['url'];
                    $post = "&nbsp;" . $row['post'] . "(sent via old api)";
                }
                if (is_numeric($row['timestamp'])) {
                    $timestamp = gmdate("Y-m-d H:i:s", $row['timestamp']);
                } else {
                    $timestamp = "[unknown]";
                }

				echo "<article class='w3-card-2 w3-light-grey w3-padding w3-large'>";
				echo "<a href='" . $url . "'>" . htmlspecialchars($row2['username']) . $post . "</a>";
                echo "<time class='w3-right w3-text-grey' datetime=''>" . $timestamp . "</time>";
				echo "</article><br />";
                $result2->free();
            }
            $result->free();
            $conn2->close();
		
		?><br /><br />
		
    <?php include '../linkbar.php' ?>
</body>
</html>