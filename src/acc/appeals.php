<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';
if(!loggedin()) {
    header('Location: login.php');
}

if(isset($token['user'])) {
    if($users_row['admin'] != '1'){
        header('Location: index.php');
    }
}

if(isset($_POST['deny'])) {
	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
	    exit($conn->connect_error);
    }

    $user = $conn->real_escape_string($_GET['user']);

	$sql = "DELETE FROM appeals WHERE user = '$user' LIMIT 1"; 
    $result = $conn->query($sql);
    if ($result) {
        exit;
    }
}

if(isset($_POST['accept'])) {
	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
	    exit($conn->connect_error);
    }

    $user = $conn->real_escape_string($_GET['user']);

	$sql = "DELETE FROM bans WHERE user = '$user' LIMIT 1"; 
    $result = $conn->query($sql);

	$sql2 = "DELETE FROM appeals WHERE user = '$user' LIMIT 1"; 
    $result2 = $conn->query($sql2);

    if ($result && $result2) {
        exit("Unbanned user");
    } else {
        exit($conn->error);
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Appeals</title>
    <?php include '../header.php' ?>
</head>
<body class="w3-light-blue w3-container">

    <?php 
        include('../navbar.php');
        include('panel.php');

        echo "<center>";
            $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

            $query = $conn->prepare("SELECT user, reason, end_date FROM appeals");
            $query->execute();
            $query->store_result();
            $query->bind_result($user, $reason, $date);

            if($query->num_rows != 0) {
                while ($query->fetch()) {
                    $query2 = $conn->prepare("SELECT username FROM users WHERE id = ?");
                    $query2->bind_param("i", $user);
                    $query2->execute();
                    $result = $query2->get_result();
                    $row = $result->fetch_assoc();

                    $username = htmlspecialchars($row['username']) ?: "Unknown User";
                    $query2->free_result();

                    echo "<article class='w3-card-2 gr8-theme w3-light-grey w3-padding-small'>";
                    echo "<header><img src='/ajax/pfp?method=image&id=" . base64_encode($user) . "' id='pfp' style='border-radius: 50%;'><br />";
                    echo "<h3><a href='/user/" . $user . "'>" . htmlspecialchars($username) . "</a></h3></header>";
                    echo "<h4>" . $reason . "</h4>";
                    echo "<b>Appeal valid until " . date('F j, Y, g:i a', (int)$date) . " (" . time_ago(date('Y-m-d H:i:s', (int)$date)) . ").</b>";
                    echo "<form method='post' action='appeals.php?user=" . $user . "&name=" . $username . "'>";
                    echo "<input type='submit' value='Keep user banned' name='deny' class='w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink'>&nbsp;";
                    echo "<input type='submit' value='Unban user' name='accept' class='w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo'>";
                    echo "</form></article><br />";
                    $query2->close();
                }
                $query->free_result();
                $query->close();
            } else {
                echo "<b>No ban appeals. Your all caught up!</b><br />";
                //echo "<img src='/img/empty.png' style='width:518px;height:288px;'>";
            }
            $conn->close();
            echo "</center>";

            include '../linkbar.php' 
        ?>
</body>
</html>