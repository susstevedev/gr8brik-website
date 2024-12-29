<?php
	include 'classes/constants.php';
	require_once 'classes/membership.php';
	$Membership = new Membership();
	
	if(isset($_GET['status']) && $_GET['status'] == 'loggedout') {
		$Membership->log_User_Out();
	} else {
        if(isset($_SESSION['username'])) {
            header('Location: ../feed.php');
        }
    }
	
	if($_POST && !empty($_POST['username']) && !empty($_POST['pwd'])) {
		$response = $Membership->validate_User($_POST['username'], $_POST['pwd']);
		
	}


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Gr8brik.rf.gd | Login</title>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="../lib/theme.css">
    <script src="../lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <meta charset="UTF-8">
    <meta name="description" content="Gr8brik is a block building browser game. No download required">
    <meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
</head>
<body class="w3-container w3-light-blue">
	<?php include '../navbar.php'; ?>
	<script>
		function showPass() {
			var x = document.getElementById("pwd");
			if (x.type === "password") {
				x.type = "text";
			} else {
				x.type = "password";
			}
		}
        $(document).ready(function() {
			$("#alert").fadeOut(10000);
			$("#close").click(function(){
				$("#alert").hide();
			});
        });
    </script>
    <center>
        <div class="main w3-card-2 w3-light-grey w3-padding-tiny">
            <?php if(isset($response)) echo "<h4 class='alert w3-padding-tiny w3-red' id='alert'>" . $response . "  <button class='close w3-btn w3-blue w3-hover-opacity' id='close'>OKAY</button></h4>"; ?>
			<form method="post" action="">
                <b>Don't have an account? <a href="register">Register</a></b><br />
				<h1>Login</h1> 
				<p>
					<label for="username">Username:</label>
					<input type="text" class="w3-input" name="username" required style="width:30%"/>
				</p>
				<p>
					<label for="pwd">Password:</label>
					<input type="password" class="w3-input" name="pwd" id="pwd" required style="width:30%"/>
					<input type="checkbox" class="w3-check" onclick="showPass();">
					<label class="w3-validate">Show password</label>
				</p>
				<p>
					<input class="w3-btn w3-blue w3-hover-white" type="submit" id="submit" value="Login" name="submit" />
			</form>
        </div>
    </center>
</body>
</html>