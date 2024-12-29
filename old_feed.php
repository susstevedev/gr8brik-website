<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';

if(!isset($_SESSION['username'])){
    header('Location: acc/login.php');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Gr8brik.rf.gd | Your homepage</title>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="lib/theme.css">
    <script src="lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <meta charset="UTF-8">
    <meta name="description" content="Gr8brik is a block building browser game. No download required">
    <meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <!-- ios support -->
    <link rel="apple-touch-icon" href="/img/logo.jpg" />
    <meta name="apple-mobile-web-app-status-bar" content="#f1f1f1" />
    <meta name="theme-color" content="#f1f1f1" />
</head>
<body class="w3-light-blue w3-container">

        <?php 
            include('navbar.php');
            include('acc/panel.php');
            require_once 'com/bbcode.php';
            $bbcode = new BBCode;
        ?>
		
		<div id="im" class="w3-modal w3-center" onclick="this.style.display='none'">

            <span class="w3-closebtn w3-red w3-hover-white w3-padding w3-display-topright">&#10006;</span>

            <img class="w3-modal-content" src="<?php echo 'acc/users/pfps/' . $id . '..jpg' ?>" style="width:75%;" loading="lazy">

        </div>

        <div class="w3-center"><h3>Here are some things you might be interested in</h3>
		
		<a href='feed#creations' class='w3-btn w3-blue w3-hover-white'>Creations</a>

        <a href='feed#posts' class='w3-btn w3-blue w3-hover-white'>Posts</a>

        <a href='feed#comments' class='w3-btn w3-blue w3-hover-white'>Comments</a><br /></div>

        <div class="w3-container">
            <p>Want to build your own model? <a href="modeler" class="w3-btn w3-blue w3-hover-white">Open Modeler</a></p>
        </div>
        <a id="creations"><h2>Creations</h2></a><hr />
        <table class="w3-table-all" style="color:black;">
        
            <?php
				
			define('DB_NAME2', 'if0_36019408_creations');
			
			$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
            $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
			if ($conn->connect_error || $conn2->connect_error) {
				die("Error: " . $conn->connect_error . ", " . $conn2->connect_error);
			}
			
			// Retrieve all creations
			$sql = "SELECT * FROM model ORDER BY date DESC";
			$result = $conn->query($sql);

			if ($result->num_rows > 0) {
				$i = 0;
				while ($row = $result->fetch_assoc()) {
                    $userid = $row['user'];
                    $sql2 = "SELECT * FROM users WHERE id = $userid";
                    $result2 = $conn2->query($sql2);
                    $user = $result2->fetch_assoc();

					echo "<div class='w3-display-container w3-left w3-padding-small'>";
					echo "<a href='creation.php?id=" . $row['id'] . "'><img src='cre/" . $row['screenshot'] . "' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-border'></a>";
                    echo "<h4 class='w3-display-middle w3-text-grey'>" . $row['name'] . "</h4>";
                    echo "<b class='w3-display-bottommiddle w3-text-grey'>By <a href='/user/" . $user['username'] . "'>" . $user['username'] . "</a></b></div>";
					if (++$i == 6) break;
				}
			}

            ?>
			
        </table><br /><br />

        <a id="posts"><h2>Posts</h2></a><hr />
        <table class="w3-table-all" style="color:black;">
        
            <?php
				
			define('DB_NAME3', 'if0_36019408_forum');
			
			$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
            $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
			if ($conn->connect_error || $conn2->connect_error) {
				die("Error " . $conn->connect_error . ", " . $conn2->connect_error);
			}
		    $sql = "SELECT * FROM posts ORDER BY date DESC";
			$result = $conn->query($sql);

			if ($result->num_rows > 0) {
				$i = 0;
				while ($row = $result->fetch_assoc()) {
                    $userid = $row['user'];
                    $sql2 = "SELECT * FROM users WHERE id = $userid";
                    $result2 = $conn2->query($sql2);
                    $user = $result2->fetch_assoc();

                    $truncatedPost = substr($row['title'], 0, 50);
                    if (strlen($row['title']) > 50) {
                        $truncatedPost .= "...";
                    }

					echo "<div class='w3-display-container w3-left w3-padding-small'>";
					echo "<a href='com/view.php?id=" . $row['id'] . "'><img src='img/com.jpg' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-border'></a>";
                    echo "<h4 class='w3-display-middle w3-text-grey'>" . $truncatedPost . "</h4>";
                    echo "<b class='w3-display-bottommiddle w3-text-grey'>By <a href='/user/" . $user['username'] . "'>" . $user['username'] . "</a></b></div>";
					if (++$i == 6) break;
				}
			}

            ?>
			
        </table><br /><br />

        <a id="comments"><h2>Comments and replies</h2></a><hr />
        <table class="w3-table-all" style="color:black;">
        
            <?php
                    $conn1 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
                    $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
                    $conn3 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
                    if ($conn1->connect_error || $conn2->connect_error || $conn3->connect_error) {
                        die("Error: " . $conn1->connect_error . ", " . $conn2->connect_error . " and " . $conn3->connect_error);
                    }

                    $sql1 = "SELECT * FROM comments ORDER BY date DESC";
                    $sql2 = "SELECT * FROM replies ORDER BY date DESC";
                    $result1 = $conn1->query($sql1);
                    $result2 = $conn2->query($sql2);

                    if ($result1->num_rows > 0) {
                        $i = 0;
                        while ($row = $result1->fetch_assoc()) {
                            $truncatedComment = substr($row['comment'], 0, 50);
                            if (strlen($row['comment']) > 50) {
                                $truncatedComment .= "...";
                            }
                            $userid = $row['user'];
                            $sql3 = "SELECT * FROM users WHERE id = $userid";
                            $result3 = $conn3->query($sql3);
                            $user = $result3->fetch_assoc();

                            $replace = array("[img]", "[/img]", "[youtube]", "[/youtube]");
                            $with = array("[url]", "[/url]", "[url]https://www.youtube.com/watch?v=", "[/url]");
                            $truncatedComment = str_replace($replace, $with, $truncatedComment);
                            echo "<div class='w3-display-container w3-left w3-padding-small'>";
                            echo "<a href='creation.php?id=" . $row['model'] . "#c" . $row['id'] . "'><img src='img/com.jpg' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-border'></a>";
                            echo "<h4 class='w3-display-middle w3-text-grey'>" . $bbcode->toHTML($truncatedComment) . "</h4>";
                            echo "<b class='w3-display-bottommiddle w3-text-grey'>By <a href='/user/" . $user['username'] . "'>" . $user['username'] . "</a></b></div>";
                            if (++$i == 3) break;
                        }
                    }

                    if ($result2->num_rows > 0) {
				        $i = 0;
				        while ($row = $result2->fetch_assoc()) {
                            $truncatedComment = substr($row['post'], 0, 50);
                            if (strlen($row['post']) > 50) {
                                $truncatedComment .= "...";
                            }
                            $userid = $row['user'];
                            $sql3 = "SELECT * FROM users WHERE id = $userid";
                            $result3 = $conn3->query($sql3);
                            $user = $result3->fetch_assoc();

                            $replace = array("[img]", "[/img]", "[youtube]", "[/youtube]");
                            $with = array("[url]", "[/url]", "[url]https://www.youtube.com/watch?v=", "[/url]");
                            $truncatedComment = str_replace($replace, $with, $truncatedComment);

					        echo "<div class='w3-display-container w3-left w3-padding-small'>";
					        echo "<a href='com/view.php?id=" . $row['reply'] . "#r" . $row['id'] . "'><img src='img/com.jpg' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-border'></a>";
                            echo "<h4 class='w3-display-middle w3-text-grey'>" . $bbcode->toHTML($truncatedComment) . "</h4>";
                            echo "<b class='w3-display-bottommiddle w3-text-grey'>By <a href='/user/" . $user['username'] . "'>" . $user['username'] . "</a></b></div>";
					        if (++$i == 3) break;
				        }
			        }

            ?>
			
        </table><br /><br />


    <?php include('linkbar.php'); ?>


</body>
</html>