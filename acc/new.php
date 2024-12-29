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
    <?php include '../header.php' ?>
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
            echo '<h2>' . $row3['num'] . ' total notifications since ' . $age . '</h2>';

			$sql = "SELECT * FROM notifications WHERE user = $id ORDER BY timestamp DESC";
            $result = $conn2->query($sql);

			while ($row = $result->fetch_assoc()) {
                $profile = $row['profile'];
                $sql2 = "SELECT * FROM users WHERE id = $profile";
                $result2 = $conn2->query($sql2);
                $row2 = $result2->fetch_assoc();

                if ($row['category'] === "1") {
                    $url = "/user/" . $profile;
                    $post = " followed you";
                    $user = $row2['username'];
                    $img = '/ajax/pfp?method=image&id=' . base64_encode($profile);
                } elseif ($row['category'] === "2") {
                    $conn3 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
                    $content = $row['content'];
                    $result3 = $conn3->query("SELECT * FROM model WHERE id = $content");
                    $row3 = $result3->fetch_assoc();

                    $img = '../cre/' . $row3['screenshot'];
                    $url = "/build/" . $content;
                    $post = " commented on " . $row3['name'];
                    $user = $row2['username'];

                    if($row2['verified'] != 0) {
                        $user = $user . '&nbsp;<i class="fa fa-check w3-blue w3-large"></i>';
                    }
                    if($row2['admin'] != 0) {
                        $user = $user . '&nbsp;<i class="fa fa-check w3-red w3-large"></i>';
                    }

                    $result3->free();
                    $conn3->close();
                } elseif ($row['category'] === "3") {
                    $conn3 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
                    $content = $row['content'];
                    $result3 = $conn3->query("SELECT * FROM posts WHERE id = $content");
                    $row3 = $result3->fetch_assoc();

                    $img = '../img/com.jpg';
                    $url = "/topic/" . $row['content'];
                    $post = " replied to thread: " . $row3['title'];
                    $user = $row2['username'];

                    if($row2['verified'] != 0) {
                        $user = $user . '&nbsp;<i class="fa fa-check w3-blue w3-large"></i>';
                    }
                    if($row2['admin'] != 0) {
                        $user = $user . '&nbsp;<i class="fa fa-check w3-red w3-large"></i>';
                    }
                    $result3->free();
                    $conn3->close();
                } else {
                    $url = $row['url'];
                    $post = $row['post'];
                    $user = "";

                    $img = '/img/no_image.png';
                }
                if (is_numeric($row['timestamp'])) {
                    $time = gmdate("Y-m-d H:i:s", $row['timestamp']);
                } else {
                    $time = "";
                }

				echo "<article class='w3-card-2 w3-light-grey w3-padding w3-large'>";
                echo "<img src='" . $img . "' width='150px' height='150px' alt='" . $img . "'>";
				echo "<a href='" . $url . "' style='display: inline-block; vertical-align: top; padding: 5px 5px 5px 5px;'>" . $user . $post . "</a>";
                echo "<time class='w3-right w3-text-grey' datetime=''>" . $time . "</time>";
				echo "</article><br />";

                $img = null;
                $result2->free();
            }
            $result->free();
            $conn2->close();
		
		?><br /><br />
		
    <?php include '../linkbar.php' ?>
</body>
</html>