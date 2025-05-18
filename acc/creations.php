<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
if(loggedin() != true) {
    header('Location:login.php');
}
$conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
if ($conn2->connect_error) {
    exit($conn->connect_error);
}
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

    <div class="message w3-padding w3-round w3-red">You cannot edit a model after it is published.</div>

	<center>
        <div class="w3-row">
            <table class="w3-table-all" style="color:black;">
            <?php
                $stmt = $conn2->prepare('SELECT * FROM model WHERE user = ?');
                $stmt->bind_param('i', $token['user']);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $model_id = $row['id'];

                    $stmt = $conn2->prepare('SELECT COUNT(*) as count FROM votes WHERE creation = ?');
                    $stmt->bind_param('i', $model_id);
                    $stmt->execute();
                    $vote_count = $stmt->get_result();
                    $likes = $vote_count->fetch_assoc();

                    if (empty($row['name'])) {
                        $row['name'] = htmlspecialchars($users_row['username']) . "'s creation";
                    }

                    $name = htmlspecialchars(substr($row['name'], 0, 30));
                    if (strlen($row['name']) >= 30) {
                        $name .= '...';
                    }

                    echo "<div class='w3-display-container w3-left w3-padding'>";
                    echo "<a href='/build/" . $row['id'] . "'><img src='/cre/" . $row['screenshot'] . "' width='320' height='240' loading='lazy' class='gr8-theme w3-card-2 w3-hover-shadow w3-border w3-border-white'></a>";
                    echo "<span class='gr8-theme w3-large w3-display-middle w3-card-2 w3-light-grey w3-padding-small'>" . $name . '</span>';
                    echo "<span class='gr8-theme w3-display-bottommiddle w3-card-2 w3-light-grey w3-padding-small'>" . $row['views'] . ' views - ' . $likes['count'] . " likes - ";
                    echo "By <a href='/user/" . $token['user'] . "'>" . $users_row['username'] . "</a></span></div>";
                }    
            ?>
            </table>
        </div>
    </center><br /><br />
    <?php include('../linkbar.php') ?>
</body>
</html>