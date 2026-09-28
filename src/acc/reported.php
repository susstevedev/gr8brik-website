<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';

if (!loggedin() || !isset($current_user)) {
    header('Location: http://www.youtube.com/watch?v=2dZy3cd9KFY');
    exit;
}

if ((int)$current_user->admin != 1) {
    header('Location: http://www.youtube.com/watch?v=2dZy3cd9KFY');
    exit;
}

if (isset($_POST['accept'])) {
    $conn = Database::get(DB_NAME2);
    $connuser = Database::get(DB_NAME);
    $pid = $_POST['id'];

    $stmt = $conn->prepare("SELECT * FROM reports WHERE id = ?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 0) {
        $row = $result->fetch_assoc();
        $content_id = $row['reportable_id'];
        $content_type = $row['reportable_type'];
        $destruct = isset($_POST['permadelete']) ? $_POST['permadelete'] : 0;

        switch ($content_type):
            case 'creation':
                $check_sql = "SELECT 1 FROM model WHERE id = ? LIMIT 1";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("i", $content_id);
                $check_stmt->execute();
                $check_res = $check_stmt->get_result();

                if ($check_res->num_rows === 0) {
                    exit("Model not found");
                }

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
            case 'comment':
                $check_sql = "SELECT 1 FROM comments WHERE id = ? LIMIT 1";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("i", $content_id);
                $check_stmt->execute();
                $check_res = $check_stmt->get_result();

                if ($check_res->num_rows === 0) {
                    exit("Comment not found");
                }

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
            case 'profile':
                $check_sql = "SELECT 1 FROM users WHERE id = ? LIMIT 1";
                $check_stmt = $connuser->prepare($check_sql);
                $check_stmt->bind_param("i", $content_id);
                $check_stmt->execute();
                $check_res = $check_stmt->get_result();

                if ($check_res->num_rows === 0) {
                    exit("User not found");
                }

                $stmt = $connuser->prepare("UPDATE users SET deactive = '9999-12-31' WHERE id = ?");
                $stmt->bind_param("i", $content_id);

                if ($stmt->execute()) {
                    if($destruct) {
                        delete_inactive_users($content_id, true, true);
                    }

                    $stmt_del = $conn->prepare("DELETE FROM reports WHERE reportable_id = ?");
                    $stmt_del->bind_param("i", $pid);
                    $stmt_del->execute();

                    exit('Removed profile successfully');
                } else {
                    exit('Couldn\'t remove comment.');
                }
            case 'direct_message':
                $messageid = $content_id;

                $check_sql = "SELECT groupid FROM direct_message WHERE id = ? LIMIT 1";
                $check_stmt = $connuser->prepare($check_sql);
                $check_stmt->bind_param("i", $messageid);
                $check_stmt->execute();
                $check_res = $check_stmt->get_result();

                if ($check_res->num_rows === 0) {
                    exit("Message not found");
                }

                $groupid = $check_res->fetch_assoc()['groupid'] ?? null;

                if(!$groupid) {
                    exit('Groupid not set');
                }

                $delete_sql = "DELETE t1, t2, t3
                    FROM message_group t1
                    LEFT JOIN message_users t2 ON t1.id = t2.groupid
                    LEFT JOIN direct_message t3 ON t1.id = t3.groupid
                    WHERE t1.id = ?";
                $delete_stmt = $connuser->prepare($delete_sql);
                $delete_stmt->bind_param("i", $groupid);
                if ($delete_stmt->execute()) {
                    $stmt_del = $conn->prepare("DELETE FROM reports WHERE reportable_id = ?");
                    $stmt_del->bind_param("i", $pid);
                    $stmt_del->execute();

                    exit('Removed message and group successfully');
                } else {
                    exit('Couldn\'t remove message and group.');
                }
            default:
                exit('Invalid reported content saved in database');
            endswitch;
    } else {
        exit('Invalid reported content ID');
    }
}

if (isset($_POST['deny'])) {
    $conn = Database::get(DB_NAME2);
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
        //todo: make this a switch case
        //nvm spoke too soon
        $conn = Database::get(DB_NAME2);
        $conn2 = Database::get(DB_NAME);
        $empty = "<center><b>No content reported. You're all caught up!</b><br />";

        $sql = "SELECT * FROM reports ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows !== 0) {
            while ($row = $result->fetch_assoc()) {
                $group = [];
                $group['reported_id'] = $row['reportable_id'];
                $group['reported_type'] = $row['reportable_type'];
                $uid = $row['reporter_user_id'];
                $valid = false;

                $usero = User::getUser($uid);
                $group['reporter_username'] = $usero->username ?: '';

                switch ($group['reported_type']):
                    case 'creation':
                        $stmt = $conn->prepare("SELECT name, user, date FROM model WHERE id = ?");
                        $stmt->bind_param("i", $group['reported_id']);
                        $stmt->execute();

                        $c_result = $stmt->get_result();

                        if ($c_result->num_rows !== 0) {
                            while ($c_row = $c_result->fetch_assoc()) {
                                $valid = true;
                                $group['reported_type_string'] = "Creation";
                                $group['reported_name'] = $c_row['name'];
                                $group['reported_link'] = '/build/' . $group['reported_id'];
                                $group['reported_user'] = User::getUser($c_row['user'])->username ?: '';
                                $group['reported_date'] = date("F j, Y, g:i a", strtotime($c_row['date']));
                            }
                        } else {
                            break;
                        }
                        break;
                    case 'comment':
                        $stmt = $conn->prepare("SELECT comment, model, user, date FROM comments WHERE id = ?");
                        $stmt->bind_param("i", $group['reported_id']);
                        $stmt->execute();

                        $c_result = $stmt->get_result();

                        if ($c_result->num_rows !== 0) {
                            while ($c_row = $c_result->fetch_assoc()) {
                                $valid = true;
                                $group['reported_type_string'] = "Creation comment";
                                $group['reported_name'] = $c_row['comment'];
                                $group['reported_link'] = '/build/' . $c_row['model'] . '#comment' . $group['reported_id'];
                                $group['reported_user'] = User::getUser($c_row['user'])->username ?: '';
                                $group['reported_date'] = date("F j, Y, g:i a", $c_row['date']);
                            }
                        } else {
                            break;
                        }
                        break;
                    case 'profile':
                        $stmt = $conn2->prepare("SELECT username, age FROM users WHERE id = ?");
                        $stmt->bind_param("i", $group['reported_id']);
                        $stmt->execute();

                        $c_result = $stmt->get_result();

                        if ($c_result->num_rows !== 0) {
                            while ($c_row = $c_result->fetch_assoc()) {
                                $valid = true;
                                $group['reported_type_string'] = "User profile";
                                $group['reported_name'] = $c_row['username'] ?: '';
                                $group['reported_link'] = '/user/' . $group['reported_id'];
                                $group['reported_user'] = $c_row['username'] ?: '';
                                $group['reported_date'] = date("F j, Y, g:i a", strtotime($c_row['age']));
                            }
                        } else {
                            break;
                        }
                        break;
                    case 'direct_message':
                        $stmt = $conn2->prepare("SELECT * FROM direct_message WHERE id = ?");
                        $stmt->bind_param("i", $group['reported_id']);
                        $stmt->execute();

                        $c_result = $stmt->get_result();

                        if ($c_result->num_rows !== 0) {
                            while ($c_row = $c_result->fetch_assoc()) {
                                $valid = true;
                                $group['reported_type_string'] = "Direct Message";
                                $group['reported_name'] = $c_row['message'];
                                $group['reported_link'] = '/acc/messages?m=' . $c_row['groupid'] . '#g' . $c_row['id'];
                                $group['reported_user'] = User::getUser($c_row['userid'])->username ?: '';
                                $group['reported_date'] = date("F j, Y, g:i a", $c_row['timestamp']);
                            }
                        } else {
                            break;
                        }
                        break;
                    default:
                        break;
                    endswitch;

                    if($valid === true) {
            ?>
                <article class='w3-card-2 gr8-theme w3-light-grey w3-padding-small w3-round'>
                    <header>
                        <h3><a href='/user/<?php echo $uid ?>'><?php echo $group['reporter_username'] ?></a> reported a <?php echo $group['reported_type_string'] ?? $group['reported_type'] ?></h3>
                    </header>
                    <div id="content">
                        <h4>
                            <a href="/@<?php echo $group['reported_user'] ?>"><?php echo $group['reported_user'] ?></a> created the <?php echo $group['reported_type_string'] ?? $group['reported_type'] ?>
                            <i><a href="<?php echo $group['reported_link'] ?>"><?php echo $group['reported_name'] ?></i></a>
                            <span>on <?php echo $group['reported_date'] ?></span>
                        </h4>
                    </div>
                    <h4>Reason: <?php echo $row['reason'] ?></h4>
                    <h4>Description:</h4>
                    <p><?php echo $row['description'] ?: '<i>no description</i>' ?></p>
                    <form method='post' action=''>
                        <input type='hidden' value='<?php echo $row['id'] ?>' name='id'>

                        <?php if($group['reported_type'] === 'profile') { ?>
                            <input type="checkbox" class="w3-check" id="permadelete" name="permadelete" value="1">
                            <label for="permadelete">Perma delete account (will also blacklist the email)</label><br />
                        <?php } ?>

                        <input type='submit' value='Keep <?php echo $group['reported_type_string'] ?>' name='deny' class='w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink'>
                        <input type='submit' value='Remove <?php echo $group['reported_type_string'] ?>' name='accept' class='w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo'>
                    </form>
                </article><br />
            <?php
                }
            }
        } else {
            echo $empty;
        }
        ?>
    </div>

    <?php include '../linkbar.php' ?>
</body>

</html>