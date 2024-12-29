<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';

if(!isset($_SESSION['username'])){

    header('Location: login.php');

}

if(isset($_POST['unfollow'])) {
	
    $profileid = $_GET['user'];
	$sql = "DELETE FROM follow WHERE userid = ? AND profileid = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ii", $id, $profileid);
	$stmt->execute();
	$stmt->close();
	$conn->close();
	die;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Gr8brik.rf.gd | Friends</title>
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
</head>
<body class="w3-light-blue w3-container">

    <?php 
        include('../navbar.php');
        require_once '../com/bbcode.php';
        $bbcode = new BBCode;
        include('panel.php');
    ?>

    <div class="w3-container">
        <ul class="w3-ul w3-card-2 w3-light-grey w3-padding-tiny w3-round-small">
            <?php
                $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
                if ($conn->connect_error) {
                    exit($conn->connect_error);
                }

                $sql = "SELECT * FROM follow WHERE userid = $id";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    $users = $result->fetch_all(MYSQLI_ASSOC);
                    $result->free_result();
                
                    foreach ($users as $user) {
                        $userid = $user['profileid'];
                        $sql2 = "SELECT * FROM users WHERE id = $userid";
                        $result2 = $conn->query($sql2);
                        $row = $result2->fetch_assoc();

                        echo "<br /><li class='w3-padding-small'><img id='pfp' class='w3-circle w3-margin-right' src='/acc/users/pfps/" . $row['id'] . ".jpg'>";
                        echo "<b class='w3-xlarge'><a href='/user/" . $row['username'] . "'>" . htmlspecialchars($row['username']) . "</a></b>";
                        echo "<br /><b class='w3-small w3-text-grey'>" . $bbcode->toHTML(htmlspecialchars($row['description'])) . "</b>";
                        echo "<form method='post' action='following.php?user=" . $userid . "'>";
                        echo "<input type='submit' value='UNFOLLOW' name='unfollow' class='w3-btn w3-large w3-white w3-hover-red'></form></li>";
                    }
                } else {
                    $result2->free_result();
                    $result3->free_result();
                    echo "Please check your internet connection";
                }

            ?>
        </ul>	
    </div>
		
    <?php include '../linkbar.php' ?>
</body>
</html>