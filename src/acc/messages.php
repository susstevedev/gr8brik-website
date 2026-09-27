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
    $sql = "SELECT
        mg.id AS group_id,
        mg.is_group,
        mg.group_name,

        (
            SELECT u2.username
            FROM message_users mu2
            JOIN users u2 ON u2.id = mu2.userid
            WHERE mu2.groupid = mg.id
            AND mu2.userid != ?
            LIMIT 1
        ) AS username,

        (
            SELECT u2.picture
            FROM message_users mu2
            JOIN users u2 ON u2.id = mu2.userid
            WHERE mu2.groupid = mg.id
            AND mu2.userid != ?
            LIMIT 1
        ) AS picture,

        (
            SELECT u2.age
            FROM message_users mu2
            JOIN users u2 ON u2.id = mu2.userid
            WHERE mu2.groupid = mg.id
            AND mu2.userid != ?
            LIMIT 1
        ) AS age,

        (
            SELECT GROUP_CONCAT(u2.username SEPARATOR ', ')
            FROM message_users mu2
            JOIN users u2 ON u2.id = mu2.userid
            WHERE mu2.groupid = mg.id
            AND mu2.userid != ?
        ) AS members

    FROM message_group mg

    JOIN message_users me
        ON me.groupid = mg.id
        AND me.userid = ?

    ORDER BY mg.timestamp DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiii", $id, $id, $id, $id, $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'No messages to display. Try sending one!']);
        exit;
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $group = null;

        if ($row['is_group'] == 1) {
            $group['title'] = ($row['group_name'] ?? 'Group ') . '(' . $row['members'] . ')';
        } else {
            $group['title'] = isset($row['username']) ? $row['username'] : 'Deleted User';
            $group['picture'] = isset($row['picture']) ? $row['picture'] : null;
            $group['joined'] = isset($row['age']) ? 'Joined ' . time_ago($row['age']) : null;
        }

        $data[] = [
            'success' => true,
            'id' => $row['group_id'],
            'title' => htmlentities($group['title'], ENT_NOQUOTES),
            'pictureurl' => $group['picture'] ?? null,
            'joined' => $group['joined'] ?? null,
        ];
    }

    header("Content-Type: application/json");
    echo json_encode($data);
    exit;
}

if ((isset($_GET['message']))) {
    $message = isset($_GET['message']) ? (int)$_GET['message'] : null;
    $uid = $current_user->id;

    if(empty($message)) {
        echo json_encode([
            'success' => false,
            'message' => 'Empty message ID'
        ]);
        exit;
    }

    $sql = "SELECT
            g.id AS groupid,
            g.group_name,
            g.is_group,
            me.admin AS group_admin,
            dm.id AS message_id,
            dm.message,
            dm.timestamp,
            u.id AS sender_id,
            u.username AS sender_username,
            u.deactive AS sender_inactive,
            u.picture AS sender_picture,
            u.age AS sender_age,
        (
            SELECT GROUP_CONCAT(u.username SEPARATOR ', ')
            FROM message_users mu
            JOIN users u ON u.id = mu.userid
            WHERE mu.groupid = g.id
            AND mu.userid != ?
        ) AS members
        FROM message_group g
        JOIN message_users me
            ON me.groupid = g.id
           AND me.userid = ?
        LEFT JOIN direct_message dm
            ON dm.groupid = g.id
        LEFT JOIN users u
            ON u.id = dm.userid
        WHERE g.id = ?
        ORDER BY dm.timestamp DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $uid, $uid, $message);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    $rows = [];

    if($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Group not found in database.']);
        exit;
    }

    while ($row = $result->fetch_assoc()) {
        $group = $row['groupid'] ?? null;
        $groupname = $row['group_name'] ?? $row['members'];
        $groupadmin = $row['group_admin'] ? true : false;

        if (!$row['message_id']) {
            continue;
        }
        $rows[] = $row;
    }

    foreach($rows as $row) {
        $message = null;

        $message['id'] = $row['message_id'] ?? null;
        $message['userid'] = $row['sender_id'] ?? null;
        $message['username'] = $row['sender_username'] ?? 'Deleted User';
        $message['post'] = $bbcode->toHTML($row['message'], true, true);
        $message['timestamp'] = $row['timestamp'] ?? 0;

        //Simple logic to give messages different colors
        if ((int)$uid !== (int)$message['userid']) {
            $p_color = "#90EE90";
        } else {
            $p_color = "#ADD8E6";
        }

        if($row['sender_inactive'] === null) {
            $message['url'] = '/@' . rawurlencode($message['username']);
        }

        http_response_code(200);
        $data[] = [
            'id' => $id,
            'user' => htmlentities($message['username']),
            'userID' => $message['userid'],
            'url' => $message['url'] ?? null,
            'message' => $message['post'],
            'color' => $p_color,
            'timestamp' => time_ago(date('Y-m-d H:i:s', $message['timestamp'])),
        ];
    }

    echo json_encode([
        'success' => true,
        'groupid' => $group,
        'name' => $groupname,
        'admin' => $groupadmin ?? false,
        'msgs' => $data
    ]);
    exit;
}

if (isset($_POST['comment'])) {
    header('Content-type: application/json');
    $comment = isset($_POST['commentbox']) ? htmlentities($_POST['commentbox']) : null;
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
        echo json_encode(['success' => false, 'error' => "Group not found in database."]);
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
        $stmt3 = $conn2->prepare("UPDATE message_group SET timestamp = NOW() WHERE id = ?");
        $stmt3->bind_param("i", $groupid);
        $stmt3->execute();

        $notifications = new Notifications($conn2);
        if(!$notifications->is_subscriber('direct_message', $groupid, $id)) {
            $notifications->subscribe($id, 'direct_message', $groupid);
        }

	    $notifications->notify_subscribers('direct_message', $groupid, $id);
        $stmt2->close();
        echo json_encode(['success' => true, 'groupid' => $groupid]);
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

    $usernames = array_filter(array_unique(array_map('trim', explode(',', $comment))));

    foreach ($usernames as $username) {
        if (strcasecmp($username, $current_user->username) === 0) {
            echo json_encode(['success' => false, 'error' => "You cannot message yourself"]);
            exit;
        }
    }

    $placeholders = implode(',', array_fill(0, count($usernames), '?'));
    $types = str_repeat('s', count($usernames));

    $stmt = $conn->prepare("SELECT id, username FROM users WHERE username IN ($placeholders) AND deactive IS NULL AND suspended = 0");
    $stmt->bind_param($types, ...$usernames);
    $stmt->execute();
    $user_result = $stmt->get_result();

    $found_users = [];
    while ($row = $user_result->fetch_assoc()) {
        $found_users[] = $row;
    }

    if (count($found_users) !== count($usernames)) {
        echo json_encode(['success' => false, 'error' => "One or more users were not found"]);
        exit;
    }

    $recipient_ids = array_column($found_users, 'id');
    $all_participant_ids = array_merge([$id], $recipient_ids);
    sort($all_participant_ids);

    $placeholders = implode(',', array_fill(0, count($recipient_ids), '?'));
    $types = str_repeat('i', count($recipient_ids)) . 'i'; 
    $bind_params = array_merge($recipient_ids, [$id]);

    $block_stmt = $conn->prepare("SELECT userid, profileid FROM user_blocks WHERE (userid = ? AND profileid IN ($placeholders)) OR (userid IN ($placeholders) AND profileid = ?)");
    $final_block_params = array_merge([$id], $recipient_ids, $recipient_ids, [$id]);
    $block_types = 'i' . str_repeat('i', count($recipient_ids)) . str_repeat('i', count($recipient_ids)) . 'i';

    $block_stmt->bind_param($block_types, ...$final_block_params);
    $block_stmt->execute();
    $block_result = $block_stmt->get_result();

    if ($block_result->num_rows > 0) {
        $blocked_names = [];
        $recipient_map = array_column($found_users, 'username', 'id');

        while ($row_block = $block_result->fetch_assoc()) {
            $blocker = (int)$row_block['userid'];
            $blocked = (int)$row_block['profileid'];

            if ($blocker === $id) {
                $blocked_names[] = "You have blocked " . ($recipient_map[$blocked] ?? 'a user');
            } else {
                $blocked_names[] = ($recipient_map[$blocker] ?? 'a user') . " has blocked you";
            }
        }
        
        echo json_encode(['success' => false, 'error' => implode('. ', array_unique($blocked_names)) . "."]);
        exit;
    }

    $check_placeholders = implode(',', array_fill(0, count($all_participant_ids), '?'));
    $check_types = str_repeat('i', count($all_participant_ids)) . 'i';
    $check_params = array_merge($all_participant_ids, [count($all_participant_ids)]);

    $check_stmt = $conn->prepare("SELECT groupid 
        FROM message_users 
        WHERE userid IN ($check_placeholders)
        GROUP BY groupid
        HAVING COUNT(DISTINCT userid) = ? 
           AND COUNT(DISTINCT userid) = (SELECT COUNT(*) FROM message_users mu WHERE mu.groupid = message_users.groupid)");
    $check_stmt->bind_param($check_types, ...$check_params);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $group = $check_result->fetch_assoc();
        echo json_encode(['success' => true, 'groupid' => $group['groupid']]);
        exit;
    }

    $conn->begin_transaction();

    try {
        $is_group = (count($recipient_ids) > 1) ? 1 : 0;

        $create_group_stmt = $conn->prepare("INSERT INTO message_group (timestamp, is_group) VALUES (NOW(), ?)");
        $create_group_stmt->bind_param("i", $is_group);
        $create_group_stmt->execute();
        $groupid = $conn->insert_id;

        $insert_values_queries = implode(',', array_fill(0, count($all_participant_ids), '(?, ?, ?, NOW())'));
        $insert_member_stmt = $conn->prepare("INSERT INTO message_users (groupid, userid, admin, joined) VALUES $insert_values_queries");

        $insert_params = [];
        $insert_types = '';
        foreach ($all_participant_ids as $p_id) {
            $insert_params[] = $groupid;
            $insert_params[] = $p_id;
            $insert_params[] = ((int)$p_id === (int)$id) ? 1 : 0;
            $insert_types .= 'iii';
        }

        $insert_member_stmt->bind_param($insert_types, ...$insert_params);
        $insert_member_stmt->execute();

        $conn->commit();

        $notifications = new Notifications($conn);
        foreach ($all_participant_ids as $p_id) {
            $notifications->subscribe($p_id, 'direct_message', $groupid);
        }

        echo json_encode(['success' => true, 'groupid' => $groupid]);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => "An error occurred. Please try again later."]);
        exit;
    }
}

if(isset($_POST['group_delete'])) {
    $groupid = (int)$_POST['groupid'];

    $exists_sql = "SELECT id FROM message_group WHERE id = ?";
    $exists_stmt = $conn->prepare($exists_sql);
    $exists_stmt->bind_param("i", $groupid);
    $exists_stmt->execute();
    $exists_res = $exists_stmt->get_result();

    if($exists_res->num_rows === 0){
        echo json_encode(['success' => false, 'error' => "Group not found in database."]);
        exit;
    }

    $groupid = $exists_res->fetch_assoc()['id'] ?? $groupid;

    $check_sql = "SELECT 1 FROM message_users WHERE groupid = ? AND userid = ? AND admin = 1 LIMIT 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $groupid, $id);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();

    if ($check_res->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => "No permission to delete this group."]);
        exit;
    }

    $delete_sql = "DELETE t1, t2, t3
        FROM message_group t1
        LEFT JOIN message_users t2 ON t1.id = t2.groupid
        LEFT JOIN direct_message t3 ON t1.id = t3.groupid
        WHERE t1.id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $groupid);

    if (!$delete_stmt->execute()) {
        echo json_encode(['success' => false, 'error' => "An error has occured. Please try again later."]);
        exit;
    } else {
        echo json_encode(['success' => true, 'groupid' => $groupid]);
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

    <div id="ajax-error" class="w3-red w3-padding w3-margin-bottom w3-round w3-border w3-border-pink"></div>

    <div class="w3-row-padding">
        <div class="w3-third" id="groups" style="display:none">
            <div id="group-form" class="w3-margin-bottom">
                <div id="post">
                    <input name="direct-box" id="direct-box" class="w3-input w3-hover-opacity w3-round w3-border w3-border-grey" placeholder="Username (comma seperated for multiple users)" rows="auto" cols="40" />
                </div>
                <button id="group_create" onclick="create_group();" class="w3-btn w3-white w3-hover-opacity w3-round w3-padding w3-border w3-border-grey">
                    <span>Message user</span>
                </button>
            </div>

            <template id="message-group">
                <div class='user-message-group w3-light-grey w3-padding-small w3-margin-bottom w3-round w3-text-black w3-hover-grey' style="cursor:pointer;">
                    <p><img src="" class="user-image w3-round" width="25px" height="25px"> <span class="user"></span></p>
                    <p class="user-joined w3-text-grey"></p>
                </div>
            </template>
        </div>

        <div class="w3-rest w3-padding" id="messages">
            <template id="message-group-form">
                <div id="comment-form w3-half">
                    <div id="group-name"></div>
                    <div id="post">
                        <textarea name="comment-box" id="comment-box" class="w3-input w3-hover-opacity w3-round w3-border w3-border-grey" placeholder="Message... (bbcode supported)" rows="auto" cols="40"></textarea>
                    </div>

                    <button id="post-comment" class="w3-btn w3-white w3-hover-opacity w3-round w3-padding w3-border w3-border-grey">
                        <span>Send</span>
                    </button>

                    <button id="post-delete-group" class="w3-btn w3-red w3-hover-opacity w3-round w3-padding w3-border w3-border-pink" style="display: none;">
                        <span>Delete</span>
                    </button>
                </div>
            </template>

            <template id="message-group-msg">
                <div style="color: #000;" class='container w3-padding-small w3-round w3-margin-bottom w3-margin-top w3-card-2'">
                    <p><a class="user" href=""></a> sent <span class="timestamp"></span></p>
                    <p class="message"></p>
                </div>
            </template>
        </div>
    </div><br />

    <?php include '../linkbar.php' ?>
</body>

</html>