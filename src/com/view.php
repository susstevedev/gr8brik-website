<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';

if (loggedin()) {
    $id = $current_user->id;
}

$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);

$post_id = htmlspecialchars($_GET['id']);

if ($conn->connect_error) {
    exit($conn->connect_error);
}

$stmt = $conn->prepare("SELECT id, userid, username, title, timestamp, views, status FROM messages WHERE id = ? AND (parent IS NULL OR parent = 0) AND deleted_at IS NULL LIMIT 1");
$stmt->bind_param("i", $post_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows <= 0) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$row = $result->fetch_assoc();
$stmt->close();

$userid = $row['userid'];
$title = $row['title'];
$date = $row['timestamp'];
$views = $row['views'];
$category = $row['status'];

if (!empty($row['edited'])) {
    $edit_date = $row['edited'];
} else {
    $edit_date = $row['timestamp'];
}

$conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

if (!$row['username']) { //check for anonymous posting
    $user = User::getUser($userid);
    $isAdmin = $user->admin === '1' ? "w3-text-red" : "";
    $username = $user->username;
} else {
    $isAdmin = false;
    $username = $row['username'];
}

if (!isset($_SESSION['viewed_post_ids'])) {
    $_SESSION['viewed_post_ids'] = [];
}

if (!in_array($post_id, $_SESSION['viewed_post_ids'])) {
    $view_stmt = $conn->prepare("UPDATE messages SET views = views + 1 WHERE id = ?");
    $view_stmt->bind_param("i", $post_id);
    $view_stmt->execute();
    $_SESSION['viewed_post_ids'][] = $post_id;
}

if (isset($_POST['comment_delete'])) {
    if ($conn->connect_error) {
        exit("Database connection failed.");
    }

    $commentid = (int)$_POST['commentid'];
    if ($commentid <= 0) {
        exit('Invalid message ID.');
    }

    $sql = "SELECT userid FROM messages WHERE id = ? AND (parent IS NOT NULL AND parent != 0) LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $commentid);
    $stmt->execute();
    $result = $stmt->get_result();
    $mrow = $result->fetch_assoc();
    $stmt->close();

    if (!$mrow) {
        exit('Message with that ID does not exist.');
    }

    if (!loggedin()) {
        exit('You are not logged in.');
    }

    if (!$current_user->admin || (int)$mrow['userid'] !== (int)$id) {
        exit('You are not allowed to delete this message.');
    }

    //old: $delete_sql = "DELETE FROM messages WHERE id = ? LIMIT 1";
    $delete_sql = "UPDATE messages SET deleted_at = NOW() WHERE id = ? LIMIT 1";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $commentid);
    $delete_result = $delete_stmt->execute();
    $delete_stmt->close();
    $conn->close();

    if ($delete_result) {
        $goto = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');
        if (!empty($_SERVER['QUERY_STRING'])) {
            $goto .= '?' . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES, 'UTF-8');
        }
        header('Location: ' . $goto);
        exit;
    }
}

if (isset($_POST['comment'])) {
    $comment = $_POST['commentbox'];
    if ($conn->connect_error) {
        exit("Connection failed: " . $conn->connect_error);
    }

    if ($comment === "" || $comment === null) {
        echo "Message shall contain text.";
        exit;
    }

    $username = htmlspecialchars($_POST['username']);

    if ($username) {
        $_SESSION['forum_anonymous_username'] = $username;
    }

    if (isset($category) && $category == "nolist" && loggedin() === false) {
        if (!$username) {
            exit('No username provided.');
        }
        $date = date("Y-m-d H:i:s");
        $sql = "INSERT INTO messages (username, parent, content, timestamp) VALUES (?, ?, ?, ?)";
        $stmt2 = $conn->prepare($sql);
        $stmt2->bind_param("siss", $username, $post_id, $comment, $date);
    } else {
        if (!$loggedin) {
            exit('Please login to post messages.');
        }
        $date = date("Y-m-d H:i:s");
        $sql = "INSERT INTO messages (userid, parent, content, timestamp) VALUES (?, ?, ?, ?)";
        $stmt2 = $conn->prepare($sql);
        $stmt2->bind_param("iiss", $id, $post_id, $comment, $date);
    }

    if (!$stmt2->execute()) {
        echo "An error has occured. Please try again later.";
        exit;
    }
    $stmt2->close();

    $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
    $offset = ($page - 1) * 10;
    $count_result = $conn->query("SELECT COUNT(*) as reply_count FROM messages WHERE parent = '$post_id'");
    $reply_count = $count_result->fetch_assoc()['reply_count'];
    $total_pages = ceil($reply_count / 10);

    $time = time();
    $sql = "UPDATE messages SET last_active_time = ?, last_page = ?, last_posted = ? WHERE id = ?";
    $stmt3 = $conn->prepare($sql);
    $stmt3->bind_param("iiii", $time, $total_pages, $id, $post_id);

    $conn3 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    $category = 3; //3 = forum reply

    $stmt = $conn3->prepare("INSERT IGNORE INTO subscriptions (userid, category, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $id, $category, $post_id);
    $stmt->execute();

    if ($stmt3->execute()) {
        $time = time();
        $content = $post_id;
        $category = 3; //3 = forum reply

        $sub_stmt = $conn3->prepare("SELECT userid FROM subscriptions WHERE content = ? AND category = ? AND userid != ?");
        $sub_stmt->bind_param("iii", $content, $category, $id);
        $sub_stmt->execute();
        $result = $sub_stmt->get_result();

        $subscribers = [];
        while ($row = $result->fetch_assoc()) {
            $subscribers[] = $row['userid'];
        }
        $sub_stmt->close();

        if (!empty($subscribers)) {
            $notif_stmt = $conn3->prepare("INSERT INTO notifications (user, profile, timestamp, content, category) VALUES (?, ?, ?, ?, ?)");
            $alert_stmt = $conn3->prepare("UPDATE users SET alert = alert + 1 WHERE id = ?");

            foreach ($subscribers as $subscriber_id) {
                $notif_stmt->bind_param("iisii", $subscriber_id, $id, $time, $content, $category);
                $notif_stmt->execute();

                $alert_stmt->bind_param("i", $subscriber_id);
                $alert_stmt->execute();
            }

            $notif_stmt->close();
            $alert_stmt->close();
        }

        $goto = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
        header('Location: ' . $goto);
    } else {
        echo "An error has occured. Please try again later.";
        exit;
    }
    $stmt3->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title><?php echo $title ?></title>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/header.php' ?>
</head>

<body class="w3-light-blue w3-container">
    <?php
    include $_SERVER['DOCUMENT_ROOT'] . '/navbar.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . "/com/bbcode.php";
    require_once $_SERVER['DOCUMENT_ROOT'] . "/ajax/time.php";
    $bbcode = new BBCode;

    $post_id = htmlspecialchars($_GET['id']);
    $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
    if ($page < 1) {
        $page = 1;
    }
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $count_sql = "SELECT COUNT(*) as reply_count FROM messages WHERE parent = '$post_id'";
    $count_result = $conn->query($count_sql);
    $count_row = $count_result->fetch_assoc();
    $reply_count = $count_row['reply_count'];

    $total_pages = ceil($reply_count / $limit);

    if (isset($error)) {
        echo '<div class="message w3-padding w3-round w3-red">' . $error . '</div><br /><br />';
        exit;
    }

    if ($category === "deleted") {
        echo "<b id='deletedwarning'>This conversation has been removed because it violated our <a href='/rules'>rules</a>.</b><br />";
        exit;
    }

    if ($category === "nolist") {
        echo "<b id='unlistedwarning'>This forum is unlisted, only people with the link can view it.</b><br />";
    }
    ?>

    <div class='gr8-theme w3-container w3-light-grey w3-card-4 w3-round-small'>
        <h2><?php echo $title ?></h2>
        <h4>By <a href='/user/<?php echo $userid ?>'><?php echo $username ?></a> on <?php echo $date ?>, edited <?php echo $edit_date . ". " . $views ?> total views.</h4>
        <h4><?php echo $reply_count ?> replies, <?php echo $total_pages ?> pages, on page <?php echo $page ?>, in <?php echo $category ?></h4>
    </div><br />

    <?php
    $sql = "SELECT * FROM messages WHERE (parent = $post_id OR id = $post_id) AND deleted_at IS NULL ORDER BY timestamp ASC LIMIT $limit OFFSET $offset";
    $comResult = $conn->query($sql);

    if ($comResult->num_rows > 0) {
        while ($row = $comResult->fetch_assoc()) {
            $c_user = $row['userid'];
            $c_comment = $row['content'];
            $c_date = $row['timestamp'];
            $decoded_comment = htmlentities($c_comment, ENT_QUOTES, 'UTF-8');

            $c_edited = $row['edited'];
            if (empty($row['edited'])) {
                $c_edited = $row['timestamp'];
            }

            $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
            if ($conn2->connect_error) {
                exit($conn2->connect_error);
            }

            $c_user_o = User::getUser($c_user);
            $c_user = $c_user_o->id;

            if (!User::isDeleted($c_user)) {
                $c_username = htmlspecialchars($c_user_o->username);
                $isAdmin = $c_user_o->admin === 1 ? "w3-text-red" : "";
                $pfp = $c_user_o->picture ?: '/img/user.png';
            } else {
                $c_username = $row['username'] ?? null;
                $isAdmin = false;
                $pfp = '/img/user.png';
            }

            $user_post_count_result = $conn->query("SELECT COUNT(*) as reply_count FROM messages WHERE userid = '$c_user'");
            $user_post_count_row = $user_post_count_result->fetch_assoc();
            $user_post_count = $user_post_count_row['reply_count'];

    ?>

            <div class="w3-row" style="display:flex;width:100%;">
                <div class="gr8-theme w3-card-2 w3-light-grey w3-padding-small w3-round-small w3-margin-right" style="flex-shrink: 1; width: 20%;">
                    <img id="pfp" src="<?php echo $pfp ?>"><br />
                    <a href="../user/<?php echo $c_user ?>">
                        <span class="<?php echo $isAdmin ?>" style="text-overflow: ellipsis;">
                            <?php echo $c_username ?>
                        </span>
                    </a><br />
                    <time title="<?php echo $c_date ?>" datetime="<?php echo $c_date ?>">Posted <?php echo time_ago($c_date) ?></time><br />
                    <time title="<?php echo $c_edited ?>" datetime="<?php echo $c_edited ?>">Edited <?php echo time_ago($c_edited) ?></time><br />
                    <span><?php echo $user_post_count ?> total posts</span>
                </div>
                <span class="gr8-theme w3-card-2 w3-light-grey w3-padding-small w3-round-small" style="flex-shrink: 1; width:80%;">
                    <pre><?php echo $bbcode->toHTML($bbcode->Smilify($decoded_comment), false, true) ?></pre>
                </span>
                <?php
                if (loggedin()) {
                    if ($current_user->admin || trim($current_user->id) === trim($c_user)) {
                        echo "<form id='delete_comment' method='post'><input type='hidden' name='commentid' value=" . $row['id'] . " /></form>";
                        echo "<button form='delete_comment' type='submit' name='comment_delete' class='w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink'>
                        <i class='fa fa-trash' aria-hidden='true'></i>
                    </button>";
                    }
                }
                ?>
            </div><br />
        <?php
        }
        ?>
        <br />
    <?php
        echo '<a class="w3-btn w3-blue w3-hover-white w3-mobile w3-border w3-border-indigo" href="?id=' . $post_id . '&p=' . ($page - 1) . '">Back</a>&nbsp;';
        echo '<a class="w3-btn w3-blue w3-hover-white w3-mobile w3-border w3-border-indigo" href="?id=' . $post_id . '&p=' . ($page + 1) . '">Next</a>';
    } else {
        echo "<p>No replies yet.</p><br />";
        echo '<a class="w3-btn w3-blue w3-hover-white w3-mobile w3-border w3-border-indigo" href="?id=' . $post_id . '&p=' . ($page - 1) . '">Back</a>';
    }

    $words = ['Spark something about flying cows', 'Start a MOC contest', 'Pigs! Its all about pigs!', 'Undefined!', 'Dont use Javascript on the server side!', 'Oops, I think I changed the padding on that button by ~0.01% of a pixel!', 'Moderation! Amazing!', 'Woaw!', 'Obviously', 'Sixty five, sixty six, sixty... why do I bother. You already get it.'];
    $randomKeys = array_rand($words);
    $randomWord = $words[$randomKeys];

    echo "<br /><hr />";
    if ($category === "pinnedLocked" || $category === "locked") {
        echo "<b id='commentboxcontainer'>This conversation is locked. New replies cannot be posted.</b><br />";
    } else if ($category === "nolist") {
        if (loggedin()) {
            $forum_anonymous_username = $current_user->username;
        } else {
            $forum_anonymous_username = $_SESSION['forum_anonymous_username'];
        }
        echo "<b>You can only post anonymous comments on unlisted forums.</b>";
        echo "<br /><form id='commentboxcontainer' method='post' action=''>";
        echo "<input type='text' value='" . $forum_anonymous_username . "' placeholder='Name' name='username' /><br />";
        echo "<textarea name='commentbox' placeholder='" . $randomWord . "' rows='4' cols='50'></textarea><br />";
        echo "<input type='submit' value='Reply' name='comment' class='w3-btn w3-blue w3-hover-white w3-mobile w3-border w3-border-indigo' />";
        echo "</form><br />";
    } else {
        if (loggedin()) {
            echo "<br /><form id='commentboxcontainer' method='post' action=''>";
            echo "<textarea name='commentbox' placeholder='" . $randomWord . "' rows='4' cols='50'></textarea><br />";
            echo "<input type='submit' value='Reply' name='comment' class='w3-btn w3-blue w3-hover-white w3-mobile w3-border w3-border-indigo' />";
            echo "</form><br />";
        } else {
            echo "<b id='commentboxcontainer'>Please <a href='../acc/login'>login</a> to post a reply.</b><br />";
        }
    }

    include $_SERVER['DOCUMENT_ROOT'] . '/linkbar.php';
    ?>
</body>

</html>