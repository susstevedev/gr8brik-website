<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';

if(!isset($_SESSION['username'])){

    header('Location: login.php');

}

define('DB_NAME2', 'if0_36019408_creations');

// Create connection
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST["upload"])) {
	$description = $_POST['description'];
	$name = $_POST['model_name'];
	$userid = $id;
	
    $target_dir = "../cre/";
    $original_file_name = basename($_FILES["fileToUpload"]["name"]);
    $file_extension = pathinfo($original_file_name, PATHINFO_EXTENSION);
    $new_file_name = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_file_name;

    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        echo "The file ". htmlspecialchars($original_file_name). " has been uploaded as " . $new_file_name;

        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO model (user, model, description, name) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userid, $new_file_name, $description, $name);

        // Execute the statement
        if ($stmt->execute()) {
            echo "File details have been added to the database.";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
}

$conn->close();

if (isset($_POST['picture'])) {
    $dir = "../acc/users/pfps/";
    $target_file = $dir . basename($_FILES['fileToupload']['name']);
    $filename = $id;
    $extension = substr_replace($target_file, 'jpg', strrpos($filename, '.') + 1);
    $temp = explode(".", $_FILES["fileToupload"]["name"]);
    $upload = $dir . md5($filename) . $extension;
    $okay = 1;

    if ($_FILES["fileToupload"]["size"] > 5242880) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    if ($okay == 0) {
        echo "Sorry, your file was not uploaded.";
    } else {

        if (move_uploaded_file($_FILES["fileToupload"]["tmp_name"], $upload)) {
            echo "Your Profile Pricture has been updated.";
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
}

if(isset($_POST['deletePfp'])){
    $pfp = "users/pfps/" . $pic . ".jpg";
    unlink($pfp);
    header("Location: index.php");
    exit;
}

$password_error = false;
if($password_error) {
    echo '<b>Some of the passwords do not match</b>';
}
if(isset($_POST['change'])){
    $old = md5($_POST['o_password']);
    $new = md5($_POST['n_password']);
    $confirm = md5($_POST['c_password']);
    if($new == $confirm) {
			// Fetch the current password from the database
			$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
			$stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
			$stmt->bind_param("s", $user);
			$stmt->execute();
			$stmt->bind_result($db_password);
			$stmt->fetch();
			$stmt->close();
			$hashed_password = $new;
			$stmt_2 = $conn->prepare("update users SET password = ? WHERE username = ?");
            $stmt_2->bind_param("ss", $hashed_password, $user);
            if ($stmt_2->execute()) {
                echo "Password successfully changed!";
                header('Location: index.php');
                die;
            } else {
                echo "Error updating password.";
                header('Location: index.php');
                die;
            }
            $stmt->close();
            header('Location: logout.php');
            die;
        }
    $password_error = true;
}
if(isset($_POST['u_change'])){
    $new = htmlspecialchars($_POST['username']);
		// Fetch the current password from the database
			$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
			$stmt = $conn->prepare("SELECT username FROM users WHERE username = ?");
			$stmt->bind_param("s", $user);
			$stmt->execute();
			$stmt->bind_result($at);
			$stmt->fetch();
			$stmt->close();
			$stmt_2 = $conn->prepare("update users SET username = ? WHERE username = ?");
            $stmt_2->bind_param("ss", $new, $user);
            if ($stmt_2->execute()) {
                echo "Username successfully changed!";
                header('Location: index.php');
                die;
            } else {
                echo "Error updating username.";
                header('Location: index.php');
                die;
            }
            $stmt->close();
            die;
    }

if(isset($_POST['a_change'])){
    $day = $_POST['day'];
	$month = $_POST['month'];
	$year = $_POST['year'];
	$birthday = mktime(0,0,0,$month,$day,$year);
	$difference = time() - $birthday;
	$new = floor($difference / 31556926);
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        die("Error: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("UPDATE users SET age = ? WHERE username = ?");
    $stmt->bind_param("is", $new, $user);
    if ($stmt->execute()) {
        echo "Age successfully changed!";
        header('Location: index.php');
        die;
    } else {
         echo "Error updating age.";
        header('Location: index.php');
        die;
    }
        $stmt->close();
        die;
    }
if(isset($_POST['about'])){
    $new = $_POST['description'];
	// Create connection
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("UPDATE users SET description = ? WHERE username = ?");
    $stmt->bind_param("ss", $new, $user);
    if ($stmt->execute()) {
        echo "About successfully changed!";
        header('Location: index.php');
        die;
    } else {
        echo "Error updating about";
        header('Location: index.php');
        die;
    }
    $stmt->close();
    die;
}
$email_error = false;
if(isset($_POST['e_change'])){
    $old = $_POST['o_email'];
    $new = $_POST['n_email'];
    $confirm = $_POST['c_email'];
    // Fetch the current email from the database
			$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
			$stmt = $conn->prepare("SELECT email FROM users WHERE username = ?");
			$stmt->bind_param("s", $user);
			$stmt->execute();
			$stmt->bind_result($email);
			$stmt->fetch();
			$stmt->close();
			$stmt_2 = $conn->prepare("update users SET email = ? WHERE username = ?");
            $stmt_2->bind_param("ss", $new, $user);
            if ($stmt_2->execute()) {
                echo "E-mail successfully changed!";
                header('Location: index.php');
                die;
            } else {
                echo "Error updating e-mail.";
                header('Location: index.php');
                die;
            }
            $stmt->close();
}

if(isset($_POST['deactivate'])){
    $id = $_SESSION['username'];
    $pfp = "users/pfps/" . $id . ".jpg";
    $banner = "users/banners/" . $id . ".jpg";
    $pass = $_POST['pwd'];
    $username = "";
    $email = "root@foo.tld";

    // Create connection
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $stmt->bind_result($db_password);
    $stmt->fetch();
    $stmt->close();

    if($db_password != md5($pass)) {
        echo "<b>Password's do not match";
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $username, $email, $id);

    if ($stmt->execute()) {
        $_SESSION['username'] = "";
        session_destroy();
        exit;
    } else {
        echo "Error updating user: " . $stmt->error;
        die;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php echo $user ?> | GR8BRIK</title>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="../lib/theme.css">
    <script src="../lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <meta charset="UTF-8">
    <meta name="description" content="Gr8brik is a block building browser game. No download required">
    <meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
</head>
<body class="w3-light-blue w3-container">

    <?php include('../navbar.php') ?>
    <div id="result" class="w3-center">
        <div class="w3-card-2 w3-light-grey w3-padding-tiny w3-center">
            <?php 
				if($alert != 0){
					echo '<h2 style="color:red;">+' . $alert  . ' inbox</h2>';
				}
                echo '<h4>Welcome,</h4><h2>' . $user . '<a href="../acc/index">';
                echo '<i class="fa fa-pencil" aria-hidden="true"></i></a></h2>';
            ?>
            <a href="/user/<?php echo $user ?>" class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><li class="fa fa-user" aria-hidden="true"></li><b>PROFILE</b></a>
			<a href="/acc/new" class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><i class="fa fa-bell" aria-hidden="true"></i><b>NOTIFICATIONS</b></a>
            <a href="/acc/creations" class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><i class="fa fa-building" aria-hidden="true"></i><b>MY CREATIONS</b></a>
            <a href="/acc/following" class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><i class="fa fa-user-plus" aria-hidden="true"></i><b>PEOPLE</b></a>
            <?php
                if($admin === 1){
                    echo '<br /><h2>MOD TOOLS</h2>';
				    echo '<a href="/acc/appeals" class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><i class="fa fa-address-book" aria-hidden="true"></i><b>BAN APPEALS</b></a>';
			    }
            ?>
        </div>
		
		<div>

			<h2>QUICK SETTINGS</h2>

			<a href="login?status=loggedout" class="w3-btn w3-blue w3-hover-opacity w3-mobile w3-border"><i class="fa fa-sign-out" aria-hidden="true"></i>LOGOUT</a>

			<button onclick="toggleDarkMode();" class="w3-btn w3-blue w3-hover-opacity w3-mobile w3-border"><i class="fa fa-moon-o" aria-hidden="true"></i>NIGHT MODE</button>

			<button onclick="toggleLightMode();" class="w3-btn w3-blue w3-hover-opacity w3-mobile w3-border"><i class="fa fa-lightbulb-o" aria-hidden="true"></i>DAY MODE</button>
        
        </div><br />
        
	<h2>PROFILE SETTINGS</h2>

        <div id="deactivate" class="w3-modal">
			<div class="w3-modal-content w3-animate-top w3-card-24 w3-light-grey w3-center">
				<div class="w3-container">
					<span onclick="document.getElementById('deactivate').style.display='none'" class="w3-closebtn w3-red w3-hover-white w3-padding w3-display-topright">&times;</span>
					<form id="activate" method="post" action="">	
						<h2>Deactivating your account <b>CANNOT</b> be undone. Deactivating your account does:
                        <li>Removes your email</li>
                        <li>Your username will be "Deleted User" or blank</li>
                        <li>Your profile will be privated</li>
                        <li>Your models and posts will be read-only</li>
                        <li>You will be logged out off <b>ALL</b> sessions</li>
                        Enter your password to continue:</h2><br />
                        <center><input type="password" value="" name="pwd" style="width:60%;" placeholder="Password" class="w3-input w3-border w3-mobile" /></center><br />

						<span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('deactivate').style.display='none'">CLOSE</span> 
						<input type="submit" value="DEACTIVATE" name="deactivate" class="w3-btn w3-large w3-white w3-hover-red">
					</form>
				</div>
			</div>
		</div>

<div>

    <img src="<?php echo '../acc/users/pfps/' . $pic . '.jpg' ?>" width="250px" height="250px" class="w3-card-2 w3-circle w3-left" />
    <form action="" method="post" enctype="multipart/form-data">
		<input type="file" name="fileToupload" id="fileToupload" style="float:left;">
		<input type="submit" value="UPLOAD PICTURE" name="picture" style="float:left;" class="w3-btn w3-blue w3-hover-opacity w3-mobile w3-border">
        <?php 
            if(file_exists('users/pfps/' . $pic . '.jpg')){
		        echo '<input type="submit" value="DELETE PICTURE" name="deletePfp" style="float:left;" class="w3-btn w3-blue w3-hover-opacity w3-mobile w3-border">';
            }
        ?>
    </form>
</div><br /><br />
  
<div id="offset">
    <a id="username"><form id="username" method="post" action="">
        <input type="text" value="<?php echo $user ?>" name="username" placeholder="New username" class="w3-input w3-border w3-mobile w3-third" />
        <input type="submit" name="u_change" value="CHANGE USERNAME" class="w3-btn w3-blue w3-hover-opacity w3-mobile w3-border w3-quarter" />
    </form></a><br /><br />

    <a id="about"><form id="about" method="post" action="">
        <textarea name="description" value="<?php echo htmlspecialchars($about) ?>" placeholder="New about section" class="w3-input w3-border w3-mobile" rows="4" cols="50"><?php echo htmlspecialchars($about) ?></textarea>
        <input type="submit" name="about" value="CHANGE ABOUT" class="w3-btn w3-blue w3-hover-opacity w3-mobile w3-border" />
    </form></a><br /><br />
</div>

    <h2>ACCOUNT SETTINGS</h2>

    <div id="offset">

        <form id="password" method="post" action="">
            <input type="password" name="o_password" placeholder="Old password" class="w3-input w3-border w3-mobile w3-third" />
            <input type="password" name="n_password" placeholder="New password" class="w3-input w3-border w3-mobile w3-third" />
            <input type="submit" name="password_change" value="CHANGE PASSWORD" class="w3-btn w3-blue w3-hover-opacity w3-mobile w3-border w3-third" />
        </form><br /><br />

        <form id="email" method="post" action="">
            <input type="email" value="<?php echo htmlspecialchars($email) ?>" name="o_email" style="float:left;width:30%;" placeholder="Old email" class="w3-input w3-border w3-mobile w3-third" />
            <input type="email" name="n_email" style="float:left;width:30%;" placeholder="New email" class="w3-input w3-border w3-mobile w3-third" />
            <input type="submit" name="e_change" style="float:left;" value="CHANGE EMAIL" class="w3-btn w3-blue w3-hover-opacity w3-mobile w3-border w3-third" /><br /><br />
        </form>
    </div>
    <br /><br /><button onclick=document.getElementById('deactivate').style.display='block' name='deactivate' class='w3-btn w3-red w3-hover-opacity w3-mobile w3-border' />DEACTIVATE ACCOUNT</button><br /><br /></center>

     <hr /><h5>If you wish us to remove any personal details we hold about you, please email us at <i class="fa fa-envelope"><a href="mailto:evanrutledge226@gmail.com">evanrutledge226[at]gmail[dot]com</a></i></h5>
    <br /><br />

    <?php include ('../linkbar.php') ?>

</body>
</html>