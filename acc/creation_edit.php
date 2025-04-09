<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
isLoggedIn();

define('DB_NAME2', 'if0_36019408_creations');
$model_id = $_GET['id'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Edit Creation</title>
    <?php include '../header.php' ?>
</head>
<body class="w3-light-blue w3-container">

    <?php 
        include('../navbar.php');
        include('panel.php');
    ?>

		<center><div class="w3-row" style="width:75%;"><table class="w3-table-all" style="color:black;">
            <?php
                $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
                
                $sql = "SELECT * FROM model WHERE id = $model_id";
                $result = $conn->query($sql);
                $row = $result->fetch_assoc();
                $error = false;

                if($result->num_rows === 0) {
                    echo "<h4>Error: Invalid creation ID.</h4>";
                    $error = true;
                }

                if($row['user'] != $id) {
                    echo "<h4>Error: Invalid login match with creation creator.</h4>";
                    $error = true;
                }

                /*$stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $model_id);
                $stmt->execute();
                $stmt->bind_result($model_id, $model_user, $model, $model_name, $model_screenshot);*/
            
                if($error === false) {
                    echo "<img src='../cre/" . $row['screenshot'] . "' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-border w3-round w3-right'>";
                    echo "<div class='w3-border w3-border-black w3-round'>";
                    echo "<b>Edit " . $row['name'] . "</b><br />";
                    echo "<p>Name:<input type='text' value='" . $row['name'] . "'></p>";
                    echo "<p>Description:<textarea value='" . $row['description'] . "' rows='2' cols='80'>" . $row['description'] . "</textarea></p>";
                    echo "<p><input type='submit' class='w3-btn' value='Please try again later' disabled></p>";
                    echo "</div>";
                } else {
                    echo "<b>Failed to load creation due to errors.</b>";
                }
            
            ?>

    </div></table></center><hr /><br /><br />
		
    <?php include('../linkbar.php') ?>
</body>
</html>