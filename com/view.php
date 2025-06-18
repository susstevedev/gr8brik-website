<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';


$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);

$post_id = $_GET['id'];

if ($conn->connect_error) {
	die("Error: " . $conn->connect_error);
}
		
$sql = "SELECT * FROM messages WHERE id = $post_id AND (parent IS NULL OR parent = 0)";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$userid = $row['userid'];
$title = $row['title'];
$post = $row['content'];
$date = $row['timestamp'];

$decoded_post = htmlentities($post, ENT_QUOTES, 'UTF-8');

if ($result->num_rows === 0) {
    header('HTTP/1.0 404 Not Found');
    echo 'Not Found';
    exit;
}
		
$conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);	
		
$sql = "SELECT * FROM users WHERE id = $userid";
$result = $conn2->query($sql);
$row = $result->fetch_assoc();
$username = $row['username'];

if(isset($_POST['comment'])){
	
	$comment = $_POST['commentbox'];
		
		$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}
		
		$date = date("Y-m-d H:i:s");
        $sql = "INSERT INTO messages (userid, parent, content, timestamp) VALUES (?, ?, ?, ?)";
        $stmt2 = $conn->prepare($sql);
        $stmt2->bind_param("iiss", $id, $post_id, $comment, $date);
        $stmt2->execute();
        $stmt2->close();
        $conn->close();

        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

        if($users_row['id'] != $userid) {
            $time = time();
            $content = $post_id;
            $category = 3;
            $sql = "INSERT INTO notifications (user, profile, timestamp, content, category) VALUES (?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisii", $userid, $id, $time, $content, $category);
            $stmt->execute();
            $stmt->close();

            $date = time();
            $sql = "SELECT alert FROM users WHERE id = ?";
            $stmt3 = $conn->prepare($sql);
            $stmt3->bind_param("i", $userid);
            $stmt3->bind_result($alert);
            $stmt3->execute();
            $stmt3->close();

            $alertnum = $alert + 1;
            $stmt4 = $conn->prepare("UPDATE users SET alert = ? WHERE id = ?");
            $stmt4->bind_param("si", $alertnum, $userid);

            if ($stmt->execute()) {
                $goto = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
                header('Location: ' . $goto);
            } else {
                echo "<script>alert('An error has occurred')</script>";
            }
            $stmt->close();
        }
        $conn->close();
	
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
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $count_sql = "SELECT COUNT(*) as reply_count FROM messages WHERE parent = '$post_id'";
    $count_result = $conn->query($count_sql);
    if (!$count_result) {
        die("Failed: " . $conn->error);
    }
    $count_row = $count_result->fetch_assoc();
    $reply_count = $count_row['reply_count'];

    $total_pages = ceil($reply_count / $limit);

    $sql_category = "SELECT status FROM messages WHERE id = '$post_id'";
    $category_result = $conn->query($sql_category);
    $category_row = $category_result->fetch_assoc();
    $category = $category_row['status'];

    if ($category === "deleted") {
        echo "<b id='commentboxcontainer'>This conversation has been removed because it violated our <a href='/rules'>rules</a>.</b><br />";
        exit;
    }

    $username = htmlspecialchars($row['username']);
    $isAdmin = $row['admin'] === '1' ? "color: red;" : "";

    echo "<div class='gr8-theme w3-container w3-light-grey w3-card-4'>";
    echo "<h2>" . $title . "</h2>";
    echo "<h4>By <a href='/user/" . $userid . "'>@" . $username . "</a> on " . $date . "</h4>";
    echo '<h4>' . $reply_count . ' replies, ' . $total_pages . ' pages, on page ' . $page . ', in ' . $category_row['status'] . '</h4></div><br />';

    $post_count_result = $conn->query("SELECT COUNT(*) as reply_count FROM messages WHERE userid = '$userid'");
    $post_count_row = $post_count_result->fetch_assoc();
    $post_count = $post_count_row['reply_count'];

    echo '<div class="w3-row" style="display:flex;width:100%;">';
    echo '<div class="gr8-theme w3-card-2 w3-light-grey w3-padding-tiny w3-margin-right" style="flex-shrink: 1; width: 20%;">';
    echo '<img id="pfp" src="/acc/users/pfps/' . $userid . '.jpg">';
    echo '<br /><a href="../user/' . $userid . '"><span style="text-overflow: ellipsis; ' . $isAdmin . '">' . $username . '</span></a>';
    echo '<br /><time title="' . $date . '" datetime="' . $date . '">Posted ' . time_ago($date) . '</time>';
    echo '<br /><span>' . $post_count . ' total posts</span></div>';
    echo '<span class="gr8-theme w3-card-2 w3-light-grey w3-padding-tiny" style="flex-shrink: 1; width: 80%;"><pre>';
    echo $bbcode->toHTML($decoded_post) . '</pre></span></div><br /><hr />';

    $sql = "SELECT * FROM messages WHERE parent = $post_id ORDER BY timestamp ASC LIMIT $limit OFFSET $offset";
    $comResult = $conn->query($sql);

    if ($comResult->num_rows > 0) {
        while ($row = $comResult->fetch_assoc()) {
            $c_user = $row['userid'];
            $c_comment = $row['content'];
            $c_date = $row['timestamp'];
            $decoded_comment = htmlentities($c_comment, ENT_QUOTES, 'UTF-8');

            $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
            if ($conn2->connect_error) {
                exit("Error: " . $conn2->connect_error);
            }

            $sql2 = "SELECT * FROM users WHERE id = ?";
            $stmt2 = $conn2->prepare($sql2);
            $stmt2->bind_param("i", $c_user);
            $stmt2->execute();
            $userResult = $stmt2->get_result();
            $userRow = $userResult->fetch_assoc();

            $c_username = htmlspecialchars($userRow['username']);
            $isAdmin = $userRow['admin'] === 1 ? "color: red;" : "";

            $user_post_count_result = $conn->query("SELECT COUNT(*) as reply_count FROM messages WHERE userid = '$c_user'");
            $user_post_count_row = $user_post_count_result->fetch_assoc();
            $user_post_count = $user_post_count_row['reply_count'];

            echo '<div class="w3-row" style="display:flex;width:100%;">';
            echo '<div class="gr8-theme w3-card-2 w3-light-grey w3-padding-tiny w3-margin-right" style="flex-shrink: 1; width: 20%;">';
            echo '<img id="pfp" src="/acc/users/pfps/' . $c_user . '.jpg">';
            echo '<br /><a href="../user/' . $c_user . '"><span style="text-overflow: ellipsis; ' . $isAdmin . '">' . $c_username . '</span></a>';
            echo '<br /><time title="' . $c_date . '" datetime="' . $c_date . '">Posted ' . time_ago($c_date) . '</time>';
            echo '<br /><span>' . $user_post_count . ' total posts</span></div>';
            echo '<span class="gr8-theme w3-card-2 w3-light-grey w3-padding-tiny" style="flex-shrink: 1; width:80%;"><pre>';
            echo $bbcode->toHTML($decoded_comment) . '</pre></span></div><br />';
        }

        echo '<a class="w3-btn w3-blue w3-hover-white w3-mobile w3-border w3-border-indigo" href="?id=' . $post_id . '&p=' . ($page - 1) . '">Back</a>&nbsp;';
        echo '<a class="w3-btn w3-blue w3-hover-white w3-mobile w3-border w3-border-indigo" href="?id=' . $post_id . '&p=' . ($page + 1) . '">Next</a>';
    } else {
        echo "<b>No replies yet.</b><br />";
        echo '<a class="w3-btn w3-blue w3-hover-white w3-mobile w3-border w3-border-indigo" href="?id=' . $post_id . '&p=' . ($page - 1) . '">Back</a>';
    }

    $words = ['Spark something about flying cows', 'Start a MOC contest', 'Pigs! Its all about pigs!', 'Undefined!' , 'Dont use Javascript on the server side!', 'Oops I think I changed the padding on that button by ~1 megagiga pixel!'];
    $randomKeys = array_rand($words);
    $randomWord = $words[$randomKeys];

    echo "<br /><hr />";

    echo '<noscript>
            <style type="text/css">
                #commentboxcontainer {
                    display: none;
                }
            </style>
            <div class="unsupported">
                <b>Please enable Javascript in your web browser to post a reply.</b><br />
            </div>
        </noscript>';

    if ($category === "pinnedLocked" || $category === "locked") {
        echo "<b id='commentboxcontainer'>This conversation is locked. New replies cannot be posted.</b><br />";
	} else {
        if(isset($_SESSION['username']) || isset($token['user'])) {
            echo "<br /><form id='commentboxcontainer' method='post' action=''>";
            echo "<textarea name='commentbox' placeholder='" . $randomWord . "' rows='4' cols='50'></textarea><br />";
            echo "<altcha-widget challengeurl='https://eu.altcha.org/api/v1/challenge?apiKey=ckey_01d9f4ad018c16287ca6f3938a0f'></altcha-widget>";
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