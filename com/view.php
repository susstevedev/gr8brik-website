<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';


$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);

$post_id = $_GET['id'];

if ($conn->connect_error) {
	die("Error: " . $conn->connect_error);
}
		
$sql = "SELECT * FROM posts WHERE id = $post_id";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$userid = $row['user'];
$title = $row['title'];
$post = $row['post'];
$date = $row['date'];

$decoded_post = htmlentities($post, ENT_QUOTES, 'UTF-8');

if ($result->num_rows === 0) {
    header('HTTP/1.0 404 Not Found');
    echo 'Not Found';
    exit;
}
		
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);	
if ($conn->connect_error) {
	die("Error: " . $conn->connect_error);
}
		
$sql = "SELECT * FROM users WHERE id = $userid";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$username = $row['username'];

if(isset($_POST['comment'])){
	
	$comment = $_POST['commentbox'];
		
		// Create connection
		$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
			
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}
		
		$date = date("Y-m-d H:i:s");
        $sql = "INSERT INTO replies (user, reply, post, date) VALUES (?, ?, ?, ?)";
        $stmt2 = $conn->prepare($sql);
        $stmt2->bind_param("iiss", $id, $post_id, $comment, $date); // Assuming 'user' and 'model' are strings
        $stmt2->execute();
        $stmt2->close();
        $conn->close();

        // Create connection
		$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
			
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}

        $notifName = " commented on your post!";
        $notifPost = "Click to view your post!";
        $notifTime = time();
        $notifUrl = "/com/view.php?id=" . $post_id;
        $sql = "INSERT INTO notifications (user, name, profile, post, timestamp, url) VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $userid, $notifName, $id, $notifPost, $notifTime, $notifUrl);
        $stmt->execute();
        $stmt->close();

        $alertnum = '1';
        $stmt = $conn->prepare("UPDATE users SET alert = ? WHERE id = ?");
        $stmt->bind_param("si", $alertnum, $userid);

        if ($stmt->execute()) {
            $goto = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
            header('Location: ' . $goto);
        } else {
            echo "An error has occurred";
        }
        $stmt->close();
        $conn->close();
	
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $title ?></title>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="../lib/theme.css">
    <script src="../lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script async defer src="https://cdn.jsdelivr.net/npm/altcha/dist/altcha.min.js" type="module"></script>
    <meta charset="UTF-8">
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
    require_once "bbcode.php";
    $bbcode = new BBCode;	
	echo "<h2 class='w3-container w3-light-grey w3-card-2 w3-text-grey'>Community -> " . $title . "</h2>";

    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
    if ($conn->connect_error) {
        die("Error: " . $conn->connect_error);
    }

    $post_id = $_GET['id'];
    $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $count_sql = "SELECT COUNT(*) as reply_count FROM replies WHERE reply = $post_id";
    $count_result = $conn->query($count_sql);
    if (!$count_result) {
        die("Failed: " . $conn->error);
    }
    $count_row = $count_result->fetch_assoc();
    $reply_count = $count_row['reply_count'];

    $total_pages = ceil($reply_count / $limit);

    $sql_category = "SELECT category FROM posts WHERE id = $post_id";
    $category_result = $conn->query($sql_category);
    $category_row = $category_result->fetch_assoc();
    $category = $category_row['category'];

    if ($category === "deleted") {
        echo "<b id='commentboxcontainer'>This conversation has been deleted.</b><br />";
        exit;
    }

    echo '<h3>' . $reply_count . ' replies, ' . $total_pages . ' pages. Showing page ' . $page . '.</h3>';

    echo '<div class="w3-row" style="display:flex;width:100%">';
    echo '<div class="w3-card-2 w3-light-grey w3-padding-tiny w3-margin-right" style="flex-shrink:0;width:15%;">';
    echo '<img id="pfp" src="../acc/users/pfps/' . $userid . '.jpg">';
    echo '<br /><a href="../@' . urlencode($username) . '">' . urlencode($username) . '</a>';
    echo '<br /><time class="w3-text-grey" datetime="' . $date . '">' . $date . '</time></div>';
    echo '<h4 class="w3-card-2 w3-light-grey w3-padding-tiny" style="flex-shrink:0;width:85%;"><pre>';
    echo $bbcode->toHTML($decoded_post) . '</pre></h4></div><hr />';

    $sql = "SELECT * FROM replies WHERE reply = $post_id ORDER BY date ASC LIMIT $limit OFFSET $offset";
    $comResult = $conn->query($sql);

    if ($comResult->num_rows > 0) {
        while ($row = $comResult->fetch_assoc()) {
            $c_user = $row['user'];
            $c_comment = $row['post'];
            $c_date = $row['date'];
            $decoded_comment = htmlentities($c_comment, ENT_QUOTES, 'UTF-8');

            $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
            if ($conn2->connect_error) {
                die("Error: " . $conn2->connect_error);
            }
            $sql2 = "SELECT * FROM users WHERE id = ?";
            $stmt2 = $conn2->prepare($sql2);
            $stmt2->bind_param("i", $c_user);
            $stmt2->execute();
            $userResult = $stmt2->get_result();
            $userRow = $userResult->fetch_assoc();
            $c_username = $userRow['username'];

            echo '<div class="w3-row" style="display:flex;width:100%;">';
            echo '<div class="w3-card-2 w3-light-grey w3-padding-tiny w3-margin-right" style="flex-shrink:0;width:15%;">';
            echo '<img id="pfp" src="../acc/users/pfps/' . $c_user . '.jpg">';
            echo '<br /><a href="../@' . urlencode($c_username) . '">' . urlencode($c_username) . '</a>';
            echo '<br /><time class="w3-text-grey" datetime="' . $c_date . '">' . $c_date . '</time></div>';
            echo '<h4 class="w3-card-2 w3-light-grey w3-padding-tiny" style="width:85%;"><pre>';
            echo $bbcode->toHTML($decoded_comment) . '</pre></h4></div><br />';
        }

        echo '<a class="w3-btn w3-blue w3-hover-white w3-mobile w3-border w3-border-indigo w3-round" href="?id=' . $post_id . '&p=' . ($page - 1) . '">Back</a>';
        echo '<a class="w3-btn w3-blue w3-hover-white w3-mobile w3-border w3-border-indigo w3-round" href="?id=' . $post_id . '&p=' . ($page + 1) . '">Next</a>';
    } else {
        echo "<b>Nothing here yet</b><br />";
        echo '<a class="w3-btn w3-blue w3-hover-white w3-mobile w3-border w3-border-indigo w3-round" href="?id=' . $post_id . '&p=' . ($page - 1) . '">Back</a>';
    }

    echo "<br /><hr />";

    echo '<noscript>
            <style type="text/css">
                #commentboxcontainer {
                    display: none;
                }
            </style>
            <div class="unsupported">
                <b>Please enable Javascript to comment.</b><br />
            </div>
        </noscript>';

    if ($category === "pinnedLocked" || $category === "locked") {
        echo "<b id='commentboxcontainer'>This conversation is locked.</b><br />";
	} else {
        if(isset($_SESSION['username'])) {
            echo "<br /><form id='commentboxcontainer' method='post' action=''>";
            echo "<textarea name='commentbox' placeholder='Post body' rows='4' cols='50'></textarea><br />";
            echo "<altcha-widget challengeurl='https://eu.altcha.org/api/v1/challenge?apiKey=ckey_01d9f4ad018c16287ca6f3938a0f'></altcha-widget>";
            echo "<input type='submit' value='Post' name='comment' class='w3-btn w3-blue w3-hover-white w3-mobile w3-border w3-border-indigo w3-round' />";
            echo "</form><br />";
        } else {
            echo "<b id='commentboxcontainer'>Please <a href='../acc/login'>Login</a> to join the conversation.</b><br />";
        }
    }

    include('../linkbar.php');

?>

</body>
</html>