<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
if(!loggedin()) {
    header('Location:../index.php');
    exit;
}
$model_id = $_GET['id'];
$user_id = $token['user'];

if (isset($_POST['edit'])) {
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
    if ($conn->connect_error) {
        exit($conn->connect_error);
    }

    $name = htmlspecialchars($_POST['name']);
    $about = htmlspecialchars($_POST['about']);

    $stmt = $conn->prepare("UPDATE model SET name = ?, description = ? WHERE id = ? AND user = ?");
    $stmt->bind_param("ssii", $name, $about, $model_id, $user_id);
    $result = $stmt->execute();

    if ($result) {
        header('Location: ../creation.php?id=' . $model_id);
        exit;
    } else {
        echo $conn->error;
        exit;
    }
    $stmt->close();
}
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
                
                $sql = "SELECT * FROM model WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $model_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $error = false;

                if($result->num_rows === 0) {
                    echo "<h4>Creation not found.</h4>";
                    $error = true;
                }

                if(isset($row['user']) && $row['user'] != $user_id) {
                    echo "<h4>You cannot edit a creation that you did not create.</h4>";
                    $error = true;
                }
            
                if($error === false) {
                    ?>
                    <form method='post' action=''>
                    <img src='<?php echo $row['screenshot'] ?>' width='250' loading='lazy' class='w3-hover-shadow w3-border w3-round w3-right'>
                    <div class='w3-border w3-border-black w3-padding-large w3-round'>
                    <b>Edit <?php echo $row['name'] ?: $row['model'] ?></b><br />
                    <p>Name:<textarea name='name' value='<?php echo $row['name'] ?: 'Title' ?>' rows='1' cols='80'><?php echo $row['name'] ?: 'Title' ?></textarea></p>
                    <p>Description:<textarea name='about' value='<?php echo $row['description'] ?: 'Description' ?>' rows='2' cols='80'><?php echo $row['description'] ?: 'Description' ?></textarea></p>
                    <p><input type='submit' name='edit' class='w3-btn w3-blue w3-hover-white w3-quarter w3-border w3-border-indigo' value='Submit'></p>
                   	</div></form>
            		<?php
                } else {
                    echo "<b>Failed to load creation.</b>";
                }
            
            ?>

    </div></table></center><hr /><br /><br />
		
    <?php include('../linkbar.php') ?>
</body>
</html>