<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/notifications.php';

if (!loggedin()) {
    header('Location:login.php');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <title>Notifications</title>
    <?php include '../header.php' ?>
</head>

<body class="w3-light-blue w3-container">
    <?php
        include '../navbar.php';
        include 'panel.php';

        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $id = $current_user->id ?? 0;

        try {
            $notifications = new Notifications($conn);
            $sliced_notifications = $notifications->get_notifications($id, $page);

            $limit = 8;
            $offset = ($page - 1) * $limit;
            $total_groups = $sliced_notifications['count'];

            if ($page > 1) {
                echo '<a class="w3-btn w3-blue w3-hover-opacity w3-round w3-border w3-border-indigo" href="?page=' . $page - 1 . '">Back</a>&nbsp;&nbsp;';
            }

            if (($offset + $limit) < $total_groups) {
                echo '<a class="w3-btn w3-blue w3-hover-opacity w3-round w3-border w3-border-indigo" href="?page=' . $page + 1 . '">Next</a>';
            }
            echo '<hr />';
            echo $notifications->toHTML($sliced_notifications);
        $conn->close();
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo "<p>Error loading some notifications.</p>";
        }
        ?><br /><br />
    <?php include '../linkbar.php' ?>
</body>

</html>