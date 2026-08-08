<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/com/bbcode.php';
if(!loggedin()) {
    header('Location:login.php');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>[BETA] Analytics</title>
    <?php include '../header.php' ?>
</head>
<body class="w3-light-blue w3-container">

    <?php 
        include '../navbar.php';
        include 'panel.php';
    ?>
		<?php
            $cookie = Cookie::controls();
            $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
            if ($conn2->connect_error) {
                exit($conn2->connect_error);
            }

            if(!in_array('analytics', $cookie)) {
                echo "<b>Analytics are disabled in your cookie settings.</b>";
            } else {
                echo "<b>Analytics are enabled in your cookie settings.</b>";
            }

			$sql = "SELECT * FROM analytics WHERE their_user = " . login()->id . " ORDER BY time DESC";
            $result = $conn2->query($sql);
            $bb = new BBcode();

			while (in_array('analytics', $cookie) && $row = $result->fetch_assoc()) {
                $profile = $row['my_user'];
                $usero = User::getUser($profile);

                $url = "/user/" . $profile;
                $post = "viewed your profile or other content you have published";
                $user = $row['my_name'] ?? '[unknown]';
                $desc = $usero->description;
                $img = '../acc/users/pfps/' . $profile;
                $time = time_ago($row['time']);

                echo "<article class='w3-card-4 w3-hover-shadow gr8-theme w3-light-grey w3-padding w3-large'>";
                echo "<a href='" . $url . "'><img src='" . $img . "' style='width: 150px; height: 150px;' alt='" . $user . "' title='" . $user . "'>";
                echo "<span style='display: inline-block; vertical-align: top; padding: 5px;'>";
                echo "<b>" . htmlspecialchars($user) . "</b>&nbsp;" . htmlspecialchars($post) . "</span></a>";
                echo "<time class='w3-right w3-text-grey' datetime=''>" . $time . "</time>";
                echo "<br /><span>" . $bb->toHTML(nl2br($desc)) . "</span></a>";
                echo "</article><br />";
            }
            $result->free();
            $conn2->close();
		
		?><br /><br />
		
    <?php include '../linkbar.php' ?>
</body>
</html>