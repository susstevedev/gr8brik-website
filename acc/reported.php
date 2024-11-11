<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';


if(!isset($_SESSION['username'])){

    header('Location: login.php');

} else {

    if($admin != 1){

        header('Location: index.php');

    }

}

if(isset($_POST['deny'])) {
    define('DB_NAME2', 'if0_36019408_creations');
	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
    if ($conn->connect_error) {
	    exit($conn->connect_error);
    }

    $id = $_GET['id'];

	$sql = "DELETE FROM model WHERE id = $id"; 
    $result = $conn->query($sql);

    if ($result) {
        header('Location: reported.php');
        exit;
    } else {
        echo $conn->error;
        exit;
    }
}

if(isset($_POST['accept'])) {
    define('DB_NAME2', 'if0_36019408_creations');
	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
    if ($conn->connect_error) {
	    exit($conn->connect_error);
    }

    $id = $_GET['id'];

	$sql = "DELETE FROM reported WHERE build = $id"; 
    $result = $conn->query($sql);

    if ($result) {
        header('Location: reported.php');
        exit;
    } else {
        echo $conn->error;
        exit;
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Reported | GR8BRIK</title>
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no"><!-- ios support -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/img/logo.jpg" />
    <meta name="apple-mobile-web-app-status-bar" content="#f1f1f1" />
    <meta name="theme-color" content="#f1f1f1" />
</head>
<body class="w3-light-blue w3-container">

    <?php 
        include('../navbar.php');
        include('panel.php');
    ?>
        <h1 style="color:#ff0000">!! WARNING !!</h1>

        <h3>This is a beta test. This should be finished by my Birthday though :) (oct 4)</h3><hr />

        <table class="w3-table-all" style="color:black;">
        <?php
            define('DB_NAME2', 'if0_36019408_creations');
            $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
            if ($conn->connect_error) {
                exit($conn->connect_error);
            }

            $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
            if ($conn2->connect_error) {
                exit($conn2->connect_error);
            }

            $sql = "SELECT * FROM reported ORDER BY date DESC";
            $result = $conn->query($sql);

            while ($row = $result->fetch_assoc()) {
                $build = $row['build'];

                $sql = "SELECT * FROM model WHERE id = $build ORDER BY date DESC";
                $result2 = $conn->query($sql);
                if ($result2->num_rows > 0) {
                    $row2 = $result2->fetch_assoc();

                    $user = $row2['user'];

                    $sql = "SELECT * FROM users WHERE id = $user";
                    $result3 = $conn2->query($sql);
                    $user = $result3->fetch_assoc();

                    echo "<div class='w3-display-container w3-left w3-padding-small'>";
                    echo "<form method='post' action='reported.php?id=" . $row2['id'] . "'>";
                    echo "<input type='submit' value='DELETE MODEL' name='deny' class='w3-btn w3-large w3-white w3-hover-red w3-mobile w3-border'><br />";
                    echo "<input type='submit' value='FALSE REPORT' name='accept' class='w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border'><br />";

                    echo "</form><a href='../build/" . $row2['id'] . "'><img src='../cre/" . $row2['screenshot'] . "' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-border'></a>";
                    echo "<h4 class='w3-display-middle w3-text-grey'>" . $row2['name'] . "</h4>";
                    echo "<b class='w3-display-bottommiddle w3-text-grey'>By @<a href='../user/" . $user['username'] . "'>" . $user['username'] . "</a></b></div>";
                }
            }
        ?>
		
	</table></div>
		
    <?php include '../linkbar.php' ?>
</body>
</html>