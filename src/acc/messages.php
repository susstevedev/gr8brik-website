<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';
if (loggedin()) {
    $id = $current_user->id;
}

$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    exit($conn->connect_error);
}

if (isset($_GET['group'])) {
    $sql = "SELECT * FROM message_group WHERE userid = $id OR profileid = $id";
    $result = $conn->query($sql);

    $data = [];
    if ($result->num_rows === 0) {
        header("HTTP/1.0 403 Forbidden");
        echo json_encode(['success' => false, 'error' => 'No messages to display. Try sending one!']);
        exit;
    }

    while ($row = $result->fetch_assoc()) {
        $userid = $row['userid'];
        $profileid = $row['profileid'];

        if ($result->num_rows === 0) {
            header('HTTP/1.0 404 Not Found');
            echo 'Not Found';
            exit;
        }

        $result2 = $conn->query("SELECT id, username, picture FROM users WHERE id IN ($userid, $profileid)");
        $username1 = $picture1 = $username2 = $picture2 = '';

        while ($row2 = $result2->fetch_assoc()) {
            if ((int)$row2['id'] === (int)$userid) {
                $username1 = $row2['username'];
                $picture1 = $row2['picture'];
            }
            if ((int)$row2['id'] === (int)$profileid) {
                $username2 = $row2['username'];
                $picture2 = $row2['picture'];
            }
        }

        //Simple logic to display the opposite users name
        if ((int)$id === (int)$userid) {
            $group_user = $username2;
            $group_picture = $picture2;
        } elseif ((int)$id === (int)$profileid) {
            $group_user = $username1;
            $group_picture = $picture1;
        }

        header("HTTP/1.0 200 OK");
        $data[] = [
            'success' => true,
            'user1' => '@' . htmlspecialchars(strtolower($username1)),
            'user2' => '@' . htmlspecialchars(strtolower($username2)),
            'user' => htmlspecialchars($group_user),
            'picture' => '<img src="' . htmlspecialchars($group_picture) . '" class="w3-round" width="50px" height="50px">',
            'id' => $row['id'],
        ];
    }

    echo json_encode($data);
    exit;
}

if ((isset($_GET['message']))) {
    $message = $_GET['message'];
    $uid = $current_user->id;
    $sql = "SELECT * FROM direct_message WHERE groupid = $message ORDER BY TIMESTAMP DESC";

    $result = $conn->query($sql);
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $group = $row['groupid'];
        $userid = $row['userid'];
        $post = $row['message'];
        $timestamp = $row['timestamp'];

        $message = htmlentities($message, ENT_QUOTES, 'UTF-8');

        if ($result->num_rows === 0) {
            header('HTTP/1.0 404 Not Found');
            echo 'Not Found';
            exit;
        }

        $sql = "SELECT * FROM users WHERE id = $userid";
        $result2 = $conn->query($sql);
        $row2 = $result2->fetch_assoc();
        $username = $row2['username'];

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
            'user' => '@' . htmlspecialchars(strtolower($username)),
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

    if ($comment === "" || $comment === null) {
        echo json_encode(['success' => false, 'error' => "Message shall contain text."]);
        exit;
    }

    $groupid = $_POST['groupid'];

    $date = time();
    $sql = "INSERT INTO direct_message (userid, groupid, message, timestamp) VALUES (?, ?, ?, ?)";
    $stmt2 = $conn2->prepare($sql);
    $stmt2->bind_param("iisi", $id, $groupid, $comment, $date);

    if (!$stmt2->execute()) {
        echo json_encode(['success' => false, 'error' => "An error has occured. Please try again later."]);
        exit;
    } else {
        $stmt2->close();
        echo json_encode(['success' => true]);
        exit;
    }
}

if (isset($_POST['group_create'])) {
    header('Content-type: application/json');
    $comment = $conn->real_escape_string(htmlspecialchars($_POST['commentbox']));

    $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if ($conn2->connect_error) {
        exit($conn2->connect_error);
    }

    if ($comment === "" || $comment === null) {
        echo json_encode(['success' => false, 'error' => "Empty user field"]);
        exit;
    }

    if (strtolower($comment) == strtolower($current_user->username)) {
        echo json_encode(['success' => false, 'error' => "You cannot add yourself to your messages nor can you message yourself"]);
        exit;
    }

    $result = $conn2->query("SELECT * FROM users WHERE username = '$comment'");
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => "User not found"]);
        exit;
    }

    $row = $result->fetch_assoc();
    $profileid = $row['id'];

    $youBlocked = false;
    $theyBlocked = false;
    $blockResult = $conn2->query("SELECT * FROM user_blocks WHERE (userid = '$profileid' AND profileid = '$id') OR (userid = '$id' AND profileid = '$profileid')");
    if ($blockResult && $blockResult->num_rows > 0) {
        while ($row_block = $blockResult->fetch_assoc()) {
            if ($row_block['userid'] == $id && $row_block['profileid'] == $profileid) {
                $youBlocked = true;
            } elseif ($row_block['userid'] == $profileid && $row_block['profileid'] == $id) {
                $theyBlocked = true;
            }
        }
        if ($youBlocked && $theyBlocked) {
            $message = "You blocked " . $row['username'] . ", and they blocked you. You cannot message each other.";
        } elseif ($youBlocked) {
            $message = "You blocked " . $row['username'] . ". They cannot message you.";
        } elseif ($theyBlocked) {
            $message = "You're blocked from " . $row['username'] . ". You cannot message them.";
        }

        if ($theyBlocked === true || $youBlocked === true) {
            echo json_encode(['success' => false, 'error' => $message]);
            exit;
        }
    }

    $user_one = min($id, $profileid);
    $user_two = max($id, $profileid);

    $sql3 = "SELECT * FROM message_group WHERE userid = '$user_one' AND profileid = '$user_two'";
    $result3 = $conn2->query($sql3);
    if ($result3->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => "Group with " . $row['username'] . " already exists."]);
        exit;
    }

    $sql = "INSERT INTO message_group (userid, profileid) VALUES (?, ?)";
    $stmt2 = $conn2->prepare($sql);
    $stmt2->bind_param("ii", $id, $profileid);

    if (!$stmt2->execute()) {
        echo json_encode(['success' => false, 'error' => "An error has occured. Please try again later."]);
        exit;
    } else {
        $stmt2->close();
        echo json_encode(['success' => true]);
        exit;
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <title>Direct Messages</title>
    <?php include '../header.php' ?>
</head>

<body class="w3-light-blue w3-container">
    <?php
        include '../navbar.php';

        if (loggedin()) {
            include 'panel.php';
        } else {
            echo "<h4>You must be authenticated to view messages!</h4>";
            include '../linkbar.php';
            exit;
        }
    ?>

    <script>
        $(document).ready(function() {
            console.log('init');

            function load_message_group() {
                $.ajax({
                    url: 'messages.php',
                    type: 'GET',
                    data: {
                        group: 1
                    },
                    success: function(res) {
                        let groups;
                        let res2 = JSON.parse(res);

                        groups = res2.map(group => `
                            <div class='w3-light-grey w3-padding-small w3-text-black w3-hover-green' style="cursor:pointer;" id="${group.id}" onclick='load_message(${group.id})'>
                                <p>${group.picture} - ${group.user}</p>
                            </div><br />
                        `);
                        $("#groups").append(groups).fadeIn();
                    },
                    error: (xhr, text, err) => {
                        console.error("error: " + text, err, xhr);
                        let response = JSON.parse(xhr.responseText);
                        $("#groups").append(response.error).fadeIn();
                    }
                });
            }
            load_message_group();

            window.load_message = function(id) {
                $.ajax({
                    url: 'messages.php',
                    type: 'GET',
                    data: {
                        message: id
                    },
                    success: function(res) {
                        let messages;
                        let res2 = JSON.parse(res);

                        let form = `
                        <div id="comment-form w3-half">
                        	<div id="post">
                            	<textarea name="comment-box" id="comment-box" class="w3-input w3-hover-shadow" placeholder="Message..." rows="auto" cols="40"></textarea>
                        	</div>
                        	<button id="post-comment" class="w3-btn w3-blue w3-hover-shadow w3-padding-small w3-border">
                        		<span>Send</span>
                        	</button></div>
                        `;

                        if (res2) {
                            messages = res2.map(message => `
                            	<div style="background-color: ${message.color}; color: #000;" class='w3-padding-small w3-margin-bottom w3-margin-top w3-card-2' id="${message.id}">
                                	<p>${message.user} ${message.timestamp}</p>
                                	<p>${message.message}</p>
                            	</div>
                        	`);
                        } else {
                            messages = "<b>No messages yet</b>";
                        }

                        $("#messages").hide();
                        $("#messages").html(form);
                        $("#messages").fadeIn().append(messages);
                        $("#post-comment").attr('onclick', `send_comment(${id})`)
                    }
                });
            }

            window.send_comment = function(groupid) {
                event.preventDefault();

                const btn = $(this);
                const commentBox = $("#comment-box").val();
                const commentBtnText = $("#comment-btn-text");

                commentBtnText.html('<img src="/img/loading.gif" style="width: 20px; height: 20px;" />');
                btn.prop("disabled", true);

                $.ajax({
                    url: "messages.php",
                    method: "POST",
                    data: {
                        comment: true,
                        groupid: groupid,
                        commentbox: commentBox
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.reload();
                        } else {
                            commentBtnText.html('Send');
                            btn.prop("disabled", false);
                            alert(response.error);
                        }
                    },
                    error: (xhr, text, err) => {
                        commentBtnText.html('Send');
                        btn.prop("disabled", false);
                        console.error("error: " + text, err, xhr);
                    }
                });
            }

            window.create_group = function() {
                event.preventDefault();

                const btn = $(this);
                const commentBox = $("[name='direct-box']").val();
                const commentBtnText = $("#comment-btn-text");

                commentBtnText.html('<img src="/img/loading.gif" style="width: 20px; height: 20px;" />');
                btn.prop("disabled", true);

                $.ajax({
                    url: "messages.php",
                    method: "POST",
                    data: {
                        group_create: true,
                        commentbox: commentBox
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Message sent');
                            window.location.reload();
                        } else {
                            commentBtnText.html('Send');
                            btn.prop("disabled", false);
                            alert(response.error);
                        }
                    },
                    error: (jqXHR, textStatus, errorThrown) => {
                        commentBtnText.html('Send');
                        btn.prop("disabled", false);
                        console.error("error:", textStatus, errorThrown, jqXHR);
                        const response = JSON.parse(jqXHR.responseText);
                        alert(resonse.error);
                    }
                });
            }
        });
    </script>

    <div class="w3-row">
        <div class="w3-third" id="groups" style="display:none">
            <div id="group-form">
                <div id="post">
                    <textarea name="direct-box" id="direct-box" class="w3-input w3-hover-shadow" placeholder="Username" rows="auto" cols="40"></textarea>
                </div>
                <button id="group_create" onclick="create_group();" class="w3-btn w3-blue w3-hover-shadow w3-padding-small w3-border">
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