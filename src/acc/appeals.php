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
    $value = $conn->real_escape_string($_GET['value']);

	$sql = "DELETE FROM blacklist WHERE (value = '$value' AND type = 'username') OR (value = '$value' AND type = 'email') LIMIT 1"; 
    $result_unblacklist = $conn->query($sql);

    $sql = "UPDATE users SET deactive = NULL WHERE id = $user"; 
    $result_undel = $conn->query($sql);

    if($result_unblacklist && $result_undel) {
	    $sql2 = "DELETE FROM appeals WHERE user = '$user' LIMIT 1"; 
        $result2 = $conn->query($sql2);
        $finished = true;
    } else {
        $finished = false;
    }

    if ($finished) {
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

            $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

            $query = $conn->prepare("SELECT user, reason FROM appeals");
            $query->execute();
            $query->store_result();
            $query->bind_result($user, $reason);

            if(!$current_user->admin) {
                "<h2>Invalid permissions</h2>";
            }

            if($query->num_rows != 0 && $current_user->admin) {
                while ($query->fetch()) {
                    $query2 = $conn->prepare("SELECT username, email, picture FROM users WHERE id = ?");
                    $query2->bind_param("i", $user);
                    $query2->execute();
                    $result = $query2->get_result();

                    $row = $result->fetch_assoc();
                    $email = hash('sha256', strtolower(trim($row['email'])));
                    $username = $row['username'];

                    $query_blacklist = $conn->prepare("SELECT value, reason FROM blacklist WHERE (value = ? AND type = 'username') OR (value = ? AND type = 'email')");
                    $query_blacklist->bind_param("ss", $username, $email);
                    $query_blacklist->execute();
                    $query_blacklist->store_result();
                    $query_blacklist->bind_result($value, $ban_reason);
                    $query_blacklist->fetch();

                    $username = htmlspecialchars($username ?? '[]');
                    $query2->free_result();
                    ?>

                    <article class='w3-card-2 gr8-theme w3-light-grey w3-padding-small'>
                        <header>
                            <img src='<?php echo $row['picture'] ?>' id='pfp' style='border-radius: 50%;'><br />
                            <h3><a href='/user/<?php echo $user ?>'><?php echo $username ?></a></h3>
                        </header>
                        <h4>Reason user wants to be unbanned:<br /><?php echo $reason ?></h4>
                        <h4>Reason for ban:<br /><?php echo $ban_reason ?></h4>
                        <form method='post' action='appeals.php?value=<?php echo $value ?>&user=<?php echo $user ?>'>
                            <input type='submit' value='Keep user banned' name='deny' class='w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink'>
                            <input type='submit' value='Unban user' name='accept' class='w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo'>
                        </form>
                    </article><br />

                    <?php
                    $query2->close();
                }
                $query->free_result();
                $query->close();
            } else {
                echo "<b>No ban appeals. You're all caught up!</b><br />";
            }
            $conn->close();

            include '../linkbar.php' 
        ?>
</body>
</html>