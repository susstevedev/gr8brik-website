<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/bbcode.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/notifications.php';
$bbcode = new BBCode;

$conn = Database::get(DB_NAME);


if (isset($_GET['group'])) {
    if(!loggedin() || !isset($current_user)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => "Please login to continue."]);
        exit;
    }

    $uid = $current_user->id ?? 0;
    $sql = "SELECT
        mg.id AS group_id,
        mg.is_group,
        mg.group_name,
        mg.timestamp,
        GROUP_CONCAT(u.username SEPARATOR ', ') AS members,
        GROUP_CONCAT(u.picture SEPARATOR ', ') AS member_pics
    FROM message_group mg
    JOIN message_users me
        ON me.groupid = mg.id
        AND me.userid = ?
        AND mg.is_removed = 0
    LEFT JOIN message_users mu
        ON mu.groupid = mg.id
        AND mu.userid != ?
    JOIN users u
        ON u.id = mu.userid
        AND deactive IS NULL
        AND suspended = 0
    GROUP BY
        mg.id,
        mg.is_group,
        mg.group_name
    ORDER BY mg.timestamp DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $uid, $uid);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    if ($result->num_rows !== 0) {
        while ($row = $result->fetch_assoc()) {
            $group = null;

            if ($row['is_group'] == 1) {
                $group['title'] = $row['group_name'] ?? $row['members'];
            } else {
                $members = explode(', ', $row['members']);
                $member_pics = explode(', ', $row['member_pics']);

                $group['title'] = $members[0] ?? 'Deleted User';
                $group['picture'] = isset($member_pics[0]) ? $member_pics[0] : null;
            }

            $group['joined'] = isset($row['timestamp']) ? 'Last messaged ' . time_ago($row['timestamp']) : null;

            $data[] = [
                'success' => true,
                'id' => $row['group_id'],
                'title' => htmlentities($group['title'], ENT_NOQUOTES),
                'pictureurl' => $group['picture'] ?? null,
                'joined' => $group['joined'] ?? null,
            ];
        }
    }

    header("Content-Type: application/json");
    echo json_encode($data);
    exit;
}

if (isset($_GET['message'])) {
    $message = isset($_GET['message']) ? (int)$_GET['message'] : null;
    $uid = $current_user->id ?? 0;
    $uadmin = $current_user->admin ?? false;

    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Empty message ID']);
        exit;
    }

    $sql = "SELECT
        g.id AS groupid,
        g.group_name,
        g.is_group,
        adm.userid AS sender_group_admin
    FROM message_group g
    LEFT JOIN message_users adm
        ON adm.groupid = g.id
        AND adm.admin = 1
    WHERE g.id = ?";

    if($uadmin !== true) {
        $sql .= " AND g.is_removed = 0";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $message);
    $stmt->execute();
    $group_res = $stmt->get_result();

    if ($group_res->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Group not found in database.']);
        exit;
    }

    $group_row = $group_res->fetch_assoc();
    $group = $group_row['groupid'];
    $groupname = $group_row['group_name'];
    $groupadmin = ((int)$group_row['sender_group_admin'] === (int)$uid);

    $sql = "SELECT GROUP_CONCAT(u.username SEPARATOR ', ') AS members
    FROM message_users mu
    JOIN users u
        ON u.id = mu.userid
        AND u.deactive IS NULL
        AND u.suspended = 0
    WHERE mu.groupid = ?
    AND mu.userid != ?";

    $member_stmt = $conn->prepare($sql);
    $member_stmt->bind_param("ii", $message, $uid);
    $member_stmt->execute();
    $member_res = $member_stmt->get_result();
    $members = $member_res->fetch_assoc()['members'] ?? null;

    if (empty($groupname)) {
        $groupname = $members;
    }

    $sql = "SELECT
        dm.id AS message_id,
        dm.message,
        dm.timestamp,
        sender.id AS sender_id,
        sender.username AS sender_username,
        sender.deactive AS sender_inactive,
        sender.picture AS sender_picture,
        sender.age AS sender_age
    FROM direct_message dm
    LEFT JOIN users sender
        ON sender.id = dm.userid
        AND sender.deactive IS NULL
        AND sender.suspended = 0
    WHERE dm.groupid = ?
    ORDER BY dm.timestamp DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $message);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $post = $bbcode->toHTML($row['message'] ?? '', true, true);

        if ((int)$uid !== (int)$row['sender_id']) {
            $p_color = "#90EE90";
        } else {
            $p_color = "#ADD8E6";
        }

        $url = null;
        $username = 'Deleted User';
        if ($row['sender_id'] !== null && !$row['sender_inactive']) {
            $username = $row['sender_username'];
            $url = '/@' . rawurlencode($username);
        }

        $data[] = [
            'id' => $row['message_id'],
            'userID' => $row['sender_id'],
            'user' => htmlentities($username),
            'url' => $url,
            'message' => $post,
            'color' => $p_color,
            'timestamp' => time_ago(date('Y-m-d H:i:s', $row['timestamp'] ?? 0)),
        ];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'groupid' => $group,
        'name' => $groupname,
        'admin' => $groupadmin,
        'msgs' => $data
    ]);
    exit;
}

if (isset($_POST['comment'])) {
    header('Content-type: application/json');
    if(!loggedin() || !isset($current_user)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => "Please login to continue."]);
        exit;
    }

    $comment = isset($_POST['commentbox']) ? htmlentities($_POST['commentbox']) : null;
    $id = $current_user->id ?? 0;
    $conn2 = Database::get(DB_NAME);

    if ($comment === "" || $comment === null || empty($comment)) {
        echo json_encode(['success' => false, 'error' => "Message shall contain text."]);
        exit;
    }

    $groupid = (int)$_POST['groupid'];

    $sql = "SELECT id FROM message_group WHERE id = ? AND is_removed = 0";
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
    if(!loggedin() || !isset($current_user)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => "Please login to continue."]);
        exit;
    }

    $id = $current_user->id ?? 0;
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

    $check_stmt = $conn->prepare("SELECT mu.groupid
    FROM message_users mu
    INNER JOIN message_group mg
        ON mg.id = mu.groupid
        AND mg.is_removed = 0
    WHERE mu.userid IN ($check_placeholders)
    GROUP BY mu.groupid
    HAVING COUNT(DISTINCT mu.userid) = ?
    AND COUNT(DISTINCT mu.userid) = (SELECT COUNT(*) FROM message_users mu2 WHERE mu2.groupid = mu.groupid)");
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
    if(!loggedin() || !isset($current_user)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => "Please login to continue."]);
        exit;
    }

    $id = $current_user->id ?? 0;
    $groupid = (int)$_POST['groupid'];

    $exists_sql = "SELECT id FROM message_group WHERE id = ? AND is_removed = 0";
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

    $delete_sql = "UPDATE message_group SET is_removed = 1 WHERE id = ?";
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

    <div id="modal-report" class="w3-modal" style="z-index: 999999">
        <div class="w3-modal-content gr8-theme w3-card-2 w3-light-grey w3-center">
            <div class="w3-container">
                <span onclick="$('#reportForm')[0].reset();$('#modal-report').hide();" class="w3-button w3-large w3-red w3-hover-white w3-display-topright">&times;</span>
                <form id="reportForm">
                    <h2>Why do you want to report this message?</h2>
                    <b>You can only report content when it violates our <a href="/rules?src=creation" target="_blank"><i class="fa fa-external-link" aria-hidden="true"></i>rules</a>.</b><br />
                    <input type="radio" name="reason" value="violent" class="w3-check"> <label>Violent or extreme content</label><br />
                    <input type="radio" name="reason" value="misinformation" class="w3-check"> <label>Misinformation/disinformation</label><br />
                    <input type="radio" name="reason" value="inappropriate" class="w3-check"> <label>Inappropriate content</label><br />
                    <input type="radio" name="reason" value="harrasing-me" class="w3-check"> <label>Harassing me or others</label><br />
                    <input type="radio" name="reason" value="spam" class="w3-check"> <label>Spam</label><br />
                    <input type="radio" name="reason" value="underage" class="w3-check"> <label>User is under 13</label><br />
                    <input type="radio" name="reason" value="copyright" class="w3-check"> <label>Copyrighted content</label><br />
                    <input type="radio" name="reason" value="other" class="w3-check" id="otherReasonToggle"> <label>Something else</label><br /><br />

                    <textarea class="w3-input w3-card-2 w3-hover-shadow w3-mobile w3-round" name="other" id="otherReason" placeholder="Explain more..." rows="4"></textarea><br />

                    <span class="w3-btn w3-large w3-white w3-hover-blue w3-round-small" onclick="$('#reportForm')[0].reset();$('#modal-report').hide();">Close</span>
                    <button type="submit" class="w3-btn w3-large w3-white w3-hover-red w3-round-small">Report</button>
                </form>
            </div>
        </div>
    </div>

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
                    <div class="tooltip" id="report-message">
                        <span class="w3-tag w3-blue tooltiptext">Report this message to moderators</span>
                        <button data-testid="" name="flag-comment" class="report-message-button fa fa-flag w3-btn w3-red w3-hover-opacity w3-padding-small w3-round w3-border w3-border-pink"></button>
                    </div>
                </div>
            </template>
        </div>
    </div><br />

    <?php include '../linkbar.php' ?>
</body>

</html>