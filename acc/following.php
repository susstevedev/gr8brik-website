<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';

if(!isset($_SESSION['username'])){

    header('Location: login.php');

}

if(isset($_POST['unfollow'])) {
	
    $profileid = $_GET['user'];
	$sql = "DELETE FROM follow WHERE userid = ? AND profileid = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ii", $id, $profileid);
	$stmt->execute();
	$stmt->close();
	$conn->close();
	die;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>People</title>
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

    <?php
        $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        if ($conn2->connect_error) {
            exit($conn2->connect_error);
        }

		$sql = "SELECT * FROM follow WHERE userid = $id ORDER BY id DESC";
        $result = $conn2->query($sql);

		while ($row = $result->fetch_assoc()) {
            $profileid = $row['profileid'];
            $sql2 = "SELECT * FROM users WHERE id = $profileid";
            $result2 = $conn2->query($sql2);
            $row2 = $result2->fetch_assoc();

			echo "<article class='w3-card-2 w3-light-grey w3-padding w3-large'>";
			echo "<a href='../@" . urlencode($row2['username']) . "'>" . $row2['username'] . '</a><br />';
            echo "<form id='unfriend' method='post' action='following.php?user=" . $profileid . "'></form>";
            echo "<input form='unfriend' type='submit' value='Unfriend' name='unfollow' class='w3-btn w3-large w3-white w3-hover-red w3-mobile w3-border'>";
			echo "</article><br />";
            $result2->free();
        }
        $result->free();
        $conn2->close();
		
	?>
		
    <?php include '../linkbar.php' ?>
</body>
</html>