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
                echo "<b>Analytics are enabled in your cookie settings.</b><br />";
                echo "<p>This page displays user information of those who have viewed your profile or content in the past <b>15 days</b>.</p>";
            }

			$sql = "SELECT * FROM analytics WHERE their_user = " . $id . " AND time >= NOW() - INTERVAL 15 DAY ORDER BY time DESC";
            $result = $conn2->query($sql);
            $bb = new BBcode();
            $rows = [];

            while(in_array('analytics', $cookie) && $row = $result->fetch_assoc()) {
                $rows[] = $row;
            }

            $userIds = array_column($rows, 'my_user');
            $users = User::getUsers($userIds);
            $follow_query_ids = array_map('intval', $userIds);

            if (!empty($follow_query_ids)) {
                $fol_counts = array_fill_keys($follow_query_ids, 0);
                $fol_you = array_fill_keys($follow_query_ids, 0);
                $placeholders = implode(',', array_fill(0, count($follow_query_ids), '?'));

                $stmt = $conn->prepare("SELECT profileid, COUNT(*) as total FROM follow WHERE profileid IN ($placeholders) GROUP BY profileid");
                $stmt->execute($follow_query_ids);
                $res = $stmt->get_result();

                while ($row = $res->fetch_assoc()) {
                    $fol_counts[$row['profileid']] = (int)$row['total'];
                }

                $stmt = $conn->prepare("SELECT userid FROM follow WHERE profileid = ? AND userid IN ($placeholders)");
                $params = array_merge([$id], $follow_query_ids);
                $stmt->execute($params);
                $res = $stmt->get_result();

                while ($row = $res->fetch_assoc()) {
                    $fol_you[$row['userid']] = true;
                }
            }

			foreach($rows as $row) {
                $profile = $row['my_user'];
                $usero = $users[$profile] ?? User::getUser($profile);

                $url = "/user/" . $profile;
                $post = "viewed your profile or other content you have published";
                $user = $usero->username ?? '[unknown]';
                $name = empty($row['content_string']) ? '[unknown]' : $row['content_string'];
                $desc = $usero->description;
                $img = $usero->picture;
                $time = time_ago($row['time']);
                $followers_count = $fol_counts[$profile];
                $follows_you = $fol_you[$profile];

                switch ($row['type']):
                case 'user':
                    $post = "viewed your profile";
                    break;
                case 'creation':
                    $post = "viewed the creation " . $name;
                    break;
                case 'forum':
                    $post = "viewed the forum topic " . $name;
                    break;
                default:
                    $post = "viewed your profile or other content you have published";
                endswitch;

                echo "<article class='w3-card-2 w3-hover-shadow gr8-theme w3-light-grey w3-padding w3-round w3-large'>";
                echo "<a href='" . $url . "'><img src='" . $img . "' width='150' height='150px' class='w3-round' alt='" . $user . "' title='" . $user . "'>";
                echo "<span style='display: inline-block; vertical-align: top; padding: 5px;'>";
                echo "<b>" . htmlspecialchars($user) . "</b> " . htmlspecialchars($post) . "</span></a>";
                echo "<time class='w3-right w3-text-grey' datetime=''>" . $time . "</time>";
                echo "<br /><span>" . $bb->toHTML($desc, true, true) . "</span>";
                echo "<br /><span>Member since " . time_ago($usero->age) . "</span>";
                echo "<br /><span>" . $followers_count . " followers</span>";
                if($follows_you) { echo "<br /><span>" . 'Follows you' . "</span>"; }
                echo "</article><br />";
            }
            $result->free();
            $conn2->close();
		
		?><br /><br />
		
    <?php include '../linkbar.php' ?>
</body>
</html>