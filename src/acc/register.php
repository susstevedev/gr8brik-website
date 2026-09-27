<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/account_settings.php';

if(loggedin() === true) {
    header('Location: index.php');
}

$utils = new ScreenNameUtils();
$combinedString = $utils->generateRandomScreenName();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Register</title>
    <?php include '../header.php' ?>
</head>
<body class="w3-container w3-light-blue">
    <?php include '../navbar.php'; ?>
    <script>
        $(document).ready(function() {
            $("#loginBtn").click(function(event) {
                event.preventDefault();

                var name = $("#loginForm input[name='name']").val();
                var mail = $("#loginForm input[name='mail']").val();
                var pwd = $("#loginForm input[name='pwd']").val();
                var prevBtnText = $("#loginBtn").html();

                $("#loginBtn").html('<img src="/img/loading.gif" style="width: 20px; height: 20px;" />');
                $("#loginBtn").prop("disabled", true);

                $.ajax({
                    url: "../ajax/auth",
                    method: "POST",
                    dataType: 'json',
                    data: { register: true, name: name, mail: mail, pwd: pwd },
                    success: function(response) {
                        $("#loginBtn").html(prevBtnText);
                		$("#loginBtn").prop("disabled", false);

                        if(response.success === true) {
                            window.location.href = "/";
                        } else {
                            $("#error").show()
                            $("#error-text").text(response.error);
                            $("#error").delay(5000).fadeOut(2500, function() {
                        		$("#error-text").text('');
                        	});
                        }
                    },

                    error: function(xhr, text, err) {
                        $("#loginBtn").html(prevBtnText);
                		$("#loginBtn").prop("disabled", false);

                        var response = JSON.parse(xhr.responseText);
                        console.error(text + ' ' + xhr.status + ' ' + err);

                        $("#error").show()
                        $("#error-text").text(response.error);
                        $("#error").delay(5000).fadeOut(2500, function() {
                        	$("#error-text").text('');
                        });
                    }
                });
            });
        });
    </script>

    <div id="error" style="display: none;" class="w3-red w3-card-2 w3-padding-small w3-round-small">
        <i class="fa fa-times-circle-o" aria-hidden="true"></i>
        <span id="error-text"></span>
    </div>

    <h2>Register Account</h2>
    <div id="loginForm" class="w3-container">
        <p>Already have an account? <a href="login">Login</a></p>
        <p><input class="login-input w3-input w3-border" value="<?php echo $combinedString; ?>" type="text" name="name" size="50px" placeholder="Unique username that is under 15 characters long"></p>
        <p><input class="login-input w3-input w3-border" type="email" name="mail" size="50px" placeholder="Email address"></p>
        <p><input class="login-input w3-input w3-border" type="password" name="pwd" size="50px" placeholder="Password that is at least 8 characters long"></p>
        
        <p>By registering, you are agreeing:</p>
        <ul>
            <li>To our <a href="/terms.php">Terms and Conditions</a>.</li>
            <li>To our <a href="/privacy.php">Privacy Policy</a>.</li>
            <li>That you are at least 13 years old or have parental consent to register an account.</li>
            <li>That you do not live in the UK or a province with "age verification" or "data verification" laws.</li>
        </ul>
        <button class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" id="loginBtn" name="login">Register</button>
        
    </div>

    <p>Or...</p>
    <div id="sso" class="w3-container">
        <a href="/ajax/auth.php?authtype=github" class="w3-btn w3-round w3-padding-small w3-white w3-hover-light-grey w3-border w3-border-grey" id="github" name="github"><i class="fa fa-github-square" aria-hidden="true"></i> Register with github</a>
        <a href="/ajax/auth.php?authtype=google" class="w3-btn w3-round w3-padding-small w3-white w3-hover-light-grey w3-border w3-border-grey" id="google" name="google"><i class="fa fa-google" aria-hidden="true"></i> Register with google</a>
    </div>

    <?php include '../linkbar.php' ?>
</body>
</html>