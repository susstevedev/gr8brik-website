<?php

session_start();

require_once 'acc/classes/user.php';

// Create connection
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['user']) && $_GET['user']) {
	$profile_user = $_GET['user'];
	$sql = "SELECT id, handle, email, admin, age, description FROM users WHERE username = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s", $profile_user);
	$stmt->execute();
	$stmt->store_result();

	$stmt->bind_result($profile_id, $profile_handle, $profile_mail, $profile_admin, $profile_age, $profile_about);
	$stmt->fetch();

	if ($stmt->num_rows === 0) {
		header('Location: error.php?error=404');
		die;
	}
    if ($profile_user === "") {
		header('Location: error.php?error=deactivate');
		die;
	}
}

if (isset($_GET['handle']) && $_GET['handle']) {
	$profile_handle = urldecode($_GET['handle']);
	$sql = "SELECT id, username, email, admin, age, description FROM users WHERE handle = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s", $profile_handle);
	$stmt->execute();
	$stmt->store_result();

	$stmt->bind_result($profile_id, $profile_user, $profile_mail, $profile_admin, $profile_age, $profile_about);
	$stmt->fetch();

    if ($stmt->num_rows === 0) {
		header('Location: error.php?error=404');
		die;
	}
    if ($profile_user === "") {
		header('Location: error.php?error=deactivate');
		die;
	}
}

if(isset($_POST['follow'])) {
		
    $notifName = $user . " followed you!";

    $notifPost = "Click to view " . $user . "'s profile.";

    $notifTime = date("Y-m-d");

    $notifUrl = "/profile.php?user=" . $user;

	$sql = "INSERT INTO follow (userid, profileid) VALUES (?, ?)";

	$stmt = $conn->prepare($sql);

	$stmt->bind_param("ii", $id, $profile_id);

	$stmt->execute();

	$stmt->close();

    $sql = "INSERT INTO notifications (user, name, post, timestamp, url) VALUES (?, ?, ?, ?, ?)";

	$stmt = $conn->prepare($sql);

	$stmt->bind_param("sssss", $profile_id, $notifName, $notifPost, $notifTime, $notifUrl);

	$stmt->execute();

	$stmt->close();

	$alertnum = '1';

	$stmt = $conn->prepare("UPDATE users SET alert = ? WHERE username = ?");

	$stmt->bind_param("ss", $alertnum, $profile_user);

	if ($stmt->execute()) {

        $floatMsg = "You are now following" . $profile_user . "!";

	} else {

		echo "An error has occurred";

	}

	$stmt->close();

	$conn->close();

	die;

}

if(isset($_POST['unfollow'])) {
	
	// $profile_id = $_GET['user'];
	
	$sql = "DELETE FROM follow WHERE userid = ? AND profileid = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ii", $id, $profile_id);
	$stmt->execute();
	$stmt->close();
	$conn->close();
	die;
}

if(isset($_POST['ban'])) {
    
    $profile_banned = "";
	$sql = "UPDATE users SET username = ? WHERE id = ?";

	$stmt = $conn->prepare($sql);
	$stmt->bind_param("si", $profile_banned, $profile_id);
	$stmt->execute();
	$stmt->close();
	$conn->close();
    header('Location: profile.php?user=' . $profile_user);
	die;
}
if(isset($_POST['unban'])) {	
	$sql = "UPDATE users SET username = ? WHERE id = ?";

	$stmt = $conn->prepare($sql);
	$stmt->bind_param("si", $profile_handle, $profile_id);
	$stmt->execute();
	$stmt->close();
	$conn->close();
    header('Location: profile.php?user=' . $profile_user);
	die;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $profile_handle ?> / GR8BRIK</title>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="lib/theme.css">
    <script src="lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <meta charset="UTF-8">
    <meta name="description" content="Gr8brik is a block building browser game. No download required">
    <meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
</head>
<body class="w3-container w3-light-blue">

<?php include('navbar.php'); ?>

    <div class="clearfix">
    <?php
        if(file_exists('acc/users/banners/' . $profile_id . '..jpg')){
            echo '<img id="banner" src="' . 'acc/users/banners/' . $profile_id . '..jpg' . '" class="w3-hover-opacity w3-border w3-card-24" /><br /><br />';
        }
    ?>
        <img id="pfp" src="<?php echo 'acc/users/pfps/' . $profile_id . '..jpg' ?>" class="w3-hover-opacity w3-border w3-card-24" onclick="document.getElementById('im').style.display='block'" />

    </div>
			
	<div id="im" class="w3-modal w3-center" onclick="this.style.display='none'">
                
		<span class="w3-closebtn w3-red w3-hover-white w3-padding w3-display-topright">&times;</span>

        <img class="w3-modal-content" src="<?php echo 'acc/users/pfps/' . $profile_id . '..jpg' ?>" loading="lazy">

    </div>

    <div class='w3-card-24 w3-light-grey'>
				
		<?php

            echo '<p style="font-size: 30px;">' . $profile_user;
            if($profile_admin === 1) {
				echo '<img src="img/star.png" title="This user is an admin of GR8BRIK" style="width:30px;height:30px;border-radius:50px;">';
			}
            echo '</p>';
            echo '<p style="font-size: 25px;"><a href="' . strtolower($profile_handle) . '">@' . $profile_handle . '</a></p>';
            echo '<p style="font-size: 15px;">' . htmlspecialchars($profile_about) . '</p>';

			$sql = "SELECT * FROM follow WHERE userid = ? AND profileid = ?";

			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ii", $id, $profile_id);
			$stmt->execute();
					
			$result = $stmt->get_result();
			if ($result->num_rows > 0) {
				// User is already following. Display "Unfollow" button.
                echo "<button onclick=document.getElementById('unfollow').style.display='block' name='unfollow' class='w3-btn w3-red w3-hover-opacity' />UNFOLLOW</button>";
			} elseif ($profile_user === $user) {
				echo '<a class="w3-btn w3-blue w3-hover-opacity w3-border" href="acc/index.php">MANAGE ACCOUNT</a>';
			} else {
				// User is not following. Display "Follow" button.
				echo '<form action="" method="post">';
				echo '<input class="w3-btn w3-blue w3-hover-opacity w3-border" type="submit" value="FOLLOW" name="follow"></form>';
			}
					
			if ($profile_user === $user) {
				echo '';
			} elseif ($admin === 1) {
                // User is not suspeneded. Display "Ban Account" button.
                echo "<br /><br /><button onclick=document.getElementById('ban').style.display='block' name='ban' class='w3-btn w3-red w3-hover-opacity' />BAN ACCOUNT</button>";
			}
				
			//}
				
			?>
            </div>

            <div id="unfollow" class="w3-modal">
				<div class="w3-modal-content w3-animate-top w3-card-24 w3-light-grey w3-center">
					<div class="w3-container">
					
						<span onclick="document.getElementById('unfollow').style.display='none'" class="w3-closebtn w3-red w3-hover-white w3-padding w3-display-topright">&times;</span>
						
						<?php
                            echo "<form method='post' action=''>";
                        ?>
							
							<h2>Are you sure you want to unfollow <?php echo $profile_user . '(@' . $profile_handle . ')' ?>?</h2>
							<span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('unfollow').style.display='none'">CLOSE</span> 
							<input type="submit" value="UNFOLLOW" name="unfollow" class="w3-btn w3-large w3-white w3-hover-blue">
						</form>
					</div>
				</div>
			</div>

            <div id="ban" class="w3-modal">
				<div class="w3-modal-content w3-animate-top w3-card-24 w3-light-grey w3-center">
					<div class="w3-container">
					
						<span onclick="document.getElementById('ban').style.display='none'" class="w3-closebtn w3-red w3-hover-white w3-padding w3-display-topright">&times;</span>
						
						<?php
                            echo "<form method='post' action=''>";
                        ?>
							
							<h2>Are you sure you want to ban <?php echo $profile_user . '(@' . $profile_handle . ')' ?>?</h2>
							<span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('ban').style.display='none'">CLOSE</span> 
							<input type="submit" value="BAN ACCOUNT" name="ban" class="w3-btn w3-large w3-white w3-hover-blue">
						</form>
					</div>
				</div>
			</div>
			
			<div class="w3-card-24 w3-large w3-padding w3-bottom w3-light-grey w3-round w3-hide-large">
				<a href="#creations"><b><p>CREATIONS</p></b></a><a href="#posts"><b><p>POSTS</p></b></a><a href="#comments"><b><p>COMMENTS</p></b></a>
			</div><br />

            <div class="w3-card-24 w3-padding w3-light-grey w3-hide-small">
				<a href="#creations"><b><p>CREATIONS</p></b></a><a href="#posts"><b><p>POSTS</p></b></a><a href="#comments"><b><p>COMMENTS</p></b></a>
			</div><br />
			
			<a href="#creations" id="creations"></a>
				<h3>CREATIONS</h3>
				<div class="w3-row">
				<?php
				
				define('DB_NAME2', 'if0_36019408_creations');
			
				$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

				if ($conn->connect_error) {
					die("Connection failed: " . $conn->connect_error);
				}
				
				$query = $profile_id;
			
				$sql = "SELECT id, model, description, name FROM model WHERE user = ?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("i", $query);
				$stmt->execute();
				$stmt->bind_result($id, $model, $description, $name);
				
				while ($stmt->fetch()) {
					echo "<div class='w3-display-container w3-left'>";
					echo "<a href='creation.php?id=" . $id . "'><img src='img/blured_model.jpg' width='320' height='240' loading='lazy' class='w3-hover-opacity w3-round w3-border'></a>";
                    echo "<b class='w3-display-middle'>" . $name . "</b></div>";
                }

            ?>
			</div>
			<hr />
			<a href="#posts" id="posts"></a>
				<h3>POSTS</h3>
				<div class="w3-row">
			<?php

                $query = $profile_id;
                // echo "<script>console.log('User ID is " . $profile_id . "')</script>";

                foreach (glob("com/posts/*.xml") as $_FF) {
                    $xml = new SimpleXMLElement($_FF, 0, true);
                    // Ensure $query is an integer
                    if ((int)$xml->uname === (int)$query) {
                        echo "<div class='w3-display-container w3-left'>";
                        echo "<a href='com/view.php?" . $_FF . "'><img src='img/com.jpg' width='320' height='240' loading='lazy' class='w3-hover-opacity w3-round w3-border'></a>";
                        echo "<b class='w3-display-middle'>" . htmlspecialchars($xml->title) . "</b></div>";
                    }
                }
            ?>

			</div>
			<hr />
			<!-- <a href="#comments" id="comments"></a>
                <div class="w3-row">
				    <h3>COMMENTS</h3>
				    <b>Coming to profile pages soon!</b>
                </div>
			<hr />
			<br /><br />
			
	<?php $stmt->close(); $conn->close(); ?><br /><br />

    <div class="w3-hide-small">
        <?php include('linkbar.php') ?>
    </div> -->


</div>

</body>
</html>