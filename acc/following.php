<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
isLoggedIn();

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
    <?php include '../header.php' ?>
</head>
<body class="w3-light-blue w3-container">

    <?php 
        include('../navbar.php');
        include('panel.php');
    ?>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            openTab('followingtab');
        });
    </script>

    <div class="w3-bar">
        <a class='w3-bar-item w3-bottombar' style="width: 50%; text-align: center;" onclick="openTab('followingtab', this)">Following</a>
        <a class='w3-bar-item w3-bottombar' style="width: 50%; text-align: center;" onclick="openTab('followerstab', this)">Followers</a>
    </div>

    <?php
        $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        if ($conn2->connect_error) {
            exit($conn2->connect_error);
        }

		$sql = "SELECT DISTINCT profileid FROM follow WHERE userid = '$id' ORDER BY id DESC";
        $result = $conn2->query($sql);

        echo "<div id='followingtab' class='tab w3-animate-opacity w3-hide'><h4>Who you follow</h4>";

		while ($row = $result->fetch_assoc()) {
            $profileid = $row['profileid'];
            $sql2 = "SELECT * FROM users WHERE id = '$profileid'";
            $result2 = $conn2->query($sql2);
            if($result2->num_rows > 0) {
                $row2 = $result2->fetch_assoc();

                echo "<article class='gr8-theme w3-card-2 w3-light-grey w3-padding w3-large'>";
                echo "<a href='../user/" . $profileid . "'>" . $row2['username'] . '</a><br />';
                echo "<form id='unfollow' method='post' action='following.php?user=" . $profileid . "'></form>";
                echo "<input form='unfollow' type='submit' value='Unfollow' name='unfollow' class='w3-btn w3-large w3-white w3-hover-red w3-mobile w3-border'>";
                echo "</article><br />";
            }
            $result2->free();
        }
        $result->free();
        echo "</div>";

        $sql = "SELECT DISTINCT userid FROM follow WHERE profileid = '$id' ORDER BY id DESC";
        $result3 = $conn2->query($sql);

        echo "<div id='followerstab' class='tab w3-animate-opacity w3-hide'><h4>Who follows you</h4>";

		while ($row3 = $result3->fetch_assoc()) {
            $userid = $row3['userid'];
            $sql2 = "SELECT * FROM users WHERE id = '$userid'";
            $result4 = $conn2->query($sql2);
            if($result4->num_rows > 0) {
                $row4 = $result4->fetch_assoc();

                echo "<article class='gr8-theme w3-card-2 w3-light-grey w3-padding w3-large'>";
                echo "<a href='../user/" . $userid . "'>" . $row4['username'] . '</a><br />';
                //echo "<form id='block' method='post' action='following.php?block=" . $userid . "'></form>";
                //echo "<input form='block' type='submit' value='Block' name='block' class='w3-btn w3-large w3-white w3-hover-red w3-mobile w3-border' disabled>";
                echo "</article><br />";
            }
            $result4->free();
        }
        $result3->free();
        $conn2->close();
        echo "</div>";
	?>
		
    <?php include '../linkbar.php' ?>
</body>
</html>