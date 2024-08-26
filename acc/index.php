<?php
session_start();

require_once 'classes/user.php';

if(!isset($_SESSION['username'])){

    header('Location: acc/login.php');

}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debug: Log the incoming data
    error_log(print_r($_POST, true));

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
	
	if(isset($_POST['picture'])){
		$dir = "../acc/users/pfps/";
		$target_file = $dir . basename($_FILES['fileToupload']['name']);
		$filename = $dir . $id;
		$extension = substr_replace($target_file , 'jpg', strrpos($filename, '.') +1);
		$temp = explode(".", $_FILES["fileToupload"]["name"]);
		$upload = $filename . $extension;
		$okay = 1;

		// Check file size
		if ($_FILES["fileToupload"]["size"] > 5242880) {
			echo "Sorry, your file is too large.";
			$uploadOk = 0;
		}

		// Check if $uploadOk is set to 0 by an error
		if ($okay == 0) {
			echo "Sorry, your file was not uploaded.";
		// if everything is ok, try to upload file
		} else {
			if (move_uploaded_file($_FILES["fileToupload"]["tmp_name"], $upload )) {
			echo "Your Profile Pricture has been updated.";
		} else {
			echo "Sorry, there was an error uploading your file.";
		}
    }

}

if(isset($_POST['banner'])){
    $dir = "../acc/users/banners/";
	$target_file = $dir . basename($_FILES['fileToupload']['name']);
	$filename = $dir . $id;
	$extension = substr_replace($target_file , 'jpg', strrpos($filename, '.') +1);
	$temp = explode(".", $_FILES["fileToupload"]["name"]);
	$upload = $filename . $extension;
	$okay = 1;

	// Check file size
	if ($_FILES["fileToupload"]["size"] > 5242880) {
		echo "Sorry, your file is too large.";
		$uploadOk = 0;
	}

	// Check if $uploadOk is set to 0 by an error
	if ($okay == 0) {
		echo "Sorry, your file was not uploaded.";
	// if everything is ok, try to upload file
	} else {
		if (move_uploaded_file($_FILES["fileToupload"]["tmp_name"], $upload )) {
		    echo "Your Profile Pricture has been updated.";
	    } else {
		    echo "Sorry, there was an error uploading your file.";
	    }
    }
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
if(isset($_POST['h_change'])){
    $new = preg_replace('/[^A-Za-z]/', '', $_POST['handle']);
	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
	$stmt = $conn->prepare("SELECT handle FROM users WHERE username = ?");
	$stmt->bind_param("s", $user);
	$stmt->execute();
	$stmt->bind_result($at);
	$stmt->fetch();
	$stmt->close();
	$stmt_2 = $conn->prepare("update users SET handle = ? WHERE username = ?");
    $stmt_2->bind_param("ss", $new, $user);
    if ($stmt_2->execute()) {
        echo "Handle successfully changed!";
        header('Location: index.php');
        die;
    } else {
        echo "Error updating handle.";
        header('Location: index.php');
        die;
    }
    $stmt->close();
    die;
    }
if(isset($_POST['a_change'])){
    $new = $_POST['age'];
	// Create connection
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
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

}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Index / GR8BRIK</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="../lib/theme.css">
    <script src="../lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <meta charset="UTF-8">
    <meta name="description" content="Gr8brik is a block building browser game. No download required">
    <meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="w3-light-blue w3-container">

    <?php include('../navbar.php') ?>
    <div id="result" class="w3-center">
        <div class="w3-light-grey w3-card-24 w3-center">
            <?php 
				if($alert != 0){
					echo '<h1 style="color:red;">You have new notifications!</h1>';
				}
                echo "<h3>Welcome,</h3><img id='pfp' src='users/pfps/" . $id . "..jpg' class='w3-hover-opacity w3-border w3-card-24' onclick=document.getElementById('im').style.display='block' />";
                echo '<h1>' . $user . '</h1><h3>@' . $handle . '</h3>';
            ?>
            <a href="<?php echo $handle ?>" class="w3-btn w3-large w3-white w3-hover-blue"><li class="fa fa-user" aria-hidden="true"></li><b>PROFILE</b></a>
			<a href="new.php" class="w3-btn w3-large w3-white w3-hover-blue"><i class="fa fa-bell" aria-hidden="true"></i><b>NOTIFICATIONS</b></a>
            <a href="creations.php" class="w3-btn w3-large w3-white w3-hover-blue"><i class="fa fa-building" aria-hidden="true"></i><b>MY CREATIONS</b></a>
        </div>

        <div id="im" class="w3-modal w3-center" onclick="this.style.display='none'">

            <span class="w3-closebtn w3-red w3-hover-white w3-padding w3-display-topright">&#10006;</span>

            <img class="w3-modal-content" src="<?php echo 'users/pfps/' . $id . '..jpg' ?>" style="width:75%;" loading="lazy">

        </div><br />
		
		<div>

			<h2>QUICK SETTINGS</h2>

			<a href="logout.php" class="w3-btn w3-blue w3-hover-opacity"><i class="fa fa-sign-out" aria-hidden="true"></i>LOGOUT</a>

			<button onclick="toggleDarkMode();" class="w3-btn w3-blue w3-hover-opacity"><i class="fa fa-moon-o" aria-hidden="true"></i>NIGHT MODE</button>

			<button onclick="toggleLightMode();" class="w3-btn w3-blue w3-hover-opacity"><i class="fa fa-lightbulb-o" aria-hidden="true"></i>DAY MODE</button>
        
        </div><br />
        
	<h2>PROFILE SETTINGS</h2>

    <!--<script>
        $(document).ready(function() {
            $(".submit-btn").click(function(event) {
                event.preventDefault(); // Prevent the default form submission

                var form = $(this).closest("form"); // Get the form that triggered the event
                var formData = form.serialize(); // Serialize the form data

                console.log("Form Data:", formData); // Debug: Log the form data

                $.ajax({
                    url: "index.php",
                    type: "POST",
                    data: formData,
                    success: function(response) {
                        // Update the page content with the response
                        $("#result").html(response);
                        form[0].reset(); // Clear the form after submission
                    },
                    error: function(xhr, status, error) {
                        console.error("Request failed:", status, error);
                        console.log("XHR Object:", xhr); // Debug: Log the XHR object
                    }
                });
            });
        });
    </script>-->

<div>
    <img src="<?php echo '../acc/users/banners/' . $id . '..jpg' ?>" style="width:100%;height:25vh;border:1px #000;border-radius:15%;background-color:#fff;" />
    <form action="" method="post" enctype="multipart/form-data">
	    <input type="file" name="fileToupload" id="fileToupload">
	    <input type="submit" value="UPLOAD" name="banner" class="w3-btn w3-large w3-white w3-hover-blue w3-mobile">
    </form><br /><br />

    <img src="<?php echo '../acc/users/pfps/' . $id . '..jpg' ?>" style="width: 250px; height: 250px; border-radius: 50%; float: left;" class="w3-card-24" />
    <form action="" method="post" enctype="multipart/form-data">
		<input type="file" name="fileToupload" id="fileToupload" style="float:left;">
		<input type="submit" value="UPLOAD" name="picture" style="float:left;" class="w3-btn w3-blue w3-hover-opacity w3-mobile">
    </form>
</div><br /><br />
  
<div id="offset">
    <form id="username" method="post" action="">
        <input type="text" value="<?php echo htmlspecialchars($user) ?>" name="username" style="float:left;width:30%;" placeholder="New username" class="w3-input w3-border w3-mobile" />
        <input type="submit" name="u_change" style="float:left;" value="CHANGE USERNAME" class="w3-btn w3-blue w3-hover-opacity w3-mobile submit-btn" />
    </form><br /><br />

    <form id="handle" method="post" action="">
        <div id="handleIcon">gr8brik.rf.gd/</div>
        <input type="text" value="<?php echo htmlspecialchars($handle) ?>" name="handle" style="float:left;width:30%;" placeholder="New handle" class="w3-input w3-border w3-mobile" />
        <input type="submit" name="h_change" style="float:left;" value="CHANGE URL" class="w3-btn w3-blue w3-hover-opacity w3-mobile submit-btn" />
    </form><br /><br />

    <form id="about" method="post" action="">
        <textarea name="description" value="<?php echo htmlspecialchars($about) ?>" placeholder="Enter a cool bio" class="w3-input w3-border w3-mobile" rows="4" cols="50"><?php echo htmlspecialchars($about) ?></textarea>
        <input type="submit" name="about" value="CHANGE ABOUT" class="w3-btn w3-blue w3-hover-opacity w3-mobile submit-btn" />
    </form>
</div>

    <h2>ACCOUNT SETTINGS</h2>

    <div id="offset" class="w3-center"><center>
        <form id="age" method="post" action="">
            <input type="number" value="<?php echo $age ?>" name="age" style="float:left;width:30%;" placeholder="New age" class="w3-input w3-border w3-mobile" />
            <input type="submit" name="a_change" style="float:left;" value="CHANGE AGE" class="w3-btn w3-blue w3-hover-opacity w3-mobile submit-btn" />
        </form><br /><br />

        <form id="password" method="post" action="">
            <input type="password" name="o_password" style="float:left;width:30%;" placeholder="Old password" class="w3-input w3-border w3-mobile" />
            <input type="password" name="n_password" style="float:left;width:30%;" placeholder="New password" class="w3-input w3-border w3-mobile" />
            <input type="submit" name="password_change" style="float:left;" value="CHANGE PASSWORD" class="w3-btn w3-blue w3-hover-opacity w3-mobile submit-btn" />
        </form><br /><br />

        <form id="email" method="post" action="">
            <input type="email" value="<?php echo htmlspecialchars($email) ?>" name="o_email" style="float:left;width:30%;" placeholder="Old email" class="w3-input w3-border w3-mobile" />
            <input type="email" name="n_email" style="float:left;width:30%;" placeholder="New email" class="w3-input w3-border w3-mobile" />
            <input type="submit" name="e_change" style="float:left;" value="CHANGE EMAIL" class="w3-btn w3-blue w3-hover-opacity w3-mobile submit-btn" />
        </form><br /><br />

        <form id="deactivate" method="post" action="">
            <p>Account deactivation is permanate. Enter your password to continue</p>
            <p><input type="email" value="" name="activate_pwd" style="float:left;width:60%;" placeholder="Password" class="w3-input w3-border w3-mobile" /></p>
            <p>Deactivation is not avalable yet. Check the footer below for more information.</p>
            <!--- <input type="submit" name="activate_btn" style="float:left;" value="DEACTIVATE" class="w3-btn w3-blue w3-hover-opacity w3-mobile submit-btn" /> -->
        </form><br /><br />

    </center></div>

     <hr /><h5>If you wish us to remove any personal details we hold about you, please email us at <i class="fa fa-envelope"><a href="mailto:sse2cpu@gmail.com">sse2cpu[at]gmail[dot]com</a></i></h5>
    <br /><br />

    <?php include ('../linkbar.php') ?>

</body>
</html>