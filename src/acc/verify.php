<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/what_browser.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';

if(!loggedin()) {
    header('Location:login.php');
    exit;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Verify account</title>
    <?php include '../header.php' ?>
</head>
<body class="w3-light-blue w3-container">

    <?php 
        include('../navbar.php');
        include('panel.php');
    ?>

	<div class="w3-center">
		<?php
            $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
            if ($conn2->connect_error) {
                exit($conn2->connect_error);
            }

			if(isset($_GET['code'])) {
				$token = $_GET['code'];
				$verify = AccountManager::verifyUser($conn2, $token);
				
				if(isset($verify['error'])) {
					?>
					<article class='gr8-theme w3-card-2 w3-light-grey w3-padding w3-margin-bottom w3-round w3-large'>
						<h3>Error :(</h3>
						<span><?php echo $verify['error'] ?></span>
					</article>
					<?php
				} else if(isset($verify['success'])) {
					?>
					<article class='gr8-theme w3-card-2 w3-light-grey w3-padding w3-margin-bottom w3-round w3-large'>
						<h3>Success!</h3>
						<span><?php echo $verify['success'] ?></span><br />
						<a href="/acc/creations" class='w3-btn w3-blue w3-hover-opacity w3-padding-small w3-round w3-border w3-border-indigo'>Account dashboard</a>
					</article>
					<?php
				} else {
					?>
					<article class='gr8-theme w3-card-2 w3-light-grey w3-padding w3-margin-bottom w3-round w3-large'>
						<h3>Internal server error</h3>
						<span>There wasn't an error nor a success. Please try again later.</span>
					</article>
					<?php
				}
			}
		?><br /><br />
	</div>
		
    <?php include '../linkbar.php' ?>
</body>
</html>