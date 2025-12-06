<?php
require_once "com/bbcode.php";
require_once "acc/classes/user.php";
$bbcode = new BBCode;
if(isset($_POST['new_appeal'])) {
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
	    exit("Error:" . $conn->connect_error);
    }

    $reason = mysqli_real_escape_string($conn, $bbcode->toHTML($_POST['appealbox']));
    $duration = 604800;
    $end_date = time() + $duration;
		
    $sql = "INSERT INTO appeals (user, reason, end_date) VALUES ($id, '$reason', $end_date)";
    $result = $conn->query($sql);
    
    // susstevedev 11/28/25 12:08 CST: This is too basic, I'm going to add better success/error handling in the future
    if ($result) {
        exit("Success! Your ban appeal has been submitted.");
    } else {
        exit("Error: " . $conn->error . "<br />(if this is unrelated please lmk @ " . DB_MAIL . ")");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Appeal</title>
    <?php include 'header.php' ?>
</head>
<body class="w3-light-blue w3-container">

<?php 
    include('navbar.php');
?>

        <div class='w3-container'>
                <p>Want to build your own model? <a href='../modeler.php' class='w3-btn w3-blue w3-hover-opacity w3-mobile w3-border'>MODELER</a></p>
        </div>
        <br />
        <center>
            <h1>Appeal</h1>
            <h4>Note: you can use BBcode for formatting (size elements will be removed in post).</h4>
            <form method="post" action="">
                <div class="w3-row w3-padding">
                    <p><input type="text" value="<?php echo $user ?>" class="w3-input w3-border w3-hover-green w3-half w3-round" name="username" placeholder="Your username" readonly required/></p>
                    <p><input type="text" value="<?php echo $email ?>" class="w3-input w3-border w3-hover-green w3-half w3-round" name="email" placeholder="Your email" required /></p>
                </div>

                <p><textarea class="w3-border w3-hover-red w3-round" name="appealbox" placeholder="What did you do wrong and why should we reinstate you're account?" rows="4" cols="50"></textarea></p>
                <p><input type="submit" value="APPEAL" name="new_appeal" class="w3-btn w3-blue w3-hover-opacity w3-mobile w3-border" /></p>
            </form>
            <a href="report">Report</a>
        </center>
        <br />
        <br />
        <br />

    <?php include('linkbar.php') ?>


</body>
</html>