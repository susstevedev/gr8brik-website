<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';


if(!isset($_SESSION['username'])){

    header('Location: login.php');

} else {

    if($admin != '1'){

        header('Location: index.php');

    }

}

if(isset($_POST['deny'])) {
	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
	    die("Error:" . $conn->connect_error);
    }

    $user = $_GET['user'];
    $username = $_GET['name'];

	$sql = "DELETE FROM appeals WHERE user = $user"; 
    $result = $conn->query($sql);
    if ($result) {
        echo "Deleted all appeals from @" . urldecode($username) . ".";
        exit;
    } else {
        echo "Error:" . $conn->error;
        exit;
    }
}

if(isset($_POST['accept'])) {
	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
	    die("Error:" . $conn->connect_error);
    }

    $user = $_GET['user'];
    $username = $_GET['name'];

	$sql = "DELETE FROM bans WHERE user = $user"; 
    $result = $conn->query($sql);

	$sql2 = "DELETE FROM appeals WHERE user = $user"; 
    $result2 = $conn->query($sql2);

    if ($result && $result2) {
        echo "Deleted all bans from @" . urldecode($username) . ".";
        exit;
    } else {
        echo "Error:" . $conn->error;
        exit;
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Appeals</title>
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
            // Create connection
            $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

            // Check connection
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            $sql = "SELECT user, reason, end_date FROM appeals";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($user, $reason, $date);

            // Prepare the second statement outside the loop
            $sql2 = "SELECT username FROM users WHERE id = ?";
            $stmt2 = $conn->prepare($sql2);

            while ($stmt->fetch()) {
                $stmt2->bind_param("i", $user);
                $stmt2->execute();
                $stmt2->store_result();
                $stmt2->bind_result($username);
                $stmt2->fetch();
                if ($stmt2->num_rows === 0 || $username == "") {
                    $username = "[unknown profile]";
                }
                $stmt2->free_result(); // Free the result set

                echo "<article class='w3-card-24 w3-light-grey w3-padding'>";
                echo "<header><img src='users/pfps/" . $user . "..jpg' id='pfp'><br />";
                echo "<h4><a href='../@" . urlencode($username) . "'>" . htmlspecialchars($username) . "</a></h4></header>";
                echo "<b>" . $reason . "</b><br />";
                echo "<form method='post' action='appeals.php?user=" . $user . "&name=" . $username . "'>";
                echo "<input type='submit' value='DENY' name='deny' class='w3-btn w3-large w3-white w3-hover-red'>&nbsp;";
                echo "<input type='submit' value='ACCEPT' name='accept' class='w3-btn w3-large w3-white w3-hover-blue'>";
                echo "</form></article><br />";
            }

            // Close the statements and connection
            $stmt2->close();
            $stmt->free_result(); // Free the result set of the outer statement
            $stmt->close();
            $conn->close();
        ?>
		
	</div>
		
    <?php include '../linkbar.php' ?>
</body>
</html>