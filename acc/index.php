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

/*if (isset($_POST['picture'])) {
    $dir = "users/pfps/";
    //$target_file = $dir . basename($_FILES['fileToupload']['name']);
    $target_file = $dir . basename($_FILES['fileToupload']);
    $upload = $dir . $id . '.jpg';

    if ($_FILES["fileToupload"]["size"] > 5242880) {
        $uploadOk = 0;
    }

    if ($uploadOk === 0) {
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['error' => 'Sorry, there was an error uploading your file.']);
        exit;
    } else {
        if (move_uploaded_file($_FILES["fileToupload"]["tmp_name"], $upload)) {
            $uploadOk = 1;
        } else {
            $uploadOk = 0;
        }
    }
}*/

if (isset($_POST['picture'])) {
    $id = $_SESSION['username'];
    $upload = "users/pfps/" . $id . '.jpg';

    if ($_FILES["fileToupload"]["size"] > 2621440) {
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['error' => 'File size exceeds 2.5MB limit.']);
        exit;
    }

    if (move_uploaded_file($_FILES["fileToupload"]["name"], $upload)) {
        header('Location: index.php');
        exit;
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['error' => 'File did not upload.']);
        exit;
    }
}

if(isset($_POST['deletePfp'])){
    $pfp = "users/pfps/" . $id . ".jpg";
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

if(isset($_POST['username_change'])){
    include $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';
    $new = htmlspecialchars($_POST['username']);
    $id = $_SESSION['username'];
    $valid = array('-', '_', '.', '+');
    if(empty($new)) {
        $error_message = "Username cannot be blank.";
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['error' => $error_message]);
        exit;
    }    
    if(!ctype_alnum(str_replace($valid, '', $new))) {
        $error_message = "Username has invalid characters.";
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['error' => $error_message]);
        exit;
    }
    if(strlen($new) > 51) {
        $error_message = "Username can only be 50 characters.";
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['error' => $error_message]);
        exit;
    }

	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

    $sql = "SELECT * FROM users WHERE username = '$new'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    if($result->num_rows != 0) {
        $error_message = "Username is taken.";
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['error' => $error_message]);
        exit;
    }

	$stmt_2 = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
    $stmt_2->bind_param("ss", $new, $id);
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

    if(strlen($new) > 16) {
        $error_message = "X handle's can only be 15 characters.";
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
    include $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';
    $new = htmlspecialchars($_POST['description']);
    $id = $_SESSION['username'];
    if(strlen($new) > 201) {
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
    }
    $stmt->close();
    exit;
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
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Settings</title>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="../lib/theme.css">
    <script src="../lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/jquery-migrate-1.4.1.min.js"></script>
    <meta charset="UTF-8">
    <meta name="description" content="Gr8brik is a block building browser game. No download required">
    <meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx">
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
        include('panel.php');
        if(file_exists('../acc/users/pfps/' . $id . '.jpg')) {
            $pfp = '../acc/users/pfps/' . $id . '.jpg';
        } else {
            $pfp = '../img/avatar.png';
        }
    ?>

    <script>
    $(document).ready(function() {
        $(".success").hide();
        $(".error").hide();

        $("#u_change").click(function(event) {
            event.preventDefault();

            var username = $("#username input[name='username']").val();

            $.ajax({
                url: "",
                method: "POST",
                data: { username_change: true, username: username },
                success: function(response) {
                    $(".success").show();
                    $(".success").text("Username updated!");
                    $(".usertext").text("@" + username);      
                },

                error: function(jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status === 500) {
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

            $.ajax({
                url: "",
                method: "POST",
                data: { about_change: true, description: description },
                success: function(response) {
                    $(".success").show();
                    $(".success").text("Bio updated!");
                },

                error: function(jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status === 500) {
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

            $.ajax({
                url: "",
                method: "POST",
                data: { twitter_change: true, handle: handle },
                success: function(response) {
                    $(".success").show();
                    $(".success").text("X updated!");
                },

                error: function(jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status === 500) {
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

        $("#picture").click(function(event) {
            event.preventDefault();
            $('#picture').attr('disabled','disabled');

            var fileToUpload = $("#pictureForm input[name='fileToUpload']").val();

            $.ajax({
                url: "",
                method: "POST",
                data: { picture: true, fileToUpload: fileToUpload },
                enctype: 'multipart/form-data',
                success: function(response) {
                    alert("Success!");
                    $('#picture').removeAttr('disabled');
                },

                error: function(jqXHR, textStatus, errorThrown) {
                    $('#picture').removeAttr('disabled');
                    if (jqXHR.status === 500) {
                        var response = JSON.parse(jqXHR.responseText);
                        alert(response.error);
                    } else {
                        console.error("AJAX Error:", textStatus, errorThrown);
                        alert("AJAX Error:", textStatus, errorThrown);
                    }
                }
            });
        });

    });
    </script>

    <style>
        .file-pfp {
            background-image: url('<?php echo $pfp ?>');
            width: 250px;
            height: 250px;
            background-repeat: no-repeat;
            background-size: cover;
            background-position: 50% 50%;
            border-radius: 6px;
            /* 
            background-color: #06402B;
            color: #fff;
            cursor: pointer; 
            */
        }
    </style>

    <p class="success w3-light-grey w3-card-2 w3-padding-small"></p>
    <p class="error w3-red w3-card-2 w3-padding-small"></p>

<div class="w3-row"><div class="w3-quarter">

    <div id="pictureForm" enctype="multipart/form-data">
		<p><input type="file" name="fileToupload" id="fileToupload" style="color:transparent;" onchange="this.style.color = 'black';" title=" " class="file-pfp"></p>
		<!-- <input type="submit" value="Upload Picture" id="picture" name="picture" class="w3-btn w3-blue w3-hover-white w3-mobile w3-border w3-border-indigo w3-round"> -->
        <button id="picture" name="picture" title="Any JPG, GIF, BMP or PNG" class="w3-btn w3-blue w3-hover-white w3-threequarter w3-mobile w3-border w3-border-indigo w3-round"><i class="fa fa-upload" aria-hidden="true"></i>Upload</button>
        <?php 
            /*if(file_exists('users/pfps/' . $id . '.jpg')){
		        echo '<input type="submit" value="Delete" name="deletePfp" class="w3-btn w3-red w3-hover-white w3-mobile w3-border w3-border-pink w3-round">';
            } */
        ?>
    </div>
</div><br /><br />
  
<div class="w3-threequarter">
    <div id="username">
        <input class="w3-input w3-border w3-mobile w3-third" value="<?php echo $user ?>" type="text" name="username" title="Must be unique, 1-50 characters, Letters, Numbers, Undercores and dashes allowed" placeholder="Username">
        <button class="w3-btn w3-blue w3-hover-white w3-quarter w3-mobile w3-border w3-border-indigo w3-round" id="u_change" name="u_change">Change Username</button>
    </div><br /><br />

    <div id="twitter">
        <input class="w3-input w3-border w3-mobile w3-third" placeholder="@your_x_handle" value="<?php echo $x ?>" type="text" name="handle">
        <button class="w3-btn w3-blue w3-hover-white w3-quarter w3-mobile w3-border w3-border-indigo w3-round" id="twitter_change" name="twitter_change">Change X handle</button>
    </div><br /><br />

    <div id="about">
        <textarea id="description" name="description" value="<?php echo htmlspecialchars($about) ?>" placeholder="New about section" class="w3-input w3-border w3-mobile" rows="4" cols="50"><?php echo htmlspecialchars($about) ?></textarea>
        <button class="w3-btn w3-blue w3-hover-white w3-quarter w3-mobile w3-border w3-border-indigo w3-round" id="a_change" name="about">Change About</button>
    </div><br /><br />

        <form id="password" method="post" action="">
            <input type="password" name="o_password" placeholder="Old password" class="w3-input w3-border w3-mobile w3-third" />
            <input type="password" name="n_password" placeholder="New password" class="w3-input w3-border w3-mobile w3-third" />
            <input type="submit" name="password_change" value="Change Password" class="w3-btn w3-blue w3-hover-white w3-third w3-mobile w3-border w3-border-indigo w3-round" />
        </form><br /><br />

        <form id="email" method="post" action="">
            <input type="email" value="<?php echo htmlspecialchars($email) ?>" name="o_email" style="float:left;width:30%;" placeholder="Old email" class="w3-input w3-border w3-mobile w3-third" />
            <input type="email" name="n_email" xstyle="float:left;width:30%;" placeholder="New email" class="w3-input w3-border w3-mobile w3-third" />
            <input type="submit" name="e_change" xstyle="float:left;" value="Change Email" class="w3-btn w3-blue w3-hover-white w3-third w3-mobile w3-border w3-border-indigo w3-round" /><br /><br />
        </form>
    </div></div><br /><br />

    <h5>If you wish us to remove any personal details we hold about you, please email us at <i class="fa fa-envelope"><a href="mailto:evanrutledge226@gmail.com">evanrutledge226[at]gmail[dot]com</a></i></h5>
    <br /><br />

    <?php include ('../linkbar.php') ?>

</body>
</html>