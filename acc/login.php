<?php

require_once 'classes/constants.php';

if(isset($_GET['status']) && $_GET['status'] === 'logout') {
	session_destroy();
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

    $userid = $row['id'];

    $sql2 = "SELECT * FROM bans WHERE user = '$userid'";
    $result2 = $conn->query($sql2);
    $row2 = $result2->fetch_assoc();

    if ($row2['end_date'] >= time()) {
        header('HTTP/1.0 500 Internal Server Error');
        echo json_encode(['error' => $row['username'] . " has been banned until " . gmdate("y-m-d", $row2['end_date']) . " for " . $row2['reason']]);
        exit;
    }

    if($pwd === $row['password'] && !empty($row['username'])) {
        $_SESSION['username'] = $userid;
        $_SESSION['auth'] = true;
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
<body class="w3-container w3-light-blue">
    <?php include '../navbar.php'; ?>
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
                        var goUrl = "index.php?status=auth";
                        $("body").load(goUrl, function() {
                            history.pushState(null, "", goUrl);
                            window.scrollTo(0,0);
                            if (localStorage.getItem('mode')) {
                                $("body").addClass("w3-dark-grey");
                                $('#navbar').removeClass('w3-light-grey').addClass('w3-black'); 
                            }
                            document.title = "";
                        });
                    },

                    error: function(jqXHR, textStatus, errorThrown) {
                        var response = JSON.parse(jqXHR.responseText);
                        $("#error").fadeIn(3000).text(response.error).fadeOut(3000);                        
                    }
                });
            });
        });
    </script>
    <h2>Login</h2>
    <div id="error" class="w3-red w3-card-2 w3-padding-small"></div>
    <div id="loginForm" class="w3-container">
        <b>Don't have an account? <a href="register">Register</a></b><br />
        <input class="w3-input w3-border" type="email" name="mail" placeholder="Email"><br />
        <input class="w3-input w3-border" type="password" name="pwd" placeholder="Password"><br />
        <button class="w3-btn w3-blue w3-hover-white w3-mobile w3-border w3-border-indigo w3-round" id="loginBtn" name="login">Login</button>
    </div>
    <?php include '../linkbar.php' ?>
</body>
</html>