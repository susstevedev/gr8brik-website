<?php

require_once 'classes/constants.php';

if(isset($_GET['status']) && $_GET['status'] === 'resume') {
	$_SESSION['username'] = $_COOKIE['userid'];
    $_SESSION['auth'] = true;
}
if(isset($_GET['status']) && $_GET['status'] === 'logout') {
    $tokenid = $_COOKIE['token'];
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

    $conn->query("DELETE FROM sessions WHERE id = '$tokenid'");
	session_destroy();
    header('../index.php');
} else {
    if(isset($_SESSION['username'])) {
        header('Location: index.php');
    }
}

if(isset($_POST['login'])) {
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        header('HTTP/1.0 500 Internal Server Error');
        echo json_encode(['error' => "Database connection failed"]);
        exit;
    }       
    $mail = $conn->real_escape_string($_POST['mail']);
    $pwd = md5($_POST['pwd']);

    if(empty($_POST['mail'])) {
		header('HTTP/1.0 500 Internal Server Error');
        echo json_encode(['error' => "Email cannot be blank"]);
        exit;
    }

    $sql = "SELECT * FROM users WHERE email = '$mail'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    if($pwd === $row['password']) {
        $username = $row['username'];
        $userid = $row['id'];
        $tokenid = uniqid('', true);

        $sql = "INSERT INTO sessions (id, user, username, password) VALUES ('$tokenid', '$userid', '$username', '$pwd')";
		if ($conn->query($sql) === TRUE) {
            setcookie('token', $tokenid, time() + (10 * 365 * 24 * 60 * 60), "/");
            $_SESSION['username'] = $userid;
            $_SESSION['auth'] = true;
        }
    } else {
		header('HTTP/1.0 500 Internal Server Error');
        echo json_encode(['error' => "Invalid email or password"]);
        exit;
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Login</title>
    <?php include '../header.php' ?>
</head>
<body class="w3-container w3-light-blue">
    <?php include '../navbar.php'; ?>
    <style>
        #welcome-mobile {
            display: none;
        }
        @media only screen and (max-width: 768px) {
            #linkbar, #mobilenav, #toggleModeMenu, #toggleLight, #toggleDark, #welcome-large, #loginForm {
                display: none;
            }
            #welcome-mobile {
                display: block;
            }
        }
    </style>
    <script>
        $(document).ready(function() {
            $("#error").hide();
            $("#loginBtn").click(function(event) {
                event.preventDefault();

                var mail = $("#loginForm input[name='mail']").val();
                var pwd = $("#loginForm input[name='pwd']").val();

                $.ajax({
                    url: "",
                    method: "POST",
                    data: { login: true, mail: mail, pwd: pwd },
                    success: function(response) {
                        window.location.href = "index.php";
                    },

                    error: function(jqXHR, textStatus, errorThrown) {
                        var response = JSON.parse(jqXHR.responseText);
                        console.error('Server status code: ' + textStatus + ' ' + jqXHR.status + ' ' + errorThrown);
                        $("#error").show()
                        $("#error-text").text(response.error);
                        $("#error").fadeOut(5000);
                    }
                });
            });
            $("#showLogin").click(function(event) {
                $("#loginForm").show().addClass("w3-animate-right");
                $("#welcome-mobile").addClass("w3-animate-left").hide();
            });
        });
    </script>
    <div id="error" style="display: none;" class="w3-red w3-card-2 w3-padding-tiny"><span class="fa fa-exclamation-triangle" aria-hidden="true"></span>&nbsp;<span id="error-text"></span></div>
    <div id="welcome-large">
        <h2>Login</h2>
    </div>
    <center>
        <div id="welcome-mobile">
            <img src="/img/logo.jpg" style="width: 15%; height: 15%; border-radius: 50%;">
            <p class="w3-large">Welcome to Gr8brik!</p>
            <p>The second most trending Lego modeler on the web!</p>
            <a href="/acc/register" class="w3-btn w3-blue w3-hover-white w3-mobile w3-border w3-border-indigo">Create account</a><br />
            <button class="w3-btn w3-blue w3-hover-white w3-mobile w3-border w3-border-indigo" id="showLogin" name="showLogin">Sign in</button><hr />
            <a href="/list" class="w3-btn w3-blue w3-hover-white w3-mobile w3-border w3-border-indigo">View without sign in</a>
        </div>
    </center>
    <div id="loginForm" class="w3-container">
        <b>Don't have an account? <a href="register">Register</a></b><br />
        <input class="w3-input w3-border" type="email" name="mail" placeholder="Email"><br />
        <input class="w3-input w3-border" type="password" name="pwd" placeholder="Password"><br />
        <button class="w3-btn w3-blue w3-hover-white w3-mobile w3-border w3-border-indigo" id="loginBtn" name="login">Login</button>
    </div>
    <?php include '../linkbar.php' ?>
</body>
</html>