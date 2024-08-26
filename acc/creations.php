<?php

session_start();

require_once 'classes/user.php';

if(!isset($_SESSION['username'])){

    header('Location: acc/login.php');

}

define('DB_NAME2', 'if0_36019408_creations');

// Create connection
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST["upload"])) {
	$description = $_POST['description'];
	$name = $_POST['model_name'];
	$userid = $id;
    $date = date ("F d Y H:i:s.");
	
    $target_dir = "../cre/";
    $original_file_name = basename($_FILES["fileToUpload"]["name"]);
    $file_extension = pathinfo($original_file_name, PATHINFO_EXTENSION);
    $new_file_name = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_file_name;

    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        echo "The file ". htmlspecialchars($original_file_name). " has been uploaded as " . $new_file_name;

        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO model (user, model, description, name, date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $id, $new_file_name, $description, $name, $date);

        // Execute the statement
        if ($stmt->execute()) {
            echo "File details have been added to the database.";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
}

$conn->close();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Creations / GR8BRIK</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="../lib/theme.css">
    <script src="../lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <meta charset="UTF-8">
    <meta name="description" content="Gr8brik is a block building browser game. No download required">
    <meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="w3-light-blue w3-container">

    <?php include('../navbar.php') ?>
	
	<div class="w3-center">
        <div class="w3-light-grey w3-card-24 w3-center">
            <?php 
				if($alert != 0){
					echo '<h1 style="color:red;">You have new notifications!</h1>';
				}
                echo "<h3>Welcome,</h3><img id='pfp' src='users/pfps/" . $id . "..jpg' class='w3-hover-opacity w3-border w3-card-24' onclick=document.getElementById('im').style.display='block' />";
                echo '<h1>' . $user . '</h1><h3>@' . $handle . '</h3>';
            ?>
            <a href="<?php echo $handle ?>" class="w3-btn w3-large w3-white w3-hover-blue"><li class="fa fa-user" aria-hidden="true"></li><b>PROFILE</b></a>
			<a href="new.php" class="w3-btn w3-large w3-white w3-hover-blue"><i class="fa fa-bell" aria-hidden="true"></i><b>NOTIFICATIONS</b></a>
            <a href="creations.php" class="w3-btn w3-large w3-white w3-hover-blue"><i class="fa fa-building" aria-hidden="true"></i><b>MY CREATIONS</b></a>
        </div><br /><br />

		<center>

			<h2 id="upload">Upload model</h2>

			<form action="" method="post" enctype="multipart/form-data">

				<input type="file" name="fileToUpload" id="fileToUpload" class="w3-input" style="width:30%">

				<input type="text" value="Model name" name="model_name" id="model_name" class="w3-input" style="width:30%">
			
				<input type="text" value="Model description" name="description" id="description" class="w3-input" style="width:30%">
			
				<input type="submit" name="upload" value="UPLOAD" class="w3-btn w3-blue w3-hover-opacity">

			</form><br />

        </center>

		<?php
			// Create connection
			$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

			// Check connection
			if ($conn->connect_error) {
				die("Connection failed: " . $conn->connect_error);
			}
			
			$sql = "SELECT id, user, model, name FROM model WHERE user = ? ORDER BY date DESC";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s", $id);
			$stmt->execute();
			$stmt->bind_result($model_id, $model_user, $model, $model_name);
        
			while ($stmt->fetch()) {
				
						echo "<div class='w3-display-container w3-left'>";
						echo "<a href='../creation.php?id=" . $model_id . "'><img src='../img/blured_model.jpg' width='320' height='240' loading='lazy' class='w3-hover-opacity w3-round w3-border'></a>";
						echo "<b class='w3-display-middle'>" . $model_name . "</b></div>";
					 }
		
		?>
		
	</div><br /><br />
		
    <?php include '../linkbar.php' ?>
</body>
</html>