<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';

if (!loggedin()) {
    header('Location:login.php');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <title>Notifications</title>
    <?php include '../header.php' ?>
</head>

<body class="w3-light-blue w3-container">
    <?php
        include '../navbar.php';
        include 'panel.php';

        try {
            $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo "<p>Unable to load notifications at this time.</p>";
            return;
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }

        $limit = 8;
        $offset = ($page - 1) * $limit;
        $pDown = $page - 1;
        $pUp = $page + 1;
        $sql = "SELECT * FROM notifications WHERE user = ? AND category2 IS NOT NULL ORDER BY timestamp DESC";

        try {
            $thisuser = $current_user->id;
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $thisuser);
            $stmt->execute();
            $result = $stmt->get_result();
            $grouped_notifications = [];

            if ($result->num_rows !== 0 && $current_user->alert > 0) {
                $alertsql = "UPDATE users SET alert = 0 WHERE id = ?";
                $alertstmt = $conn->prepare($alertsql);
                $alertstmt->bind_param("i", $thisuser);
                if ($alertstmt->execute()) {
                    $alertstmt->close();
                } else {
                    echo $alertstmt->error;
                    exit;
                }
            }

            while ($row = $result->fetch_assoc()) {
                $profile = $row['profile'];
                $content = $row['content'];
                $category = $row['category2'];
                $timestamp = is_numeric($row['timestamp']) ? (int)$row['timestamp'] : time();

                $user_data_o = User::getUser($profile);
                $username = $user_data_o->username ?: '[unknown]';
                $userid = $user_data_o->id ?: 0;

                //groups matching content together
                if ($category === 'profile') {
                    $group_key = $category . "_" . date("Y-m-d", $timestamp);
                } else {
                    $group_key = $category . "_" . $row['content'] . "_" . date("Y-m-d", $timestamp);
                }

                if (!isset($grouped_notifications[$group_key])) {
                    $grouped_notifications[$group_key] = [
                        'category' => $category,
                        'content' => $content,
                        'timestamp' => $timestamp,
                        'users' => [],
                        'fallback_pic' => $user_data_o->picture ?: '/img/no_image.png'
                    ];
                }

                if (!in_array($userid, array_column($grouped_notifications[$group_key]['users'], 'id'))) {
                    $grouped_notifications[$group_key]['users'][] = [
                        'name' => $username,
                        'id'   => $userid
                    ];
                }
            }

            $total_groups = count($grouped_notifications);
            $sliced_notifications = array_slice($grouped_notifications, $offset, $limit);
            print_r($sliced_notifications);

            if ($page > 1) {
                echo '<a class="w3-btn w3-blue w3-hover-opacity w3-round w3-border w3-border-indigo" href="?page=' . $pDown . '">Back</a>&nbsp;&nbsp;';
            }
            if (($offset + $limit) < $total_groups) {
                echo '<a class="w3-btn w3-blue w3-hover-opacity w3-round w3-border w3-border-indigo" href="?page=' . $pUp . '">Next</a>';
            }
            echo '<hr />';
            $stmt->close();

            foreach ($sliced_notifications as $group_name => $group) {
                $url = null;
                $post = null;
                $img = null;
                $valid = false;

                $category = $group['category'];
                $content = $group['content'];
                $users = $group['users'];
                $user_count = count($users);

                if ($user_count === 1) {
                    $user_string = $users[0]['name'];
                } elseif ($user_count === 2) {
                    $user_string = $users[0]['name'] . " and " . $users[1]['name'];
                } else {
                    $user_string = $users[0]['name'] . " and " . ($user_count - 1) . " others";
                }

                // --- CATEGORY 1: Follows ---
                if ($category === 'follow') {
                    $valid = true;

                    if ($user_count > 1) {
                        $url = "/acc/following?p=followerstab";
                    } else {
                        $url = "/user/" . $group['users'][0]['id'];
                    }

                    $post = ($user_count > 1 ? "followed you " : "followed you ");
                    $img = $group['fallback_pic'];
                    // --- CATEGORY 2: Model Comments ---
                } elseif ($category === 'comment') {
                    $stmt2 = $conn->prepare("SELECT screenshot, name FROM `" . DB_NAME2 . "`.`model` WHERE id = ?");
                    $stmt2->bind_param("i", $content);
                    $stmt2->execute();
                    $res2 = $stmt2->get_result();
                    $row2 = $res2->fetch_assoc();

                    $valid = true;
                    $img = $row2['screenshot'];
                    $url = "/build/" . urlencode($content);
                    $post = ($user_count > 1 ? 'commented on ' : 'commented on ') . ($row2['name'] ?: '[unknown]');
                    $stmt2->close();
                    // --- CATEGORY 3: Topic Replies ---
                } elseif ($category === 'forum_reply') {
                    $stmt2 = $conn->prepare("SELECT title FROM `" . DB_NAME3 . "`.`messages` WHERE id = ?");
                    $stmt2->bind_param("i", $content);
                    $stmt2->execute();
                    $res2 = $stmt2->get_result();
                    $row2 = $res2->fetch_assoc();

                    $valid = true;
                    $img = '../img/com.jpg';
                    $url = "/topic/" . urlencode($content);
                    $title = !empty($row2['title']) ? $row2['title'] : "[unknown]";
                    $post = ($user_count > 1 ? "replied to " : "replied to ") . $title;

                    $stmt2->close();
                    // --- CATEGORY 4: Admin creation removals ---
                } elseif ($category === 'creation_remove') {
                    $stmt2 = $conn->prepare("SELECT screenshot, name FROM `" . DB_NAME2 . "`.`model` WHERE id = ?");
                    $stmt2->bind_param("i", $content);
                    $stmt2->execute();
                    $res2 = $stmt2->get_result();
                    $row2 = $res2->fetch_assoc();

                    $valid = true;
                    $img = $row2['screenshot'];
                    $url = "/build/" . urlencode($content);
                    $title = !empty($row2['name']) ? $row2['name'] : "[unknown]";
                    $post = $title . " was removed";

                    $stmt2->close();
                    // --- CATEGORY 5: Model Favorites ---
                } elseif ($category === 'creation_fav') {
                    $stmt2 = $conn->prepare("SELECT screenshot, name FROM `" . DB_NAME2 . "`.`model` WHERE id = ?");
                    $stmt2->bind_param("i", $content);
                    $stmt2->execute();
                    $res2 = $stmt2->get_result();

                    if ($row2 = $res2->fetch_assoc()) {
                        $valid = true;
                        $img = $row2['screenshot'];
                        $url = "/build/" . urlencode($content);
                        $post = ($user_count > 1 ? "favorited " : "favorited ") . ($row2['name'] ?: '[unknown]');
                    }
                    $stmt2->close();
                }

                $time = time_ago(date("Y-m-d H:i:s", $group['timestamp']));

                if ($valid === true) {
        ?>
            <article id="<?php echo $group_name ?>" class='w3-card-4 w3-hover-opacity gr8-theme w3-padding w3-round w3-large'>
                <a href="<?php echo htmlspecialchars($url); ?>"><img src="<?php echo htmlspecialchars($img); ?>" class="w3-round" style='background: #ddd; width: 150px; height: 150px;' alt='Image' title='Image'></a>
                <a href="<?php echo htmlspecialchars($url); ?>"><span style='display: inline-block; vertical-align: top; padding: 10px;'>
                        <strong><?php echo htmlspecialchars($user_string); ?></strong> <?php echo htmlspecialchars($post); ?>
                    </span></a>
                <time class='w3-right'><?php echo htmlspecialchars($time); ?></time>
            </article>
            <br />
        <?php
            }
        }
            $conn->close();
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo "<p>Error loading some notifications.</p>";
        }
        ?><br /><br />
    <?php include '../linkbar.php' ?>
</body>

</html>