<?php
session_start();
if(file_exists('users/' . $_SESSION['username'] . '.xml')){
    header('Location: index.php');
}
$error = false;
$banned = false;
if(isset($_POST['login'])){
    $username = htmlspecialchars($_POST['username']);
    $password = md5($_POST['password']);
    if(file_exists('users/' . $username . '.xml')){
        $xml = new SimpleXMLElement('users/' . $username . '.xml', 0, true); {
            if($xml->username != '') {
                if($password == $xml->password) {
                    session_start();
                    $_SESSION['username'] = $username;
                        header('Location: index.php');
                        die;
                }
            }else{
                $banned = true;
            }
        }
    }

    $error = true;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Login - Gr8brik</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../w3.css">
    <link rel="stylesheet" href="../theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
</head>
<body class="w3-light-blue w3-container">
    <?php include '../navbar.php'; ?>
    <br />
    <center>
        <h1>Login</h1>
        <form method="post" action="">
            <p><input type="text" class="w3-input" placeholder="Username" name="username" style="width:30%" /></p>
            <p><input type="password" class="w3-input" placeholder="Password" name="password" style="width:30%" /></p>
            <?php
            if($error) { 
                echo '<b>Invalid username or password</b>';
            }
            if($banned) {
                echo '<br /><b>You are banned. Request to be unbanned <a href="../appeal.php">here</a>.</b> <br /><b>Reason:' . $xml->reason . '</b>';
            }

            ?>

            <p><input type="submit" value="LOGIN" name="login" class="w3-btn w3-round" /></p>
        </form>
        <a class="w3-large" href="register.php">Register</a>
        <br />
        <br />
        <br />
        <?php include('../linkbar.php') ?>
    </center>
</body>
</html>