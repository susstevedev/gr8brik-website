<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';

if (!loggedin()) {
    header('Location: http://www.youtube.com/watch?v=2dZy3cd9KFY');
}

if ((int)$current_user->admin != 1) {
    header('Location: http://www.youtube.com/watch?v=2dZy3cd9KFY');
}

if (isset($_POST['accept'])) {
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
    if ($conn->connect_error) {
        exit($conn->connect_error);
    }

    $connuser = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if ($connuser->connect_error) {
        exit($connuser->connect_error);
    }

    $pid = $_POST['id'];

    $stmt = $conn->prepare("SELECT * FROM reports WHERE id = ?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 0) {
        $row = $result->fetch_assoc();
        $content_id = $row['reportable_id'];
        $content_type = $row['reportable_type'];

        if ($content_type === 'creation') {
            $stmt = $conn->prepare("UPDATE model SET removed = 0 WHERE id = ?");
            $stmt->bind_param("i", $content_id);

            if ($stmt->execute()) {
                $stmt_del = $conn->prepare("DELETE FROM reports WHERE reportable_id = ?");
                $stmt_del->bind_param("i", $pid);
                $stmt_del->execute();

                exit('Removed creation successfully');
            } else {
                exit('Couldn\'t remove creation.');
            }
        } else if ($content_type === 'comment') {
            $stmt = $conn->prepare("DELETE from comments WHERE id = ?");
            $stmt->bind_param("i", $content_id);

            if ($stmt->execute()) {
                $stmt_del = $conn->prepare("DELETE FROM reports WHERE reportable_id = ?");
                $stmt_del->bind_param("i", $pid);
                $stmt_del->execute();

                exit('Removed comment successfully');
            } else {
                exit('Couldn\'t remove comment.');
            }
        } else if ($content_type === 'profile') {
            $stmt = $connuser->prepare("UPDATE users SET deactive = 1, verify_token = 1 WHERE id = ?");
            $stmt->bind_param("i", $content_id);

            if ($stmt->execute()) {
                $stmt_del = $conn->prepare("DELETE FROM reports WHERE reportable_id = ?");
                $stmt_del->bind_param("i", $pid);
                $stmt_del->execute();

                exit('Removed profile successfully');
            } else {
                exit('Couldn\'t remove comment.');
            }
        } else {
            exit('Invalid type for reported content.');
        }
    } else {
        exit('Invalid reported content ID');
    }
}

if (isset($_POST['deny'])) {
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
    if ($conn->connect_error) {
        exit($conn->connect_error);
    }

    $pid = $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM reports WHERE reportable_id = ?");
    $stmt->bind_param("i", $pid);
    $result = $stmt->execute();

    if ($result) {
        header('Location: reported.php');
        exit;
    } else {
        echo $conn->error;
        exit;
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <title>Reported content</title>
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

        $empty = "<center><b>No content reported. You're all caught up!</b><br />";

        $sql = "SELECT * FROM reports";
        $result = $conn->query($sql);
        if ($result->num_rows !== 0) {
            while ($row = $result->fetch_assoc()) {
                $reported_id = $row['reportable_id'];
                $reported_type = $row['reportable_type'];
                $uid = $row['reporter_user_id'];

                $usero = User::getUser($uid);
                $reporter_username = $usero->username ?: '';

                if ($reported_type === 'creation') {
                    $stmt = $conn->prepare("SELECT name, user, date FROM model WHERE id = ? AND removed = 0");
                    $stmt->bind_param("i", $reported_id);
                    $stmt->execute();

                    $c_result = $stmt->get_result();

                    if ($c_result->num_rows !== 0) {
                        while ($c_row = $c_result->fetch_assoc()) {
                            $reported_name = $c_row['name'];
                            $reported_link = '/build/' . $reported_id;
                            $reported_user = User::getUser($c_row['user'])->username ?: '';
                            $reported_date = date("F j, Y, g:i a", strtotime($c_row['date']));
                        }
                    } else {
                        continue;
                    }
                } else if ($reported_type === 'comment') {
                    $stmt = $conn->prepare("SELECT comment, model, user, date FROM comments WHERE id = ?");
                    $stmt->bind_param("i", $reported_id);
                    $stmt->execute();

                    $c_result = $stmt->get_result();

                    if ($c_result->num_rows !== 0) {
                        while ($c_row = $c_result->fetch_assoc()) {
                            $reported_name = $c_row['comment'];
                            $reported_link = '/build/' . $c_row['model'] . '#comment' . $reported_id;
                            $reported_user = User::getUser($c_row['user'])->username ?: '';
                            $reported_date = date("F j, Y, g:i a", $c_row['date']);
                        }
                    } else {
                        continue;
                    }
                } else if ($reported_type === 'profile') {
                    $usero = User::getUser($reported_id);

                    if (!User::isDeleted($reported_id)) {
                        $reported_name = 'User profile';
                        $reported_link = '/user/' . $reported_id;
                        $reported_user = $usero->username ?: '';
                        $reported_date = date("F j, Y, g:i a", strtotime($usero->age));
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }
        ?>
                <article class='w3-card-2 gr8-theme w3-light-grey w3-padding-small w3-round'>
                    <header>
                        <h3><a href='/user/<?php echo $uid ?>'><?php echo $reporter_username ?></a> reported a <?php echo $reported_type ?></h3>
                    </header>
                    <div id="content">
                        <h4>
                            <i><a href="<?php echo $reported_link ?>"><?php echo $reported_name ?></i></a>
                            <a href="/@<?php echo $reported_user ?>">by <?php echo $reported_user ?></a>
                            <span>on <?php echo $reported_date ?></span>
                        </h4>
                    </div>
                    <h4>Reason: <?php echo $row['reason'] ?></h4>
                    <h4><?php echo $row['description'] ?: '<i>no description</i>' ?></h4>
                    <form method='post' action=''>
                        <input type='hidden' value='<?php echo $row['id'] ?>' name='id'>
                        <input type='submit' value='Keep <?php echo $reported_type ?>' name='deny' class='w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink'>
                        <input type='submit' value='Remove <?php echo $reported_type ?>' name='accept' class='w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo'>
                    </form>
                </article><br />
        <?php
            }
        } else {
            echo $empty;
        }
        $conn->close();
        ?>
    </div>


    <?php include '../linkbar.php' ?>
</body>

</html>