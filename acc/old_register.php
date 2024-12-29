<?php
include 'classes/user.php';
require_once 'classes/membership.php';
$Membership = new Membership();

if(isset($_SESSION['username'])) {
    header('Location: feed.php');
}

if ($_POST && !empty($_POST['username']) && !empty($_POST['pwd']) && !empty($_POST['email'])) {
    $response = $Membership->register_User($_POST['username'], $_POST['pwd'], $_POST['email']);
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
    <title>Gr8brik.rf.gd | Create Account</title>
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
            <?php if (isset($response)) echo "<h4 class='alert w3-padding w3-red' id='alert'>" . $response . "  <button class='close w3-btn w3-blue w3-hover-opacity' id='close'>OKAY</button></h4>"; ?>
            <form method="post" action="">
				<b>Already have an account? <a href="login">Login</a></b><br />
                <h1>Register</h1>
                <p>
                    <label for="username">Username:</label>
                    <input type="text" value="<?php echo $combinedString; ?>" name="username" class="w3-input" required style="width:30%"/>
					<small>Username's are randomly generated</small>
				</p>
                <p>
                    <label for="email">E-mail:</label>
                    <input type="email" name="email" class="w3-input" required style="width:30%"/>
                </p>
                <p>
                    <label for="pwd">Password:</label>
                    <input type="password" name="pwd" id="pwd" class="w3-input" required style="width:30%"/>
					<input type="checkbox" class="w3-check" onclick="showPass();">
					<label class="w3-validate">Show password</label>
				</p>
                <p>
					<span onclick="document.getElementById('id02').style.display='block'" class="w3-btn w3-blue w3-hover-white">Next</span>
                </p>
				
            <div id="id02" class="w3-modal">
                <div class="w3-modal-content w3-card-2 w3-light-grey w3-center">
                    <div class="w3-container">
                        <span onclick="document.getElementById('id02').style.display='none'" class="w3-closebtn w3-red w3-hover-white w3-padding w3-display-topright">&times;</span>
                        <b>By using Gr8brik, you confirm your over the age of 13 or have parental permission, and agree to the <a href="../terms">Terms and Conditions</a>, along with the <a href="../privacy">Privacy Policy</a></b>
                        <p>Confirm your not a robot by writing the number box to box. Then press "accept".</p>
                        <?php
                            $notbotnumber = rand (1, 10000);
                            echo '<input autocomplete="false" type="text" name="notbot" placeholder=' . $notbotnumber . ' size="10" readonly />';
                        ?>
                        <input autocomplete="false" type="text" name="botbox" placeholder="<?php $notbot ?>" size="20" />
                        <p><input class="w3-btn w3-blue w3-hover-white" type="submit" id="submit" value="Finish" name="submit" /></p>
                    </div>
                </div>
            </div>
			
            </form>
        </div>
    </center>
	<?php include '../linkbar.php'; ?>
</body>
</html>
