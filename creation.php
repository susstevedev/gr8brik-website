<?php

session_start();

		// Define constants here

		define('DB_NAME2', 'if0_36019408_creations');
		include 'acc/classes/user.php';
		
		// Create connection
		$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
			
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}
			
		// Query to get creation by it's own ID
		$model_id = $_GET['id'];

	    $sql = "SELECT id, user, model, description, name, date FROM model WHERE id = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("i", $model_id);
		$stmt->execute();
		
		$stmt->bind_result($model_id, $userid, $model, $description, $name, $date);
		$stmt->store_result();
		$stmt->fetch();

        if ($stmt->num_rows === 0 || $name === "") {
		    header('Location: error.php?error=404');
		    die;
	    }
		
		// Create connection
		$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
			
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}
		
		$sql = "SELECT username, handle FROM users WHERE id = $userid";
		$result = $conn->query($sql);
			
		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			$username = $row['username'];
            $user_handle = $row['handle'];
		}

if(isset($_POST['comment'])){
	
	$comment = $_POST['commentbox'];
		
		// Create connection
		$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
			
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}
		
		$date = date ("F d Y H:i:s.");
        $sql = "INSERT INTO comments (user, model, comment, date) VALUES (?, ?, ?, ?)";
        $stmt2 = $conn->prepare($sql);
        $stmt2->bind_param("iiss", $id, $model_id, $comment, $date); // Assuming 'user' and 'model' are strings
        $stmt2->execute();
        $stmt2->close();
        $conn->close();

        // Create connection
		$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
			
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}

        $notifName = $user . " commented on your model!";
        $notifPost = "Click to view your model!";
        $notifTime = date("Y-m-d");
        $notifUrl = "/creation.php?id=" . $model_id;
        $sql = "INSERT INTO notifications (user, name, post, timestamp, url) VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $userid, $notifName, $notifPost, $notifTime, $notifUrl);
        $stmt->execute();
        $stmt->close();
        $alertnum = '1';
        $stmt = $conn->prepare("UPDATE users SET alert = ? WHERE id = ?");
        $stmt->bind_param("si", $alertnum, $userid);

        if ($stmt->execute()) {
            echo "You commented!";
        } else {
            echo "An error has occurred";
        }
        $stmt->close();
        $conn->close();
	
}

include('com/bbcode.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $name . " by " . $username ?> / GR8BRIK</title>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="lib/theme.css">
    <script src="lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script async defer src="https://cdn.jsdelivr.net/npm/altcha/dist/altcha.min.js" type="module"></script>
    <meta charset="UTF-8">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body class="w3-light-blue w3-container">

<?php 
	include('navbar.php');
		
	echo "<h1>" . $name . "</h1>";
		
	echo '<img id="pfp" src="acc/users/pfps/' . $userid . '..jpg" xwidth="50px" xheight="50px">';

    echo '<article class="w3-card-24 w3-light-grey w3-hover-white"><header><b>BY:<a href="' . strtolower($user_handle) . '">' . $username . '</a>';

    echo '<br /><time id="postTime" datetime="' . $date . '">POSTED:' . $date . '</time></b></header>';

    echo '<h4>' . $description . '</h4></article><br />';
?>
		
		<div class="<?php echo $model ?>">
			<iframe src="<?php echo 'embed.php?model=' . $model . '&login=' . $_SESSION['username']?>" style="width:70vw;height:50vh;border:1px solid;border-radius:15%;" id="<?php echo $model ?>" title="<?php echo $model ?>">
				<b>error loading model "<?php echo $name ?>"</b>
			</iframe>
		</div>
		
			<button onclick="document.getElementById('report').style.display='block'" name="flag" class="w3-btn w3-red w3-hover-opacity" /><i class="fa fa-flag-o"></i>FLAG</button>&nbsp;
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
						
						<?php
                            echo "<form method='post' action='report.php?" . $model ."'>";
                        ?>
							
							<h2>Are you sure you want to delete your creations?</h2>
							<span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('delete').style.display='none'">CLOSE</span> 
							<input type="submit" value="DELETE" name="delete" class="w3-btn w3-large w3-white w3-hover-blue">
						</form>
					</div>
				</div>
			</div>
		
		<?php
		if(isset($_SESSION['username'])) {
			echo "<a href='" . 'cre/' . $model . '.json' . "' class='w3-btn w3-blue w3-hover-opacity' download><i class='fa fa-download'></i>DOWNLOAD</a>&nbsp;&nbsp;";
            if (trim($_SESSION['username']) === trim($userid)) {
                echo "<button onclick=document.getElementById('delete').style.display='block' name='delete' class='w3-btn w3-red w3-hover-opacity' /><i class='fa fa-trash'></i>DELETE</button>&nbsp;";
            }
			echo "<br /><b>EMBED:<input type='text' class='w3-input' value='" . 'http://gr8brik.rf.gd/embed.php?model=' . $model . '&login=' . $_SESSION['status'] . "' style='width:auto;height:50px;'></b>";
			echo "<br /><form method='post' action=''>";
			echo "<textarea name='commentbox' placeholder='Add a comment...' rows='4' cols='50'></textarea><br />";
			echo "<input type='submit' value='COMMENT' name='comment' class='w3-btn w3-blue w3-hover-opacity' />";
			echo "<altcha-widget challengeurl='https://eu.altcha.org/api/v1/challenge?apiKey=ckey_01d9f4ad018c16287ca6f3938a0f'style='background-color:white!important;'></altcha-widget>";
			echo "</form>";
		} else {
			echo "<br />Want to comment?";
			echo "<a class='w3-btn w3-blue w3-hover-opacity' href='acc/login.php'>LOGIN</a>";
		}
		?>
			
		<hr /><h3><i class="fa fa-comments" aria-hidden="true"></i>COMMENTS</h3><br />
			
		<?php 
			// Create connection
			$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
			
			if ($conn->connect_error) {
				die("Connection failed: " . $conn->connect_error);
			}
		
			$model_id = $_GET['id'];
			$sql = "SELECT id, user, model, comment, date FROM comments WHERE model = ? ORDER BY date ASC";
			$stmt = $conn->prepare($sql);
			if (!$stmt) {
				die("Prepare failed: " . $conn->error);
			}
			$stmt->bind_param("i", $model_id);
			$stmt->execute();
			$comResult = $stmt->get_result();
			
			if ($comResult->num_rows > 0) {
				while ($row = $comResult->fetch_assoc()) {
			
					$c_id = $row['id'];
					$c_user = $row['user'];
                    $c_comment = $row['comment'];
					$c_model = $row['model'];
					$c_date = $row['date'];
			
					// Create connection
					$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
			
					if ($conn->connect_error) {
						die("Connection failed: " . $conn->connect_error);
					}
					$sql = "SELECT username, handle FROM users WHERE id = $c_user";
					$userResult = $conn->query($sql);
					$userRow = $userResult->fetch_assoc();
					$c_username = $userRow['username'];
                    $c_handle = $userRow['handle'];
			
					echo '<img src="acc/users/pfps/' . $c_user . '..jpg" style="width:50px;height:50px;border-radius:50px;float:left;">';
					echo '<article class="w3-card-24 w3-light-grey w3-padding"><header><b><a href="' . strtolower($c_handle) . '">' . $c_username . '</a>';
                    echo '<time style="float:right;" datetime="' . $c_date . '">' . $c_date . '</time></b></header>';
					echo '<h4>' . $c_comment . '</h4></article><br />';
				}
			} else {
				echo "<b>Nothing here yet</b><br />";
				echo "<img src='img/empty.png' style='width:auto;height:auto;border:5px solid;border-radius:5px;'>";
			}

            echo '</div><br />';

            include('linkbar.php');

        ?>

</body>
</html>