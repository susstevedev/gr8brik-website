<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';

if(loggedin() === true) {
    header('Location: index.php');
}

/*$words = ['Brick', 'Minifig', 'Stud', 'Build', 'Block', 'Stack', 'Baseplate', 'Roadplate', 'Fanatic', 'Craftsman', 'Awesome', 'Great'];
$randomKeys = array_rand($words, 2);
$randomWord1 = $words[$randomKeys[0]];
$randomWord2 = $words[$randomKeys[1]];
$randomNumber = rand(100, 999);

$combinedString = $randomWord1 . $randomWord2 . $randomNumber;*/

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

                $.ajax({
                    url: "../ajax/auth",
                    method: "POST",
                    data: { register: true, name: name, mail: mail, pwd: pwd },
                    success: function(response) {
                        if(response.success === true) {
                            window.location.href = "/";
                        } else {
                            alert(response.error || "An unknown error occurred. Please try again later.")
                        }
                    },

                    error: function(jqXHR, textStatus, errorThrown) {
                        if (jqXHR.status === 500 || jqXHR.status === 400) {
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
    </script>
    <h2>Register Account</h2>
    <div id="loginForm" class="w3-container">
        <p>Already have an account? <a href="login">Login</a></p>
        <p><input class="w3-border" value="<?php echo $combinedString; ?>" type="text" name="name" size="50px" placeholder="Unique username that is under 15 characters long"></p>
        <p><input class="w3-border" type="email" name="mail" size="50px" placeholder="Email address"></p>
        <p><input class="w3-border" type="password" name="pwd" size="50px" placeholder="Password that is at least 8 characters long"></p>
        
        <p>By registering, you are agreeing:</p>
        <ul>
            <li>To our <a href="/terms.php">Terms and Conditions</a>.</li>
            <li>To our <a href="/privacy.php">Privacy Policy</a>.</li>
            <li>That you are at least 13 years old or have parental consent to register an account.</li>
            <li>That you do not live in the UK or a province with "age verification" or "data verification" laws.</li>
        </ul>
        <button class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" id="loginBtn" name="login">Register</button>
        
    </div>
    <?php include '../linkbar.php' ?>
</body>
</html>