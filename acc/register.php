<?php

require_once 'classes/constants.php';

if(isset($_SESSION['username'])){
    session_start();
    header('Location: index.php');
}

if(isset($_POST['login'])) {
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['error' => "An error occured while connecting to the database"]);
        exit;
    }
    $username = $conn->real_escape_string(htmlspecialchars($_POST['name']));	
	$password = md5($_POST['pwd']);
	$email = $conn->real_escape_string($_POST['mail']);
    $age = date("Y-m-d");

    if(empty($_POST['pwd']) || empty($_POST['mail']) || empty($_POST['name'])) {
		header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['error' => "One of the field(s) are blank"]);
        exit;
    }
		
	$sql_check = "SELECT id FROM users WHERE username = '$username' UNION ALL SELECT id FROM users WHERE email = '$email'";
    $stmt = $conn->query($sql_check);

	if ($stmt->num_rows > 0) {
		header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['error' => "Username or email already in use"]);
        exit;
	} else {
		$sql = "INSERT INTO users (username, password, email, age) VALUES ('$username', '$password', '$email', '$age')";
		if ($conn->query($sql) === TRUE) {
            $sql = "SELECT id FROM users WHERE username = '$username'";
            $result = $conn->query($sql);
            $row2 = $result->fetch_assoc();

            $userid = $row2['id'];
            $tokenid = uniqid('', true);

            $sql = "INSERT INTO sessions (id, user, username, password) VALUES ('$tokenid', '$userid', '$username', '$password')";
            if ($conn->query($sql) === TRUE) {
                setcookie('token', $tokenid, time() + (10 * 365 * 24 * 60 * 60), "/");
                $_SESSION['username'] = $userid;
                $_SESSION['auth'] = true;
            }
		} else {
			header("HTTP/1.0 500 Internal Server Error");
            echo json_encode(['error' => " "]);
            exit;
		}
	}   
}

$words = ['Brick', 'Minifig', 'Stud', 'Build', 'Block', 'Stack', 'Baseplate', 'Roadplate', 'Fanatic', 'Craftsman', 'Awesome', 'Great'];
$randomKeys = array_rand($words, 2);
$randomWord1 = $words[$randomKeys[0]];
$randomWord2 = $words[$randomKeys[1]];
$randomNumber = rand(100, 999);

$combinedString = $randomWord1 . $randomWord2 . $randomNumber;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Create Account</title>
    <?php include '../header.php' ?>
</head>
<body class="w3-container w3-light-blue">
    <?php include '../navbar.php'; ?>
     <style>
        @media only screen and (max-width: 768px) {
            #linkbar, #mobilenav, #toggleModeMenu, #toggleLight, #toggleDark {
                display: none;
            }
        }
    </style>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        $(document).ready(function() {
            $("#loginBtn").click(function(event) {
                event.preventDefault();

                var name = $("#loginForm input[name='name']").val();
                var mail = $("#loginForm input[name='mail']").val();
                var pwd = $("#loginForm input[name='pwd']").val();

                $.ajax({
                    url: "",
                    method: "POST",
                    data: { login: true, name: name, mail: mail, pwd: pwd },
                    success: function(response) {
                        window.location.href = "index.php?status=auth";
                    },

                    error: function(jqXHR, textStatus, errorThrown) {
                        if (jqXHR.status === 500) {
                            var response = JSON.parse(jqXHR.responseText);
                            alert(response.error);
                        } else {
                            console.error("AJAX Error:", textStatus, errorThrown);
                            alert("An error occurred. Please try again later.");
                        }
                    }
                });
            });
        });
    });
    </script>
    <h2>Create Account</h2>
    <div id="loginForm" class="w3-container">
        <b>Already have an account? <a href="login">Login</a></b><br />
        <input class="w3-input w3-border" value="<?php echo $combinedString; ?>" type="text" name="name" placeholder="Username"><br />
        <input class="w3-input w3-border" type="email" name="mail" placeholder="Email"><br />
        <input class="w3-input w3-border" type="password" name="pwd" placeholder="Password"><br />
        <button class="w3-btn w3-blue w3-hover-white w3-mobile w3-border w3-border-indigo" id="loginBtn" name="login">Create Account</button>
    </div>
    <?php include '../linkbar.php' ?>
</body>
</html>