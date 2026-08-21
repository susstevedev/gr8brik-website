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
                $username = htmlspecialchars($user_data_o->username) ?: '[unknown]';
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
                    $user_string = "<a href='/user/" . $users[0]['id'] . "'>" . $users[0]['name'] . "</a>";
                } elseif ($user_count === 2) {
                    $user_string = "<a href='/user/" . $users[0]['id'] . "'>" . $users[0]['name'] . "</a> and <a href='/user/" . $users[1]['id'] . "'>" . $users[1]['name'] . "</a>";
                } else {
                    $user_string = "<a href='/user/" . $users[0]['id'] . "'>" . $users[0]['name'] . "</a> and " . ($user_count - 1) . " others";
                }

                // --- CATEGORY 1: Follows ---
                if ($category === 'follow') {
                    $valid = true;

                    if ($user_count > 1) {
                        $url = "/acc/following?p=followerstab";
                    } else {
                        $url = "/user/" . $group['users'][0]['id'];
                    }

                    $title = $user_string;
                    $post = 'followed you';
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
                    $title = !empty($row2['name']) ? $row2['name'] : "[unknown]";
                    $post = 'commented on by';
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
                    $post = "replied to by";

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
                    $post = $title . " was removed by";

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
                        $title = !empty($row2['name']) ? $row2['name'] : "[unknown]";
                        $post = "Favorited by";
                    }
                    $stmt2->close();
                }

                $time = time_ago(date("Y-m-d H:i:s", $group['timestamp']));

                if ($valid === true) {
        ?>
            <article id="<?php echo $group_name ?>" class='w3-card-4 w3-hover-shadow gr8-theme w3-padding w3-round w3-large'>
                <div class="w3-row">
                    <div class="w3-col s2 m3">
                        <img src="<?php echo htmlspecialchars($img); ?>" class="w3-round" style='background: #ddd; height: 150px;' alt='Image' title='Image'>
                    </div>

                    <div class="w3-col s6 m7">
                        <a href="<?php echo htmlspecialchars($url); ?>">
                            <strong><?php echo htmlspecialchars($title); ?></strong>
                        </a><br />
                        <?php echo htmlspecialchars($post); ?> <?php echo $user_string ?> 
                    </div>

                    <time class='w3-right-align w3-col m2'><?php echo htmlspecialchars($time); ?></time>
                </div>
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