<?php
session_start();
if(isset($_POST['login'])){
    $username = preg_replace('/[^A-Za-z]/', '', $_POST['username']);
    $email = $_POST['email'];
    if(file_exists('users/' . $username . '.xml')){
        $xml = new SimpleXMLElement('users/' . $username . '.xml', 0, true); {
            if($username == $xml->username) {
                if($email == $xml->email) {
                    $new = md5($_POST['n_password']);
                    $xml->password = $new;
                    $xml->asXML('users/' . $username . '.xml');
                    header('Location: logout.php');
                    die;
                }
            }
        }
    }

}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Login - Gr8brik</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
</head>
<body class="w3-light-blue">
    <?php include '../navbar.php'; ?>
    <br />
    <center>
        <h1>Reset password</h1>
        <form method="post" action="">
            <p><input type="text" class="w3-input" placeholder="Username" name="username" style="width:30%" /></p>
            <p><input type="text" class="w3-input" placeholder="Email" name="email" style="width:30%" /></p>
            <p><input type="password" class="w3-input" placeholder="New password" name="n_password" style="width:30%" /></p>

            <p><input type="submit" value="Reset" name="login" class="w3-btn w3-green" /></p>
            <b>Or...</b>

        </form>
        <a href="login.php">Login</a>
        <?php include '../linkbar.php'; ?>
    </center>
</body>
</html>