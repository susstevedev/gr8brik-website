<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';

$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
			
if ($conn->connect_error) {
	exit($conn->connect_error);
}
			
$model_id = $conn->real_escape_string($_GET['id']);

if (!is_numeric($model_id)){
    header('HTTP/1.0 403 Forbidden');
    echo "<b>Invalid creation ID!</b>";
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
$views = $row2['views'] + 1;
$isRemoved = $row2['removed'];

$decoded_description = htmlentities($description, ENT_QUOTES, 'UTF-8');
            
$conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
if ($conn2->connect_error) {
    exit($conn2->connect_error);
}

if($result->num_rows != 0 || !empty($row2['id'])) {
    $sql = "SELECT * FROM users WHERE id = $userid";
    $result = $conn2->query($sql);
    $row = $result->fetch_assoc();
    $username = $row['username'];
    $model_admin = $row['admin'];
    $model_verified = $row['verified'];

    $result2 = $conn2->query("SELECT * FROM bans WHERE user = $userid");
    $row2 = $result2->fetch_assoc();
} else {
    header('HTTP/1.0 404 Not Found');
}

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

    if(!isset($_SESSION['username']) || empty($comment) || !isset($_COOKIE['token'])) {
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

    if($user != $userid) {
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
    }
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

if(isset($_POST['saveComment'])){

	$commentId = $_POST['commentId'];
	$comment = htmlspecialchars($_POST['commentEditing' . $commentId]);
    
		
	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

    if(!isset($_SESSION['username']) || empty($comment)) {
        header("HTTP/1.0 500 Internal Server Error");
        exit;
    }
		
    $sql = "UPDATE comments SET comment = ? WHERE id = ?";
    $stmt2 = $conn->prepare($sql);
    $stmt2->bind_param("si", $comment, $commentId);
    $stmt2->execute();

    if ($stmt2->execute()) {
        header('Location:' . $_SERVER["REQUEST_URI"]);
    }
	
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $name ?></title>
    <?php include 'header.php' ?>
</head>

<body class="w3-light-blue w3-container">

<div id="report" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-24 w3-light-grey w3-center w3-round w3-padding">
		<div class="w3-container">		
			<span onclick="document.getElementById('report').style.display='none'" class="w3-closebtn w3-red w3-hover-white w3-padding w3-round w3-display-topright">&times;</span>
			<form method="post" action="report.php">		
			<h2>Why do you want to flag this creation?</h2>
            <b>You can only report a creation when it violates the <a href="/rules?src=creation" target="_blank">Rules</a>.</b><br />
			<input type="checkbox" name="type" value="1" class="w3-check">
			<label class="w3-validate">Violent</label><br />
			<input type="checkbox" name="type" value="2" class="w3-check">
			<label class="w3-validate">Misinformation</label><br />
			<input type="checkbox" name="type" value="3" class="w3-check">
			<label class="w3-validate">Inappropriate content</label><br />
			<input type="checkbox" name="type" value="4" class="w3-check">
			<label class="w3-validate">Harrasing me</label><br />
		    <input type="checkbox" name="type" value="5" class="w3-check" onclick="document.getElementById('other').classList.toggle('w3-hide');">
			<label class="w3-validate">Something else</label><br />
			<input style="display:none;" type="text" value="<?php echo $username ?>" name="user" readonly>
			<input style="display:none;" type="text" value="<?php echo $model_id ?>" name="id" readonly><br />

			<center><textarea class='w3-card-24 w3-input w3-round w3-hide' name="other" id='other' placeholder='Expain more...' rows='4' cols='50'></textarea><br /></center>
		<span name="close" class="w3-btn w3-large w3-white w3-hover-blue w3-round" onclick="document.getElementById('report').style.display='none';">Close</span> 
							<input type="submit" value="Report" name="report" class="w3-btn w3-large w3-white w3-hover-red w3-round">
						</form>
					</div>
				</div>
			</div>

            <!--<div id="delete" class="w3-modal">
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
			</div>-->

<script>

    $(document).ready(function() {

        $("#success").hide();

        const urlParams = new URLSearchParams(window.location.search);

        const paramValue = urlParams.get('sentComment');

        if(paramValue == '1') {
            $("#success").show();
            $("#success").html("<b>Comment posted!</b>");
        }

        function checkCookieExists(n) {
            if (document.cookie.split(';').some((item) => item.trim().startsWith(n + '='))) {
                return true;
            }
            return false;
        }

        $("#postComment").click(function(event) {
            event.preventDefault();

            var commentbox = $("#commentForm textarea[name='commentBox']").val();

            $.ajax({
                url: "",
                method: "POST",
                data: { comment: true, commentbox: commentbox },
                success: function(response) {
                    var goUrl = window.location.href + window.location.search + '?sentComment=1';
                    window.location.href = goUrl;
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

    echo "<span id='success' class='w3-padding w3-round w3-green w3-top'></span>";

    if ($result->num_rows === 0 || $isRemoved === "1" || $result2->num_rows != 0 && $row2['end_date'] >= time() || empty($username)) {
        echo '<h2>Error</h2><p>Creation does not exist.</p>';
        exit;
    } else {
        $sql = "UPDATE model SET views = '$views' WHERE id = '$model_id'";
        $result = $conn->query($sql);
        $count_result = $conn->query("SELECT COUNT(*) as vote_count FROM votes WHERE creation = $model_id");
        $count_row = $count_result->fetch_assoc();
    }

	echo "<h2>" . $name . "</h2>";
    echo '<h4><i class="fa fa-clock-o" aria-hidden="true"></i><time datetime="' . $date . '">Posted ' . $date . '</time></h4>';
    echo '<h4><i class="fa fa-user-o" aria-hidden="true"></i>By <a href="/user/' . $userid . '">' . $username . '</a>';
    if($model_verified != 0) {
        echo '&nbsp;<i class="fa fa-check w3-blue w3-large"></i>';
    }
    if($model_admin != 0) {
        echo '&nbsp;<i class="fa fa-check w3-red w3-large"></i>';
    }
    echo '</h4>';
    echo '<h4><i class="fa fa-arrow-circle-o-up" aria-hidden="true"></i>' . $count_row['vote_count'] . ' likes</h4>';
    echo '<h4><i class="fa fa-eye" aria-hidden="true"></i>' . $views . ' views</h4>';

    if(!empty($decoded_description)) {
        echo '<pre class="gr8-theme w3-card-2 w3-hover-shadow w3-light-grey w3-padding w3-large w3-round">' . $bbcode->toHTML($decoded_description) . '</pre><br />';
    }
    echo '<form id="upvote" action="" method="post"></form>';
    echo '<form id="downvote" action="" method="post"></form>';
?>

        <div class="<?php echo $model ?>">
            <img src='/cre/<?php echo $screenshot ?>' style="width:50vw;height:50vh;border:1px solid;border-radius:15%;" loading='lazy'>
        </div>
			
		<?php
        
		if(isset($_COOKIE['token'])) {
            echo '<div class="w3-dropdown-click">
                <div class="tooltip">
                    <span class="w3-tag w3-round w3-blue tooltiptext"><i class="fa fa-info-circle" aria-hidden="true"></i>Download a model to save it</span>
                    <button onclick="dropdown()" class="w3-btn w3-blue w3-hover-white w3-border w3-border-indigo">Download...</button>
                </div>
                <div id="gr8-dropdown" class="w3-dropdown-content w3-bar-block w3-border" style="z-index: 9999;">
                    <a class="w3-bar-item w3-btn w3-hover-blue w3-border" href="' . "/cre/" . $model . '" download="build' . $model_id . '.gr8.json">creation</a>
                    <a class="w3-bar-item w3-btn w3-hover-blue w3-border" href="' . "/cre/" . $screenshot . '" download="img' . $model_id . '.gr8.png">screenshot</a>
                </div>
            </div>&nbsp;&nbsp';

            $profileid = $row['id'];
            $sql2 = "SELECT * FROM votes WHERE user = $id AND creation = $model_id";
            $result2 = $conn->query($sql2);
            $row2 = $result2->fetch_assoc();
            if ($result2->num_rows > 0) {
                echo '<input form="downvote" class="w3-btn w3-orange w3-hover-white w3-border w3-border-pink" type="submit" value="Unlike" name="downvote">&nbsp;&nbsp';
            } else {
                echo '<input form="upvote" class="w3-btn w3-blue w3-hover-white w3-border w3-border-indigo" type="submit" value="Like" name="upvote">&nbsp;&nbsp';
            }

            if (trim($token['user']) === trim($userid)) {
                echo '<div class="tooltip">
                    <span class="w3-tag w3-round w3-blue tooltiptext"><i class="fa fa-info-circle" aria-hidden="true"></i>Delete not available right now</span>
                    <button xonclick=document.getElementById("delete").style.display="block" name="delete" class="w3-btn w3-red w3-hover-white w3-border w3-border-pink" disabled/>Delete</button>&nbsp;
                </div>';
            } else {
                echo '<div class="tooltip">
                    <span class="w3-tag w3-round w3-blue tooltiptext"><i class="fa fa-info-circle" aria-hidden="true"></i>Report a model to Admins</span>
                        <button onclick=document.getElementById("report").style.display="block" name="flag" class="w3-btn w3-red w3-hover-white w3-border w3-border-pink" />Flag</button>&nbsp;
                    </div>';
            }

            echo "<br /><br /><div id='commentForm' class='gr8-theme w3-card-2 w3-hover-shadow w3-light-grey w3-border w3-padding-small w3-round'>";
            echo "<div id='post'>";
			echo "<textarea name='commentBox' id='commentBox' class='w3-input w3-mobile w3-half w3-round' placeholder='add a comment...' rows='4' cols='40'></textarea></div><br />";
            echo "<altcha-widget challengeurl='https://eu.altcha.org/api/v1/challenge?apiKey=ckey_01d9f4ad018c16287ca6f3938a0f'></altcha-widget><br />";
			echo "<button id='postComment' class='w3-btn w3-blue w3-hover-white w3-border w3-border-indigo'>Comment</button></div>";
		} else {
			echo "<h4><a href='/acc/login.php'>Login</a> to comment</h4>";
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
                $userResult = $conn->query("SELECT * FROM users WHERE id = $c_user");
                $userRow = $userResult->fetch_assoc();

                $pfp = 'ajax/pfp?method=image&id=' . base64_encode($c_user);

                $banResult = $conn->query("SELECT * FROM bans WHERE user = '$c_user'");
                while ($banRow = $banResult->fetch_assoc()) {
                    if ($banResult->num_rows > 0 && $banRow['end_date'] >= time()) {
                        $userRow['username'] = "[deleted]";
                        $pfp = 'img/no_image.png';
                    }
                }
                if (!empty($userRow['deactive'] || empty($userRow['username']) || $userResult->num_rows < 0)) {
                    $userRow['username'] = "";
                    $pfp = 'img/no_image.png';
                }

                if (!is_numeric($row['date'])) {
                    $row['date'] = time();
                }
                    
                    echo "<script>
                        $(document).on('click', '#editComment' + " .  $row['id'] . " , function(event) {
                            $('#commentEditing' + " . $row['id'] . ").toggle();
                            $('#comment' + " . $row['id'] . ").toggle();
                            $('#saveComment' + " . $row['id'] . ").toggle();
                        });

                    </script>";

                    echo '<div id="post">';
                    echo '<img class="pfp-post w3-card-2 w3-hover-shadow w3-light-grey" src="/' . $pfp . '">';
                        
                    echo '<article class="gr8-theme w3-card-2 w3-hover-shadow w3-light-grey w3-padding-tiny w3-round" style="width:75%">';
                    echo '<header><a href="/user/' . $c_user . '" title="The user who wrote this comment">' . $userRow['username'] . '</a>';
                    if($userRow['verified'] != 0) {
                        echo '&nbsp;<span class="fa fa-check w3-blue w3-large w3-padding-tiny"></span>';
                    }
                    if($userRow['admin'] != 0) {
                        echo '&nbsp;<span class="fa fa-check w3-red w3-large w3-padding-tiny"></span>';
                    }
                    echo '<time class="w3-right" datetime="' . $row['date'] . '" title="Time that has passed since this comment was posted">' . time_ago(date('Y-m-d H:i:s', $row['date'])) . '</time>';
                    if(isset($_COOKIE['token']) && isset($token)) {
                        if (trim($token['user']) === trim($c_user)) {
                            echo "&nbsp;<span id='editComment" . $row['id'] . "' class='w3-blue w3-padding-tiny fa fa-pencil w3-large'></span>";
                            echo "&nbsp;<span id='saveComment" . $row['id'] . "' form='editComment' type='submit' name='saveComment' style='display: none;' class='w3-blue w3-padding-tiny fa fa-save w3-large'></span>";
                            echo '&nbsp;<span class="fa fa-flag w3-red w3-large w3-padding-tiny"></span>';
                            echo '<br /><textarea form="editComment" id="commentEditing' . $row['id'] . '" name="commentEditing' . $row['id'] . '" rows="2" cols="80" style="display:none;">' . $row['comment'] . '</textarea>';
                            echo '<form action="" method="POST" id="editComment">';
                            echo '<input type="hidden" name="commentId" value="' . $row['id'] . '"></form>';
                        }
                    }
                    echo '</header><pre id="comment' . $row['id'] . '" value="' . $row['comment'] . '">' . $bbcode->toHTML(htmlentities($row['comment'], ENT_QUOTES, 'UTF-8')) . '</pre>';
                    echo '</article></div><br />';

                    $banResult->free();
                    $userResult->free();
                    $userError = false;
            }
            $comResult->free();

            echo '<br /><br />';

            include('linkbar.php');

        ?>

</body>
</html>