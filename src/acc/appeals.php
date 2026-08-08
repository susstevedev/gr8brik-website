<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';

$frame = '<center><p>sure easter egg why not</p><iframe width="640px" height="480px" src="https://www.youtube-nocookie.com/embed/2dZy3cd9KFY" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></center>';

if(loggedin()) {
    if(!$current_user->admin){
        echo $frame;
        exit;
    }
} else {
    echo $frame;
    exit;
}

if(isset($_POST['deny']) && $current_user->admin) {
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

if(isset($_POST['accept']) && $current_user->admin) {
	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
	    exit($conn->connect_error);
    }

    $user = $conn->real_escape_string($_GET['user']);

	$sql = "DELETE FROM bans WHERE user = '$user' LIMIT 1"; 
    $result = $conn->query($sql);

    if($result) {
	    $sql2 = "DELETE FROM appeals WHERE user = '$user' LIMIT 1"; 
        $result2 = $conn->query($sql2);
    } else {
        $result2 = false;
    }

    if ($result && $result2) {
        exit("Unbanned user");
    } else {
        exit($conn->error ?? 'Error');
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

            if(!$current_user->admin) {
                "<h2>Invalid permissions</h2>";
            }

            if($query->num_rows != 0 && $current_user->admin) {
                while ($query->fetch()) {
                    $query2 = $conn->prepare("SELECT username, picture FROM users WHERE id = ?");
                    $query2->bind_param("i", $user);
                    $query2->execute();
                    $result = $query2->get_result();
                    $row = $result->fetch_assoc();

                    $username = htmlspecialchars($row['username'] ?? '[]');
                    $query2->free_result();

                    echo "<article class='w3-card-2 gr8-theme w3-light-grey w3-padding-small'>";
                    echo "<header><img src='" . $row['picture'] . "' id='pfp' style='border-radius: 50%;'><br />";
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
                echo "<b>No ban appeals. You're all caught up!</b><br />";
            }
            $conn->close();
            echo "</center>";

            include '../linkbar.php' 
        ?>
</body>
</html>