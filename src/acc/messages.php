<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/bbcode.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/notifications.php';
$bbcode = new BBCode;

if (loggedin()) {
    $id = $current_user->id;
} else {
    header('Location: login.php');
    exit;
}

$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    exit($conn->connect_error);
}

if (isset($_GET['group'])) {
    /*$sql = "SELECT 
                mg.id AS group_id,
                u.id AS other_user_id,
                u.username,
                u.picture,
                u.age
            FROM message_group mg
            JOIN message_users gm1 ON mg.id = gm1.groupid AND gm1.userid = ?
            JOIN message_users gm2 ON mg.id = gm2.groupid AND gm2.userid != ?
            JOIN users u ON gm2.userid = u.id
            WHERE u.deactive IS NULL
            ORDER BY mg.id DESC";*/

    $sql = "SELECT 
            mg.id AS group_id,
            mg.is_group,
            mg.group_name,
            u.username,
            u.picture,
            u.age,
            (SELECT GROUP_CONCAT(users.username SEPARATOR ', ')
            FROM message_users
            JOIN users ON message_users.userid = users.id
            WHERE message_users.groupid = mg.id AND message_users.userid != ?) AS members,
            (SELECT GROUP_CONCAT(users.age SEPARATOR ', ')
            FROM message_users
            JOIN users ON message_users.userid = users.id
            WHERE message_users.groupid = mg.id AND message_users.userid != ?) AS members_join
        FROM message_group mg
        JOIN message_users gm1 ON mg.id = gm1.groupid AND gm1.userid = ?
        LEFT JOIN message_users gm2 ON mg.id = gm2.groupid AND mg.is_group = 0 AND gm2.userid != ?
        LEFT JOIN users u ON gm2.userid = u.id AND u.deactive IS NULL
        ORDER BY mg.id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $id, $id, $id, $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'No messages to display. Try sending one!']);
        exit;
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['is_group'] == 1) {
            $title = $row['group_name'] ?? $row['members'];
            $group_picture = $row['picture'];
            $group_joined = $row['members_join'];
        } else {
            $title = $row['username'] ?? 'Deleted User';
            $group_picture = !empty($row['picture']) ? $row['picture'] : '/img/no_image.png';
            $group_joined = isset($row['age']) ? 'Joined ' . time_ago($row['age']) : null;
        }

        $data[] = [
            'success' => true,
            'user' => htmlspecialchars($title),
            'pictureurl' => $group_picture ?? null,
            'id' => $row['group_id'],
            'joined' => $group_joined ?? null,
        ];
    }

    header("Content-Type: application/json");
    echo json_encode($data);
    exit;
}

if ((isset($_GET['message']))) {
    $message = $_GET['message'];
    $uid = $current_user->id;

    $sql = "SELECT 
        u.id AS sender_id,
        u.username AS sender_username,
        u.picture AS sender_picture,
        u.age AS sender_age,
        mg.groupid,
        mg.message,
        mg.timestamp,
        mg.id AS message_id
        FROM direct_message mg
        JOIN users u ON mg.userid = u.id
        WHERE mg.groupid = ?
        AND EXISTS (
            SELECT 1 FROM message_users
            WHERE groupid = mg.groupid AND userid = ?
        )
        ORDER BY mg.timestamp DESC;
        ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $message, $id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $id = $row['message_id'];
        $group = $row['groupid'];
        $userid = $row['sender_id'];
        $username = $row['sender_username'] ?? 'Deleted User';
        $post = $bbcode->toHTML($row['message'], true, true);
        $timestamp = $row['timestamp'];
        $message = htmlentities($message, ENT_QUOTES, 'UTF-8');

        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'No messages to display. Try sending one!']);
            exit;
        }

        //Simple logic to give messages different colors
        if ((int)$uid !== (int)$userid) {
            $p_color = "#90EE90";
        } else {
            $p_color = "#ADD8E6";
        }

        header("HTTP/1.0 200 OK");
        $data[] = [
            'success' => true,
            'id' => $id,
            'groupid' => $group,
            'user' => '@' . htmlspecialchars($username),
            'message' => $post,
            'color' => $p_color,
            'timestamp' => time_ago(date('Y-m-d H:i:s', $timestamp)),
        ];
    }

    echo json_encode($data);
    exit;
}

if (isset($_POST['comment'])) {
    header('Content-type: application/json');
    $comment = $conn->real_escape_string(htmlspecialchars($_POST['commentbox']));
    $id = $current_user->id;

    $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if ($conn2->connect_error) {
        exit($conn2->connect_error);
    }

    if ($comment === "" || $comment === null || empty($comment)) {
        echo json_encode(['success' => false, 'error' => "Message shall contain text."]);
        exit;
    }

    $groupid = (int)$_POST['groupid'];

    $sql = "SELECT id FROM message_group WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $groupid);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 0){
        echo json_encode(['success' => false, 'error' => "No group with this Id exists. It may have been deleted by a platform admin."]);
        exit;
    }

    $groupid = $result->fetch_assoc()['id'] ?? $groupid;
    $date = time();
    $sql = "INSERT INTO direct_message (userid, groupid, message, timestamp) VALUES (?, ?, ?, ?)";
    $stmt2 = $conn2->prepare($sql);
    $stmt2->bind_param("iisi", $id, $groupid, $comment, $date);

    if (!$stmt2->execute()) {
        echo json_encode(['success' => false, 'error' => "An error has occured. Please try again later."]);
        exit;
    } else {
        $notifications = new Notifications($conn2);

        if(!$notifications->is_subscriber('direct_message', $groupid, $id)) {
            $notifications->subscribe($id, 'direct_message', $groupid);
        }

	    $notifications->notify_subscribers('direct_message', $groupid, $id);
        $stmt2->close();
        echo json_encode(['success' => true]);
        exit;
    }
}

if (isset($_POST['group_create'])) {
    header('Content-type: application/json');
    $comment = trim($_POST['commentbox'] ?? '');

    if ($comment === "") {
        echo json_encode(['success' => false, 'error' => "Empty user field"]);
        exit;
    }

    if (strcasecmp($comment, $current_user->username) === 0) {
        echo json_encode(['success' => false, 'error' => "You cannot message yourself"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, username FROM users WHERE username = ? AND deactive IS NULL LIMIT 1");
    $stmt->bind_param("s", $comment);
    $stmt->execute();
    $user_result = $stmt->get_result();

    if ($user_result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => "User not found"]);
        exit;
    }

    $target_user = $user_result->fetch_assoc();
    $profileid = (int)$target_user['id'];
    $target_username = $target_user['username'];

    $block_stmt = $conn->prepare("SELECT userid, profileid FROM user_blocks WHERE (userid = ? AND profileid = ?) OR (userid = ? AND profileid = ?)");
    $block_stmt->bind_param("iiii", $profileid, $id, $id, $profileid);
    $block_stmt->execute();
    $block_result = $block_stmt->get_result();

    if ($block_result->num_rows > 0) {
        $youBlocked = false;
        $theyBlocked = false;

        while ($row_block = $block_result->fetch_assoc()) {
            if ((int)$row_block['userid'] === $id) $youBlocked = true;
            if ((int)$row_block['userid'] === $profileid) $theyBlocked = true;
        }

        if ($youBlocked && $theyBlocked) {
            $msg = "You blocked {$target_username}, and they blocked you. You cannot message each other.";
        } elseif ($youBlocked) {
            $msg = "You blocked {$target_username}. They cannot message you.";
        } else {
            $msg = "You're blocked from {$target_username}. You cannot message them.";
        }

        echo json_encode(['success' => false, 'error' => $msg]);
        exit;
    }

    $check_stmt = $conn->prepare("
        SELECT gm1.groupid
        FROM message_users gm1
        JOIN message_users gm2 ON gm1.groupid = gm2.groupid
        WHERE gm1.userid = ? AND gm2.userid = ?
        GROUP BY gm1.groupid
    ");
    $check_stmt->bind_param("ii", $id, $profileid);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $group = $check_result->fetch_assoc();
        echo json_encode(['success' => true, 'groupid' => $group['groupid']]);
        exit;
    }
    $conn->begin_transaction();

    try {
        $create_group_stmt = $conn->prepare("INSERT INTO message_group (timestamp) VALUES (NOW())");
        $create_group_stmt->execute();
        $groupid = $conn->insert_id;

        $insert_member_stmt = $conn->prepare("INSERT INTO message_users (groupid, userid) VALUES (?, ?), (?, ?)");
        $insert_member_stmt->bind_param("iiii", $groupid, $id, $groupid, $profileid);
        $insert_member_stmt->execute();

        $conn->commit();

        $notifications = new Notifications($conn);
        $notifications->subscribe($id, 'direct_message', $groupid);
        $notifications->subscribe($profileid, 'direct_message', $groupid);

        echo json_encode(['success' => true, 'groupid' => $groupid]);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => "An error occurred. Please try again later.", 'details' => htmlspecialchars($e)]);
        exit;
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <title>Direct Messages</title>
    <?php include '../header.php' ?>
    <script src="/lib/direct_messages.js" type="text/javascript"></script>
</head>

<body class="w3-light-blue w3-container">
    <?php
        include '../navbar.php';
        include 'panel.php';
    ?>

    <div class="w3-row">
        <div class="w3-third" id="groups" style="display:none">
            <div id="group-form">
                <div id="post">
                    <input name="direct-box" id="direct-box" class="w3-input w3-hover-light-grey" placeholder="Username" rows="auto" cols="40" />
                </div>
                <button id="group_create" onclick="create_group();" class="w3-btn w3-white w3-hover-light-grey w3-padding-small w3-border w3-border-grey">
                    <span>Message user</span>
                </button>
            </div><br />
        </div>

        <div class="w3-rest w3-padding" id="messages"></div>
    </div><br />
    <hr />

    <?php include '../linkbar.php' ?>
</body>

</html>