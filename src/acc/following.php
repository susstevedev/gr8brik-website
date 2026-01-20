<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
if(loggedin() !== true) {
    header('Location:login.php');
}

if(isset($_POST['unfollow'])) {
    $profileid = $_GET['user'];
	$sql = "DELETE FROM follow WHERE userid = ? AND profileid = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ii", $id, $profileid);
	$stmt->execute();
	$stmt->close();
	$conn->close();
	exit;
}

if(isset($_POST['unblock'])) {
    $profileid = $_GET['user'];
	$sql = "DELETE FROM user_blocks WHERE userid = ? AND profileid = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ii", $id, $profileid);
	$stmt->execute();
	$stmt->close();
	$conn->close();
	exit;
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
        $(document).ready(function() {
            openTab('followingtab');
        });
    </script>

    <div class="message w3-padding w3-round w3-red">Some accounts may not appear here.</div><br />

    <div class="w3-bar">
        <a class='w3-bar-item w3-bottombar' style="width: 33%; text-align: center;" onclick="openTab('followingtab', this)">Following</a>
        <a class='w3-bar-item w3-bottombar' style="width: 33%; text-align: center;" onclick="openTab('followerstab', this)">Followers</a>
        <a class='w3-bar-item w3-bottombar' style="width: 33%; text-align: center;" onclick="openTab('blockedtab', this)">Blocked</a>
    </div>

    <?php
        $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        if ($conn2->connect_error) {
            exit($conn2->connect_error);
        }

		$sql = "SELECT DISTINCT id, profileid FROM follow WHERE userid = '" . $token['user'] . "' ORDER BY id DESC";
        $result = $conn2->query($sql);

        echo "<div id='followingtab' class='tab w3-animate-opacity w3-hide'><h4>Who you follow</h4>";

    	while ($row = $result->fetch_assoc()) {
            $profileid = $row['profileid'];
            $sql2 = "SELECT DISTINCT * FROM users WHERE id = '$profileid'";
            $result2 = $conn2->query($sql2);
            if(!empty($result2)) {
                $row2 = $result2->fetch_assoc();
            	$username = $row2['username'] ?? 'inactive';

                echo "<article data-gr8brik-item='follow-" . $row2['blog_user_id'] . "' id='user-" . $profileid . "-name-" . strtolower(urlencode($username)) . "' class='gr8-theme w3-card-2 w3-light-grey w3-padding w3-large'>";
                echo "<a href='../user/" . $profileid . "'>" . $row2['username']. '</a><br />';
                if(!empty($row2['description'])) {
                    echo "<b>" . $row2['description'] . '</b><br />';
                }
                echo "<form id='unfollow' method='post' action='following.php?user=" . $profileid . "'></form>";
                echo "<input form='unfollow' type='submit' value='Unfollow' name='unfollow' class='w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink'>";
                echo "</article><br />";
          	}
                $result2->free();
            	$username = null;
            	$userid = null;
        }
        $result->free();
        echo "</div>";

        $sql = "SELECT DISTINCT id, userid FROM follow WHERE profileid = '" . $_SESSION['userid'] . "' ORDER BY id DESC";
        $result3 = $conn2->query($sql);

        echo "<div id='followerstab' class='tab w3-animate-opacity w3-hide'><h4>Who follows you</h4>";

		while ($row3 = $result3->fetch_assoc()) {
            $userid = $row3['userid'];
            $sql2 = "SELECT DISTINCT * FROM users WHERE id = '$userid'";
            $result4 = $conn2->query($sql2);
            if(!empty($result4)) {
                $row4 = $result4->fetch_assoc();
            	$username = $row4['username'] ?? 'inactive';

                echo "<article data-gr8brik-item='follow-" . $row4['blog_user_id'] . "' id='user-" . $userid . "-name-" . strtolower(urlencode($username)) . "' class='gr8-theme w3-card-2 w3-light-grey w3-padding w3-large'>";
                echo "<a href='../user/" . $userid . "'>" . $row4['username'] . '</a><br />';
                if(!empty($row4['description'])) {
                    echo "<b>" . $row4['description'] . '</b><br />';
                }
                echo "</article><br />";
            }
            $result4->free();
            $username = null;
            $userid = null;
        }
        $result3->free();
        echo "</div>";

        $result4 = $conn2->query("SELECT DISTINCT id, profileid FROM user_blocks WHERE userid = '" . $token['user'] . "' ORDER BY id DESC");
        echo "<div id='blockedtab' class='tab w3-animate-opacity w3-hide'><h4>Who you blocked</h4>";

		while ($row4 = $result4->fetch_assoc()) {
            $userid = $row4['profileid'];
            $result5 = $conn2->query("SELECT DISTINCT * FROM users WHERE id = '$userid'");
            if(!empty($result5)) {
                $row5 = $result5->fetch_assoc();
                $username = $row5['username'] ?? 'inactive';

                echo "<article data-gr8brik-item='block-" . $row5['blog_user_id'] . "' id='user-" . $userid . "-name-" . strtolower(urlencode($username)) . "' class='gr8-theme w3-card-2 w3-light-grey w3-padding w3-large'>";
                echo "<a href='../user/" . $userid . "'>" . $row5['username'] . '</a><br />';
                if(!empty($row5['description'])) {
                    echo "<b>" . $row5['description'] . '</b><br />';
                }
                echo "<form id='unblock' method='post' action='following.php?user=" . $profileid . "'></form>";
                echo "<input form='unblock' type='submit' value='Unblock' name='unblock' class='w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink'>";
                echo "</article><br />";
            }
            $result5->free();
            $username = null;
            $userid = null;
        }
        $result4->free();
        $conn2->close();
        echo "</div>";
	?>
		
    <?php include '../linkbar.php' ?>
</body>
</html>