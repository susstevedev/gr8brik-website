<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';

if(isset($_GET['status']) && $_GET['status'] === 'logout') {
    $tokenid = htmlspecialchars($_COOKIE['token']);
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

    $conn->query("DELETE FROM sessions WHERE id = '$tokenid'");
	session_destroy();
    header('login.php');
}
isLoggedin();

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
        .w3-input {
            width: 50%;
        }
        @media only screen and (max-width: 768px) {
            #linkbar, #mobilenav, #toggleModeMenu, #toggleLight, #toggleDark, #welcome-large, #loginForm {
                display: none;
            }
            #welcome-mobile {
                display: block;
            }
            .w3-input {
                width: 100%;
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
                    url: '/ajax/auth',
                    method: "POST",
                    data: { login: true, mail: mail, pwd: pwd },
                    success: function(response) {
                        if(response.success === true) {
                            window.location.href = "/";
                        } else {
                            $("#error").show()
                            $("#error-text").text(response.error || "An error occured. Please try again later.");
                            $("#error").delay(5000).fadeOut(2500);
                        }
                    },

                    error: function(jqXHR, textStatus, errorThrown) {
                        var response = JSON.parse(jqXHR.responseText);
                        console.error('Server status code: ' + textStatus + ' ' + jqXHR.status + ' ' + errorThrown);
                        if(response.popup) {
                            $("#popup").show();
                            $("#popup-text").text(response.popup);
                            $("#popup-btn").attr("href", response.goto)
                        }
                        $("#error").show()
                        if(!response.error) {
                            $("#error-text").text("An error occured. Please try again later.");
                        } else if(response.error) {
                            $("#error-text").text(response.error);
                        }
                        $("#error").delay(5000).fadeOut(2500);
                    }
                });
            });
            $("#showLogin").click(function(event) {
                $("#loginForm").show().addClass("w3-animate-right");
                $("#welcome-mobile").addClass("w3-animate-left").hide();
            });
        });
    </script>

    <div id="popup" class="w3-modal w3-card-2">
        <div class="w3-modal-content">
            <header class="w3-container w3-teal"> 
                <span onclick="document.getElementById('popup').style.display='none'" 
                class="w3-button w3-display-topright">&times;</span>
                <h2>Important Modal</h2>
            </header>
            <div class="w3-container">
                <p id="popup-text"></p>
                <a href="" class="w3-btn w3-blue w3-hover-white w3-border w3-border-indigo" id="popup-btn">Yes</a>
            </div>
        </div>
    </div>

    <div id="error" style="display: none;" class="w3-red w3-card-2 w3-padding w3-round"><span class="fa fa-exclamation-triangle" aria-hidden="true"></span>&nbsp;<span id="error-text"></span></div>
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
        <span>Don't have an account? <a href="register">Register</a></span><br />
        <input class="w3-input w3-border" type="email" name="mail" placeholder="Email or username"><br />
        <input class="w3-input w3-border" type="password" name="pwd" placeholder="Password"><br />
        <span><small>Your session will be remembered, so you don't need to re-login everytime you visit our services.</small></span><br />
        <button class="w3-btn w3-blue w3-hover-opacity w3-round w3-padding w3-border w3-border-indigo" id="loginBtn" name="login">Login</button>
    </div>
    <?php include '../linkbar.php' ?>
</body>
</html>