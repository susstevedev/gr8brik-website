<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';

if(isset($_GET['reactive'])) {
    $token = $_GET['token'];
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

    $tokendata = $conn->query("SELECT * FROM sessions WHERE id = '$token' LIMIT 1");
    $tokenrow = $tokendata->fetch_assoc();
    $user = (int)$tokenrow['user'];
    
    $stmt_2 = $conn->prepare("UPDATE users SET deactive = NULL WHERE id = ? LIMIT 1");
    $stmt_2->bind_param("i", $user);
    if ($stmt_2->execute()) {
        setcookie('token', $token, time() + (10 * 365 * 24 * 60 * 60), "/");
        header('Location: index.php');
        exit;
    }
}

isLoggedin();

/*if (isset($_POST["upload"])) {
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

        $stmt = $conn->prepare("INSERT INTO model (user, model, description, name) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userid, $new_file_name, $description, $name);

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

$conn->close(); */

if (isset($_POST['picture'])) {
    $okay = true;

    if(isset($_POST['deletePfp'])){
        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        $pfp = "users/pfps/" . $id . ".jpg";

        $stmt = $conn->prepare("UPDATE users SET picture = NULL WHERE id = ?");
        $stmt->bind_param("s", $id);
        if (!$conn->connect_error && $stmt->execute()) {
            unlink($pfp);
            ob_start();
            echo "Profile picture removed";
            ob_end_flush();
            header("refresh:3; url=index.php");
        } else {
            exit('Error removing profile picture');
        }
    } else {
        if($users_row['verify_token'] != NULL) {
            echo "Please verify your account to upload or edit your profile picture.";
            $okay = false;
            exit;
        }

        $mime = mime_content_type($_FILES["fileToUpload"]["tmp_name"]);

        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif'])) {
            echo "Only JPEG, PNG, and GIF files are allowed.";
            exit;
        }

        if(empty($_FILES['fileToUpload'])) {
            echo 'No file selected.';
            $okay = false;
            exit;
        }

        $db_pfp = '/acc/users/pfps/' . $id . '.jpg';
        $upload = "users/pfps/" . $id . ".jpg";

        if ($_FILES["fileToUpload"]["size"] > 5242880) {
            echo 'File too large.';
            $okay = false;
            exit;
        }

        if ($okay != false) {
            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $upload)) {
                $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

                if ($conn->connect_error) {
                    exit("database connection failed " . $conn->connect_error);
                }

                $stmt = $conn->prepare("UPDATE users SET picture = ? WHERE id = ?");
                $stmt->bind_param("ss", $db_pfp, $id);
                if ($stmt->execute()) {
                    ob_start();
                    echo "Profile picture updated";
                    ob_end_flush();
                    header("refresh:3; url=index.php");
                } else {
                    echo "Could not update profile picture row in the database";
                    exit;
                }
            } else {
                echo "Could not upload profile picture to file storage.";
                exit;
            }
        }
    }
}

if (isset($_POST['banner'])) {
    if(isset($_POST['deleteBanner'])){
        $pfp = "../acc/users/banners/" . $id . "..jpg";
        unlink($pfp);
        header("Location: index.php?deletedBanner=true");
    } else {

        if(empty($_FILES['fileToUpload'])) {
            $uploadOkay = 0;
        }

        $dir = "../acc/users/banners/";
        $target_file = $dir . basename($_FILES['fileToupload']['name']);
        $upload = $dir . $id . '..jpg';

        if ($_FILES["fileToupload"]["size"] > 5242880) {
            $uploadOk = 0;
        }

        if ($uploadOk === 0) {
            echo 'Sorry, there was an error uploading your file.';
        } else {
            if (move_uploaded_file($_FILES["fileToupload"]["tmp_name"], $upload)) {
                $uploadOk = 1;
            } else {
                $uploadOk = 0;
            }
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

if(isset($_POST['username_change'])){
    include $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';
    $new = htmlspecialchars($_POST['username']);
    if(!isset($_SESSION['username'])) {
        $error_message = "Session expired.";
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['error' => $error_message]);
        exit;
    }
    $id = $_SESSION['username'];
    if(empty($new)) {
        $error_message = "Username cannot be blank.";
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['error' => $error_message]);
        exit;
    }    
    if(!ctype_alnum(str_replace(array('-', '_', '.'), '', $new))) {
        $error_message = "Username has invalid characters.";
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['error' => $error_message]);
        exit;
    }
    if(strlen($new) > 50) {
        $error_message = "Username can only be 50 characters.";
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['error' => $error_message]);
        exit;
    }
    if(strlen($new) < 2) {
        $error_message = "Username must be more than 2 characters.";
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['error' => $error_message]);
        exit;
    }
    if(!empty($changed)) {
        if (($changed - time()) < 86400) {
            $change_when = $changed - time();
            $error_message = "You can only change your username once every day (on " . date("M d, Y H:i", $change_when) . ".";
            header("HTTP/1.0 500 Internal Server Error");
            echo json_encode(['error' => $error_message]);
            exit;
        }
    }

	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

    $sql = "SELECT * FROM users WHERE username = '$new'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    if($result->num_rows != 0) {
        if($row['id'] === $id) {
            $error_message = "This is your current username.";
            header("HTTP/1.0 500 Internal Server Error");
            echo json_encode(['error' => $error_message]);
            exit;
        }
        $error_message = "Username is taken.";
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['error' => $error_message]);
        exit;
    }

    $changed = time();

	$stmt_2 = $conn->prepare("UPDATE users SET username = ?, changed = ? WHERE id = ?");
    $stmt_2->bind_param("sss", $new, $changed, $id);
    if ($stmt_2->execute()) {
        echo "Success!";
        header('Location: index.php');
        exit;
    }
}

if(isset($_POST['twitter_change'])){
    include $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';
    $new = htmlspecialchars($_POST['handle']);
    $id = $_SESSION['username'];

    if(strlen($new) > 15) {
        $error_message = "Twitter handle's can only be 15 characters.";
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['error' => $error_message]);
        exit;
    }

	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

	$stmt_2 = $conn->prepare("UPDATE users SET twitter = ? WHERE id = ?");
    $stmt_2->bind_param("ss", $new, $id);
    if ($stmt_2->execute()) {
        echo "Success!";
        header('Location: index.php');
        exit;
    }
}

if(isset($_POST['about_change'])){
    $new = $_POST['description'];
    $id = $token['user'];
    if(strlen($new) > 200) {
        $error_message = "Bio can only be 200 characters.";
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['error' => $error_message]);
        exit;
    }

	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
	$stmt_2 = $conn->prepare("UPDATE users SET description = ? WHERE id = ?");
    $stmt_2->bind_param("ss", $new, $id);
    if ($stmt_2->execute()) {
        echo "Success!";
        header('Location: index.php');
        exit;
    } else {
        $error_message = "Error changing bio.";
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['error' => $error_message]);
        exit;
    }
    $stmt->close();
    exit;
}

$email_error = false;
if(isset($_POST['e_change'])){
    $old = $_POST['o_email'];
    $new = $_POST['n_email'];
    $confirm = $_POST['c_email'];
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
if(isset($_POST['deactive'])) {
    $id = $conn->real_escape_string($token['user']);
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    $date = date("Y-m-d");
    
    $stmt_2 = $conn->prepare("UPDATE users SET deactive = ? WHERE id = ? LIMIT 1");
    $stmt_2->bind_param("si", $date, $id);
    if ($stmt_2->execute()) {
        logout();
    }
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Settings</title>
    <?php include '../header.php' ?>
</head>
<body class="w3-light-blue w3-container">

    <?php 
        include('../navbar.php');
        include('panel.php');

        $words = ['Brick', 'Minifig', 'Stud', 'Build', 'Block', 'Stack', 'Baseplate', 'Roadplate', 'Fanatic', 'Craftsman', 'Awesome', 'Great'];
        $randomKeys = array_rand($words, 2);
        $randomWord1 = $words[$randomKeys[0]];
        $randomWord2 = $words[$randomKeys[1]];
        $randomNumber = rand(100, 999);

        $combinedString = $randomWord1 . $randomWord2 . $randomNumber;
    ?>

    <div id="deactive" class="w3-modal">
		<div class="w3-modal-content w3-card-2 w3-light-grey w3-center">
			<div class="w3-container">
				<span onclick="document.getElementById('deactive').style.display='none'" class="w3-closebtn w3-red w3-hover-white w3-padding w3-display-topright">&times;</span>
				<form method='post' action='/ajax/account_settings.php'>
					<h2>Are you sure you want to deactivate your account?</h2>
                    <p><input type="password" name="password" placeholder="Password" class="w3-input w3-border w3-mobile" required /></p>
                    <p>You can restore your account until 30 days after you deactivate. After 30 days, your account will be deleted and all data anonymized.</p>
                    <p><b class="w3-red">Your data may not be recoverable after 30 days.</b></p>
					<span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('deactive').style.display='none'">No</span>&nbsp;
					<input type="submit" value="Yes" name="deactive_account" class="w3-btn w3-large w3-white w3-hover-red">
				</form>
			</div>
		</div>
	</div>

    <script>
    $(document).ready(function() {
        $(".success").hide();
        $(".error").hide();

        $("#username-input").on("input", function() {
            var newUsername = $(this).val();
            var token = $.cookie('token');
            $("#u_change").attr('disabled', true).html('<img src="/img/loading.gif" style="width: 25px; height: 25px;" />');

            $.ajax({
                url: "../ajax/account_settings",
                method: "GET",
                data: { check_username_available: true, username: newUsername, token: $.cookie('token') },
                success: function(response) {
                    $("#data-username-available").hide();
                    setTimeout(function() {
                        if(response.available === "1") {
                            $("#u_change").html('Change Username').attr('disabled', false);
                            $("#data-username-available").show().css("background-color", "green").text('Username available.').delay(10000).fadeOut();
                        } else if(response.available === "0") {
                            $("#u_change").html('Change Username');
                            $("#data-username-available").show().css("background-color", "red").text(response.reason).delay(10000).fadeOut();
                        }
                    }, 2500);
                },

                error: function(jqXHR, textStatus, errorThrown) {
                    var response = JSON.parse(jqXHR.responseText);
                    console.error(jqXHR, textStatus, errorThrown);
                    $(".error").show();
                    $(".error").text(response.error);
                }
            });
        });

        $("#u_change").click(function(event) {
            event.preventDefault();

            var username = $("#username input[name='username']").val();
            var token = $.cookie('token');
            if ($("#username input[name='username']").val().trim() === '') {
                var username = $("#username input[name='username']").attr('placeholder');
            }

            $.ajax({
                url: "../ajax/account_settings",
                method: "GET",
                data: { username_change: true, username: username, token: token },
                success: function(response) {
                    $(".success").show();
                    $(".success").text("Username updated!");
                    $(".usertext").text(username);      
                },

                error: function(jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status === 403) {
                        appCrash('403', 'Invalid login');
                    }else if (jqXHR.status === 500) {
                        var response = JSON.parse(jqXHR.responseText);
                        $(".error").show();
                        $(".error").text(response.error);
                    } else {
                        console.error("AJAX Error:", textStatus, errorThrown);
                        alert("An error occurred. Please try again later.");
                    }
                }
            });
        });

        $("#a_change").click(function(event) {
            event.preventDefault();

            var description = $("#about textarea[name='description']").val();
            var token = $.cookie('token');

            $.ajax({
                url: "../ajax/account_settings",
                method: "GET",
                data: { about_change: true, description: encodeURIComponent(description), token: token },
                success: function(response) {
                    $(".success").show();
                    $(".success").text(response.success);
                },

                error: function(jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status === 403) {
                        appCrash('403', 'Invalid login');
                    }else if (jqXHR.status === 500) {
                        var response = JSON.parse(jqXHR.responseText);
                        $(".error").show();
                        $(".error").text(response.error);
                    } else {
                        console.error("AJAX Error:", textStatus, errorThrown);
                        alert("An error occurred. Please try again later.");
                    }
                }
            });
        });
        $("#twitter_change").click(function(event) {
            event.preventDefault();

            var handle = $("#twitter input[name='handle']").val();
            var token = $.cookie('token');

            $.ajax({
                url: "../ajax/account_settings",
                method: "GET",
                data: { twitter_change: true, handle: handle, token: token },
                success: function(response) {
                    $(".success").show();
                    $(".success").text("Twitter updated!");
                },

                error: function(jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status === 403) {
                        appCrash('403', 'Invalid login');
                    }else if (jqXHR.status === 500) {
                        var response = JSON.parse(jqXHR.responseText);
                        $(".error").show();
                        $(".error").text(response.error);
                    } else {
                        console.error("AJAX Error:", textStatus, errorThrown);
                        alert("An error occurred. Please try again later.");
                    }
                }
            });
        });
    });
    </script>

    <style>
        .file-pfp {
            background-image: url('<?php echo "../acc/users/pfps/" . $id . ".jpg?t=" . time() ?>');
            width: 250px;
            height: 250px;
            background-repeat: no-repeat;
            background-size: cover;
            background-position: 50% 50%;
            border-radius: 15px;
            background-color: #06402B;
            color: #fff;
            cursor: pointer; 
        }
        .file-banner {
            background-image: url('<?php echo "../acc/users/banners/" . $id . ".jpg?t=" . time() ?>');
            width: 50%;
            height: 25%;
            height: 25vh;
            background-repeat: no-repeat;
            background-size: cover;
            background-position: 50% 50%;
            border-radius: 15px;
            background-color: #06402B;
            color: #fff;
            cursor: pointer; 
        }
    </style>

    <h1>Settings</h1>

    <p class="success w3-light-grey w3-card-2 w3-padding-small"></p>
    <p class="error w3-red w3-card-2 w3-padding-small"></p>

    <h2>Account</h2>
    
    <form class="pictureForm w3-center" method="post" action="" enctype="multipart/form-data">
        <p>We recommend your banner be 250x500 pixels</p>
		<p><input type="file" name="fileToupload" id="fileToupload" style="color:transparent;" onchange="this.style.color = 'black';" title=" " class="file-banner"></p>
        <?php 
            if(file_exists('users/banners/' . $id . '..jpg')){
                echo '<input type="checkbox" class="w3-check" id="deleteBanner" name="deleteBanner" value="1">';
                echo '<label for="deleteBanner">Remove banner</label>';
            }
        ?>
		<input type="submit" value="Upload" id="banner" name="banner" class="w3-btn w3-blue w3-hover-white w3-border w3-border-indigo"><br /><br />
    </form>

<div class="w3-row"><div class="w3-quarter">

    <form id="pictureForm" method="post" action="" enctype="multipart/form-data">
        <p>We recommend your profile picture be 50x50 pixels</p>
		<p><input type="file" name="fileToUpload" id="fileToUpload" style="color:transparent;" onchange="this.style.color = 'black';" title=" " class="file-pfp"></p>
		<input type="submit" value="Upload" id="picture" name="picture" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo w3-quarter">
        <?php 
            if(file_exists('users/pfps/' . $id . '.jpg')){
                echo '&nbsp;&nbsp;<input type="checkbox" class="w3-check" id="deletePfp" name="deletePfp" value="1">';
                echo '<label for="deletePfp">Remove</label>';
            }
        ?>
    </form>
</div><br /><br />
  
<div class="w3-threequarter">
    <div id="username">
        <p>Your username can only be 2-50 numbers, letters, underscore, dash, or dot. Make sure it follows the <a href="/rules" target="_blank" rel="noopener noreferrer">Rules</a>.</p>
        <p class="gr8-child"><span id="data-username-available" style="display: none; padding: 5px 5px 5px 5px;"></span></p>
        <input class="w3-input w3-border w3-mobile w3-third" value="<?php echo $user ?>" type="text" id="username-input" name="username" placeholder="<?php echo $combinedString ?>">
        <button class="w3-btn w3-blue w3-hover-white w3-quarter w3-border w3-border-indigo" id="u_change" name="u_change">Change Username</button>
    </div><br /><br />

    <div id="twitter">
        <input class="w3-input w3-border w3-mobile w3-third" placeholder="@handle" value="<?php echo $x ?>" type="text" name="handle">
        <button class="w3-btn w3-blue w3-hover-white w3-quarter w3-border w3-border-indigo" id="twitter_change" name="twitter_change">Change Linked Twitter</button>
    </div><br /><br />

    <div id="about">
        <p>Your description can be 1-200 characters of anything. Keep it clean and make sure it doesn't violate our <a href="/rules" target="_blank" rel="noopener noreferrer">Rules</a>.</p>
        <textarea id="description" name="description" value="<?php echo htmlspecialchars($about) ?>" placeholder="New about section" class="w3-input w3-border w3-mobile" rows="4" cols="50"><?php echo htmlspecialchars($about) ?></textarea>
        <button class="w3-btn w3-blue w3-hover-white w3-quarter w3-border w3-border-indigo" id="a_change" name="about">Change About</button>
    </div><br /><br />

        <form id="password" method="post" action="/ajax/account_settings">
            <p>Your password to login to your account. Don't share this with anyone. Keep this saved somewhere, like a secure password manager.</p>
            <input type="password" name="o_password" placeholder="Old password" class="w3-input w3-border w3-mobile w3-third" />
            <input type="password" name="n_password" placeholder="New password" class="w3-input w3-border w3-mobile w3-third" />
            <input type="password" name="c_password" placeholder="Confirm new password" class="w3-input w3-border w3-mobile w3-third" />
            <input type="submit" name="change" value="Change Password" class="w3-btn w3-blue w3-hover-white w3-third w3-border w3-border-indigo" />
        </form><br /><br />

        <form id="email" method="post" action="">
            <p>Your email to login to your account. Don't share this either. Make sure you own this email incase you ever lose access to your account.</p>
            <input type="email" value="<?php echo htmlspecialchars($email) ?>" name="o_email" style="float:left;width:30%;" placeholder="Old email" class="w3-input w3-border w3-mobile w3-third" />
            <input type="email" name="n_email" xstyle="float:left;width:30%;" placeholder="New email" class="w3-input w3-border w3-mobile w3-third" />
            <input type="submit" name="e_change" xstyle="float:left;" value="Change Email" class="w3-btn w3-blue w3-hover-white w3-third w3-border w3-border-indigo" /><br /><br />
        </form>
    </div></div><br /><br />

    <a class="w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink" href="login.php?status=logout">Logout</a>

    <h2>Danger zone</h2>
    
    <div class="tooltip">
    <button onclick="document.getElementById('deactive').style.display='block'" name='deactive' class='w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink' />Deactivate Account</button>
        <span class="w3-tag w3-round w3-blue w3-padding-small tooltiptext"><i class="fa fa-info-circle" aria-hidden="true"></i>Deactivate your account.</span>
    </div>

    <?php include ('../linkbar.php') ?>
</body>
</html>