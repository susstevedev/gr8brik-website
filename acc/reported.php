<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
isLoggedIn();

if (isset($_COOKIE['token'])) {
    if ((int)$users_row['admin'] != 1) {
        header('Location: index.php');
    }
}

if (isset($_POST['deny'])) {
    define('DB_NAME2', 'if0_36019408_creations');
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
    if ($conn->connect_error) {
        exit($conn->connect_error);
    }

    $id = $_GET['id'];

    $stmt = $conn->prepare("UPDATE model SET removed = '1' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();

    if ($result) {
        header('Location: reported.php');
        exit;
    } else {
        echo $conn->error;
        exit;
    }
    $stmt->close();
}

if (isset($_POST['accept'])) {
    define('DB_NAME2', 'if0_36019408_creations');
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
    if ($conn->connect_error) {
        exit($conn->connect_error);
    }

    $id = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM reported WHERE build = ?");
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();

    if ($result) {
        header('Location: reported.php');
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
    <title>Reported</title>
    <?php include '../header.php' ?>
</head>
<body class="w3-light-blue w3-container">

    <?php 
        include('../navbar.php');
        include('panel.php');
    ?>

        <div class="w3-row">
        <?php
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
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $build = $row['build'];

                    $sql = "SELECT * FROM model WHERE id = ? AND removed = '0'";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $build);
                    $stmt->execute();
                    $result2 = $stmt->get_result();
                    if ($result2->num_rows > 0) {
                        $row2 = $result2->fetch_assoc();
                        $userid = $row2['user'];

                        $sql = "SELECT * FROM users WHERE id = ?";
                        $stmt2 = $conn2->prepare($sql);
                        $stmt2->bind_param("i", $userid);
                        $stmt2->execute();
                        $result3 = $stmt2->get_result();
                        $user = $result3->fetch_assoc();
                        $username = isset($user['username']) ? $user['username'] : null;
                        $stmt2->close();

                        $sql = "SELECT COUNT(*) as count FROM votes WHERE creation = ?";
                        $stmt3 = $conn->prepare($sql);
                        $stmt3->bind_param("i", $build);
                        $stmt3->execute();
                        $result4 = $stmt3->get_result();
                        $likes = $result4->fetch_assoc();
                        $stmt3->close();

                        $sql = "SELECT * FROM bans WHERE user = ?";
                        $stmt4 = $conn2->prepare($sql);
                        $stmt4->bind_param("i", $userid);
                        $stmt4->execute();
                        $banResult = $stmt4->get_result();
                        $banRow = $banResult->fetch_assoc();
                        $stmt4->close();

                        if(empty($row2['name'])) {
                            $row2['name'] = $username . "'s creation";
                        }

                        $truncatedName = substr($row2['name'], 0, 20);
                        if (strlen($row2['name']) > 20) {
                            $truncatedName .= "...";
                        }

                        echo "<div class='w3-display-container w3-left w3-padding'>";

                        echo "<h4><i>Reason:</i><header title='Reason for report' id='data-report-reason'>" . $row['reason'] . "</header></h4>";

                        echo "<input form='report' type='submit' value='Remove creation' name='deny' class='w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo'>&nbsp;&nbsp;";
                        echo "<input form='report' type='submit' value='Keep creation' name='accept' class='w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo'>";
                        echo "<form id='report' method='post' action='reported.php?id=" . $build . "'></form>";

                        echo "<a href='/build/" . $build . "'><img src='/cre/" . $row2['screenshot'] . "' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-border w3-border-white'></a>";
                        echo "<span class='gr8-theme w3-large w3-display-middle w3-card-2 w3-light-grey w3-padding-small'>" . $truncatedName . "</span>";
                        echo "<span class='gr8-theme w3-display-bottommiddle w3-card-2 w3-light-grey w3-padding-small'>" . $row2['views'] . " views - " . $likes['count'] . " likes - By ";
                        echo "<a href='/user/" . $row2['user'] . "'>" . $username . "</a></span></div>";
                    }
                }
                $stmt->close();
            } else {
                echo "<center><b>No creations reported. Your all caught up!</b><br />";
                //echo "<img src='/img/empty.png' style='width:518px;height:288px;'></center>";
            }
            $conn->close();
            $conn2->close();
        ?>
        </div>
		
		
    <?php include '../linkbar.php' ?>
</body>
</html>