<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
isLoggedIn();

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
    $date = date("Y-m-d H:i:s");

    $target_dir = "../cre/";

    // Generate a unique ID
    $unique_id = uniqid();

    // Handle model file upload
    $original_file_name = basename($_FILES["fileToUpload"]["name"]);
    $file_extension = pathinfo($original_file_name, PATHINFO_EXTENSION);
    $new_file_name = $unique_id . '.' . $file_extension;
    $target_file = $target_dir . $new_file_name;

    // Handle screenshot file upload
    $screenshot_file_name = basename($_FILES["screenshotToUpload"]["name"]);
    $screenshot_extension = pathinfo($screenshot_file_name, PATHINFO_EXTENSION);
    $new_screenshot_name = $unique_id . '_screenshot.' . $screenshot_extension;
    $target_screenshot = $target_dir . $new_screenshot_name;

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO model (user, model, description, name, date, screenshot) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $id, $new_file_name, $description, $name, $date, $new_screenshot_name);

    // Execute the statement
    if ($stmt->execute()) {
        echo "File details have been added to the database.";
    } else {
        echo "Error: " . $stmt->error;
    }

    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file) && move_uploaded_file($_FILES["screenshotToUpload"]["tmp_name"], $target_screenshot)) {
        echo "The files have been uploaded.";

        $stmt->close();
    } else {
        echo "Sorry, there was an error uploading your files.";
    }
}

$conn->close();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>My Creations</title>
    <?php include '../header.php' ?>
</head>
<body class="w3-light-blue w3-container">

    <?php 
        include('../navbar.php');
        include('panel.php');
    ?>

        <div class="gr8-warning w3-card-2 w3-red w3-padding-tiny"><img src="../img/warning.jpg" style="width:20px;height=20px;"><b>You cannot edit a model after it is published.</b></div>

		<center>

                <h2 id="upload">Upload model</h2>

                <form action="" method="post" enctype="multipart/form-data">
                
                    <img src='../img/blured_model.jpg' width='320' height='240' loading='lazy'><br />

                    <p>Model screenshot (optional):</p>

                    <input type="file" name="screenshotToUpload" id="screenshotToUpload" style="width:30%">

                    <p>Model file:</p>

                    <input type="file" name="fileToUpload" id="fileToUpload" class="w3-input" style="width:30%">

                    <input type="text" placeholder="Model name" name="model_name" id="model_name" class="w3-input" style="width:30%"><br />
                
                    <!-- <input type="text" placeholder="Model description" name="description" id="description" class="w3-input" style="width:30%"> -->

                    <textarea placeholder="Model description" id="description" name="description" rows="4" cols="40"></textarea><br />
                
                    <input type="submit" name="upload" value="Upload" class="w3-btn w3-blue w3-hover-white w3-border w3-border-indigo">

                </form><hr /><div class="w3-row"><table class="w3-table-all" style="color:black;">
            <?php
                // Create connection
                $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

                // Check connection
                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }
                
                $sql = "SELECT id, user, model, name, screenshot FROM model WHERE user = ? ORDER BY date DESC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $id);
                $stmt->execute();
                $stmt->bind_result($model_id, $model_user, $model, $model_name, $model_screenshot);
            
                while ($stmt->fetch()) {

                    echo "<div class='w3-display-container w3-left w3-padding-small'>";
                    echo "<a href='../build/" . $model_id . "'><img src='../cre/" . $model_screenshot . "' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-border'></a>";
                    echo "<b class='w3-display-middle w3-text-grey'>" . $model_name . "</b></div>";
                }
            
            ?>

    </div></table></center><hr /><br /><br />
		
    <?php include('../linkbar.php') ?>
</body>
</html>