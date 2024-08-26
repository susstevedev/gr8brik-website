<?php

session_start();

if(!isset($_SESSION['username'])){

    header('Location: login.php');

}

require_once 'classes/user.php';

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
    <title>Notifications - GR8BRIK</title>
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="w3-light-blue w3-container">

    <?php include('../navbar.php') ?>
	
	<div class="w3-center">
        <div class="w3-light-grey w3-card-24 w3-center">
            <?php 
				if($alert != 0){
					echo '<h1 style="color:red;">You have new notifications!</h1>';
				}
                echo "<h3>Welcome,</h3><img id='pfp' src='users/pfps/" . $id . "..jpg' class='w3-hover-opacity w3-border w3-card-24' onclick=document.getElementById('im').style.display='block' />";
                echo '<h1>' . $user . '</h1><h3>@' . $handle . '</h3>';
            ?>
            <a href="<?php echo $handle ?>" class="w3-btn w3-large w3-white w3-hover-blue"><li class="fa fa-user" aria-hidden="true"></li><b>PROFILE</b></a>
			<a href="new.php" class="w3-btn w3-large w3-white w3-hover-blue"><i class="fa fa-bell" aria-hidden="true"></i><b>NOTIFICATIONS</b></a>
            <a href="creations.php" class="w3-btn w3-large w3-white w3-hover-blue"><i class="fa fa-building" aria-hidden="true"></i><b>MY CREATIONS</b></a>
        </div><br /><br />

        <form id="clearForm" method="post" action="">
            <input class="w3-btn w3-blue w3-hover-opacity" type="submit" value="CLEAR ALL" name="clear">
        </form><br />

		<?php
			// Create connection
			$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

			// Check connection
			if ($conn->connect_error) {
				die("Connection failed: " . $conn->connect_error);
			}
			
			$sql = "SELECT user, name, post, timestamp, url FROM notifications WHERE user = ? ORDER BY timestamp DESC";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s", $id);
			$stmt->execute();
			$stmt->bind_result($user, $name, $post, $timestamp, $url);

			while ($stmt->fetch()) {
				echo "<article class='w3-card-24 w3-light-grey w3-padding'>";
				echo "<header><h4><a href='../" . $url . "'>" . htmlspecialchars($name) . "</a></h4></header>";
				echo "<b>" . htmlspecialchars($post) . "</b>";
				echo "<br /><b>" . htmlspecialchars($timestamp) . "</b>";
				echo "</article><br />";
            }
		
		?>
		
	</div>
		
    <?php include '../linkbar.php' ?>
</body>
</html>