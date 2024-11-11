<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';

define('DB_NAME2', 'if0_36019408_creations');
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
			
if ($conn->connect_error) {
	exit($conn->connect_error);
}
			
$model_id = $conn->real_escape_string($_GET['id']);

if (!is_numeric($model_id)){
    header('HTTP/1.0 403 Forbidden');
    echo "Invalid creation ID";
    exit;
}

$sql = "SELECT * FROM model WHERE id = $model_id";
$result = $conn->query($sql);
$row2 = $result->fetch_assoc();

$userid = $row2['user'];
$model = $row2['model'];
$description = $row2['description'];
$name = $row2['name'];
$date = $row2['date'];
$screenshot = $row2['screenshot'];
if ($result->num_rows < 0) {
    header('HTTP/1.0 404 Not Found');
    echo 'Creation does not exist.';
    exit;
}

$decoded_description = htmlentities($description, ENT_QUOTES, 'UTF-8');
		
$conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
if ($conn2->connect_error) {
	exit($conn2->connect_error);
}
		
$sql = "SELECT * FROM users WHERE id = $userid";
$result = $conn2->query($sql);
$row = $result->fetch_assoc();
$username = $row['username'];
$username_desc = htmlentities($row['description'], ENT_QUOTES, 'UTF-8');

if (isset($_POST['downvote'])) {
    $sql = "DELETE FROM votes WHERE user = $id AND creation = $model_id";
    $result = $conn->query($sql);

    if ($result) {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']);
        exit;
    } else {
        echo $conn->error;
        exit;
    }

    $stmt->close();
    $conn->close();
    die;
}

if (isset($_POST['upvote'])) {
    $id = $_SESSION['username'];
    $sql = "INSERT INTO votes (creation, user) VALUES ($model_id, $id)";
    $result = $conn->query($sql);

    if ($result) {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']);
        exit;
    } else {
        echo $conn->error;
        exit;
    }

    $stmt->close();
    $conn->close();
    die;
}

if(isset($_POST['comment'])){
	
	$comment = $_POST['commentbox'];
		
	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

    if(!isset($_SESSION['username']) || empty($comment)) {
        header("HTTP/1.0 500 Internal Server Error");
        exit;
    }
		
	$date = time();
    $sql = "INSERT INTO comments (user, model, comment, date) VALUES (?, ?, ?, ?)";
    $stmt2 = $conn->prepare($sql);
    $stmt2->bind_param("iiss", $id, $model_id, $comment, $date);
    $stmt2->execute();
    $stmt2->close();
    $conn->close();

	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

    $time = time();
    $content = $_GET['id'];
    $category = 2;
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
    $stmt = $conn->prepare("UPDATE users SET alert = ? WHERE id = ?");
    $stmt->bind_param("si", $alertnum, $userid);

    if ($stmt->execute()) {
        echo "<script>alert('You commented!')</script>";
    } else {
        echo "<script>alert('An error has occurred')</script>";
    }
    $stmt->close();
    $conn->close();
	
}
if (isset($_POST['delete'])) {
    $commentId = $_POST['deleteId'];
	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
	if ($conn->connect_error) {
		exit($conn->connect_error);
	}
    if($admin != 1) {
        exit("You are not an admin");
    }
	$sql = "DELETE FROM comments WHERE id = $commentId";
	$result = $conn->query($sql);
    $conn->close();
}

include('com/bbcode.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $name ?></title>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="/lib/theme.css">
    <script src="/lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/jquery-migrate-1.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/altcha-org/altcha@main/dist_plugins/obfuscation.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/altcha-org/altcha@main/dist/altcha.min.js"></script>
    <script async defer src="https://cdn.jsdelivr.net/npm/altcha/dist/altcha.min.js" type="module"></script>
    <link rel="canonical" href="https://www.gr8brik.rf.gd/build/" />
    <meta charset="UTF-8">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no"><!-- ios support -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/img/logo.jpg" />
    <meta name="apple-mobile-web-app-status-bar" content="#f1f1f1" />
    <meta name="theme-color" content="#f1f1f1" />
</head>

<body class="w3-light-blue w3-container">

<div id="report" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-24 w3-light-grey w3-center">
		<div class="w3-container">		
			<span onclick="document.getElementById('report').style.display='none'" class="w3-closebtn w3-red w3-hover-white w3-padding w3-display-topright">&times;</span>
			<form method="post" action="report.php">		
			<h2>Why do you want to flag this creation?</h2>
            <b>You can only report a creation when it violates the <a href="rules?utm_source=creation" target="_blank">Rules</a>.</b><br />
			<input type="checkbox" name="type" class="w3-check">
			<label class="w3-validate" value="Volent">Volent</label><br />
			<input type="checkbox" name="type" value="Missinformation" class="w3-check">
			<label class="w3-validate">Missinformation</label><br />
			<input type="checkbox" name="type" value="Inapropriate content" class="w3-check">
			<label class="w3-validate">Inapropriate content</label><br />
			<input type="checkbox" name="type" value="Harrasing me" class="w3-check">
			<label class="w3-validate">Harrasing me</label><br />
		    <input type="checkbox" name="type" value="Something else" class="w3-check" onclick="document.getElementById('other').style.display='block';document.getElementById('hide').style.display='block'">
			<label class="w3-validate">Something else</label><br />
			<input style="display:none;" type="text" value="<?php echo $username ?>" name="user" readonly>
			<input style="display:none;" type="text" value="<?php echo $model_id ?>" name="id" readonly><br />

			<center><textarea style="display:none;" class='w3-card-24' name="other" id='other' placeholder='Expain more...' rows='4' cols='50'></textarea><input type="checkbox" class="w3-check" id="hide" style="display:none;" onclick="document.getElementById('other').style.display='none'"><label class="w3-validate">hide</label><br /></center>
		<span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('report').style.display='none';document.getElementById('hide').style.display='none'">CLOSE</span> 
							<input type="submit" value="REPORT" name="report" class="w3-btn w3-large w3-white w3-hover-blue">
						</form>
					</div>
				</div>
			</div>

            <div id="delete" class="w3-modal">
				<div class="w3-modal-content w3-animate-top w3-card-24 w3-light-grey w3-center">
					<div class="w3-container">
						<span onclick="document.getElementById('delete').style.display='none'" class="w3-closebtn w3-red w3-hover-white w3-padding w3-display-topright">&times;</span>
                        <form method='post' action='/report.php?<?php echo $model ?>'>
							<h2>Are you sure you want to delete your creation (<?php echo $name ?>)?</h2>
							<span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('delete').style.display='none'">CLOSE</span> 
							<input type="submit" value="DELETE" name="delete" class="w3-btn w3-large w3-white w3-hover-red">
						</form>
					</div>
				</div>
			</div>

<script>

    $(document).ready(function() {

        $("#postComment").click(function(event) {
            event.preventDefault();

            var commentbox = $("#commentForm textarea[name='commentBox']").val();

            $.ajax({
                url: "",
                method: "POST",
                data: { comment: true, commentbox: commentbox },
                success: function(response) {
                    var goUrl = "/build/<?php echo $model_id ?>";
                    $("body").load(goUrl, function() {
				        history.pushState(null, "", goUrl);
                        function getCookie(name) {
                            function escape(s) { return s.replace(/([.*+?\^$(){}|\[\]\/\\])/g, '\\$1'); }
                            var match = document.cookie.match(RegExp('(?:^|;\\s*)' + escape(name) + '=([^;]*)'));
                            return match ? match[1] : '';
                        }
                        if (getCookie("theme") === "dark") {
				            $("body").addClass("w3-dark-grey");
                            $("#navbar").removeClass("w3-light-grey");
                            $("#navbar").addClass("w3-black");   
			            }
                        window.scrollTo(0, document.body.scrollHeight);
                    });
                },

                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error:", textStatus, errorThrown);
                    alert("An error occurred.");
                }
            });
        });
    });
</script>

<?php 
	include('navbar.php');
    require_once "com/bbcode.php";
    require_once "ajax/time.php";
    $bbcode = new BBCode;
    $count_result = $conn->query("SELECT COUNT(*) as vote_count FROM votes WHERE creation = $model_id");
    $count_row = $count_result->fetch_assoc();

    $result2 = $conn2->query("SELECT * FROM bans WHERE user = $userid");
    $row2 = $result2->fetch_assoc();
		
	echo "<h2>" . $name . "</h2>";
    echo '<h4><i class="fa fa-clock-o" aria-hidden="true"></i><time datetime="' . $date . '">Posted ' . $date . '</time></h4>';
    echo '<h4><i class="fa fa-user-o" aria-hidden="true"></i>By <a href="/@' . urlencode($username) . '">' . $username . '</a></h4>';

    echo '<h4><i class="fa fa-arrow-circle-o-up" aria-hidden="true"></i>' . $count_row['vote_count'] . ' likes</h4>';
    if(!empty($decoded_description)) {
        echo '<pre class="w3-card-2 w3-light-grey w3-padding w3-large">' . $bbcode->toHTML($decoded_description) . '</pre><br />';
    }
    echo '<form id="upvote" action="" method="post"></form>';
    echo '<form id="downvote" action="" method="post"></form>';
?>

        <div class="<?php echo $model ?>">
            <img src='/cre/<?php echo $screenshot ?>' style="width:50vw;height:50vh;border:1px solid;border-radius:15%;" loading='lazy'>
        </div>
			
		<?php
        
		if(isset($_SESSION['username'])) {
            echo '<div class="w3-dropdown-click">
                <button onclick="dropdown()" class="w3-btn w3-blue w3-hover-white"><i class="fa fa-download"></i>Download</button>
                <div id="gr8-dropdown" class="w3-dropdown-content w3-bar-block w3-border">
                    <a class="w3-bar-item w3-btn w3-hover-blue w3-border" href="' . "http://www.gr8brik.rf.gd/cre/" . $model . '" download>Creation</a>
                    <a class="w3-bar-item w3-btn w3-hover-blue w3-border" href="' . "http://www.gr8brik.rf.gd/cre/" . $screenshot . '" download>Screenshot</a>
                </div>
            </div>&nbsp;&nbsp';

            $profileid = $row['id'];
            $sql2 = "SELECT * FROM votes WHERE user = $id AND creation = $model_id";
            $result2 = $conn->query($sql2);
            $row2 = $result2->fetch_assoc();
            if ($result2->num_rows > 0) {
                echo '<input form="downvote" class="w3-btn w3-orange w3-hover-white" type="submit" value="Unlike" name="downvote">&nbsp;&nbsp';
            } else {
                echo '<input form="upvote" class="w3-btn w3-blue w3-hover-white" type="submit" value="Like" name="upvote">&nbsp;&nbsp';
            }

            if (trim($_SESSION['username']) === trim($userid)) {
                echo "<button onclick=document.getElementById('delete').style.display='block' name='delete' class='w3-btn w3-red w3-hover-white' /><i class='fa fa-trash'></i>Delete</button>&nbsp;";
            } else {
                echo "<button onclick=document.getElementById('report').style.display='block' name='flag' class='w3-btn w3-red w3-hover-white' /><i class='fa fa-flag-o'></i>Flag</button>&nbsp;";
            }

            echo "<br /><br /><div id='commentForm'>";
            echo "<div id='post'>";
			echo "<textarea name='commentBox' id='commentBox' class='w3-mobile' placeholder='Add a comment...' rows='4' cols='40'></textarea></div>";
            echo "<altcha-widget challengeurl='https://eu.altcha.org/api/v1/challenge?apiKey=ckey_01d9f4ad018c16287ca6f3938a0f'></altcha-widget>";
			echo "<button id='postComment' class='w3-btn w3-blue w3-hover-white w3-mobile w3-border w3-border-indigo w3-round'><i class='fa fa-commenting-o' aria-hidden='true'></i>Comment</button></div>";
		} else {
			echo "<br />Want to comment?";
			echo "<a class='w3-btn w3-blue w3-hover-white' href='/acc/login.php'>Login</a>";
		}

            $model_id = $_GET['id'];
			$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
			if ($conn->connect_error) {
				die("Connection failed: " . $conn->connect_error);
			}
			$sql = "SELECT * FROM comments WHERE model = $model_id ORDER BY id ASC";
			$comResult = $conn->query($sql);

            $count_result = $conn->query("SELECT COUNT(*) as reply_count FROM comments WHERE model = $model_id");
            $count_row = $count_result->fetch_assoc();

            echo '<hr /><h3><i class="fa fa-comments" aria-hidden="true"></i>' . $count_row['reply_count'] . ' comments</h3><br />';

			while ($row = $comResult->fetch_assoc()) {
			
				$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
				if ($conn->connect_error) {
					exit($conn->connect_error);
				}
                
                $c_user = $row['user'];
				$sql = "SELECT * FROM users WHERE id = $c_user";
				$userResult = $conn->query($sql);
				$userRow = $userResult->fetch_assoc();
                $pfp = "../img/avatar.png";
                $username = "[deleted]";
                $timestamp = "[unknown]";
                $comment = $bbcode->toHTML(htmlentities($row['comment'], ENT_QUOTES, 'UTF-8'));

                if(!empty($userRow['username'])) {
                    $username = $userRow['username'];
                }

                if(file_exists('acc/users/pfps/' . $c_user . '.jpg')) {
                    $pfp = '../ajax/pfp.php?id=' . base64_encode($c_user) . '&method=image';
                }

                if (is_numeric($row['date'])) {
                    $timestamp = time_ago(date('Y-m-d H:i:s', $row['date']));
                }
			
                echo '<div id="post">';
                echo '<img id="pfp-post" src="' . $pfp . '">';
                echo '<article class="w3-card-2 w3-light-grey w3-padding-small" style="width:50%"><header><b>';
                echo '<a href="/@' . urlencode($username) . '">' . $username . '</a> - ';
                echo '<time datetime="' . $timestamp . '">' . $timestamp . '</time></b></header>';
                echo '<pre>' . $comment . '</pre>';
                echo '</article></div><br />';
			}

            echo '<br /><br />';

            include('linkbar.php');

        ?>

</body>
</html>