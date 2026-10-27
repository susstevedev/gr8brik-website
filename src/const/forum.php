<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/profile.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/notifications.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/com/bbcode.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/jbroadway/Slimdown.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/ajax/time.php";
$bbcode = new BBCode;
$page_limit = 10;

if (loggedin()) {
    $id = $current_user->id ?? 0;
}

function gettopic($conn, $post_id) {
    $stmt = $conn->prepare("SELECT id, userid, username, title, timestamp, edited, views, status FROM messages WHERE id = ? AND (parent IS NULL OR parent = 0) AND deleted_at IS NULL LIMIT 1");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post_row = $result->fetch_assoc() ?? [];
    $stmt->close();

    if (!$post_row) {
        http_response_code(404);
        //$post_row['error'] = "This conversation does not exist.";
        return ['success' => false, 'error' => "This conversation does not exist."];
    }

    $title = $post_row['title'];
    $userid = $post_row['userid'];
    $date = $post_row['timestamp'];
    $views = $post_row['views'];
    $category = $post_row['status'];
    $edit_date = $post_row['edited'] ?? null;

    $conn2 = Database::get(DB_NAME);

    $user = User::getUser($userid);

    if(!$user || $user->deactive || $user->suspended) {
        http_response_code(404);
        //$post_row['error'] = 'The user that created this thread does not exist';
        return ['success' => false, 'error' => "The user that created this thread does not exist"];
    }

    $user_name = $user->username ?? null;
    $user_picture = $user->picture ?? null;

    if (!isset($_SESSION['viewed_post_ids'])) {
        $_SESSION['viewed_post_ids'] = [];
    }

    if (!in_array($post_id, $_SESSION['viewed_post_ids'])) {
        $view_stmt = $conn->prepare("UPDATE messages SET views = views + 1 WHERE id = ?");
        $view_stmt->bind_param("i", $post_id);
        $view_stmt->execute();
        $_SESSION['viewed_post_ids'][] = $post_id;
    }

    if ($category === "deleted") {
        http_response_code(404);
        //$post_row['error'] = "This conversation has been removed because it violated our <a href='/rules'>rules</a>.";
        return ['success' => false, 'error' => "This conversation has been removed because it violated our <a href='/rules'>rules</a>."];
    }

    //you would probably like to know what categories exist
    //in the database table it should be an enum with the values
    //'pinned','pinnedLocked','deleted','nolist','locked','general'
    //pinned = pinned to top of index page
    //pinnedLocked = like pinned but locked from new replies
    //locked = locked from new replies
    //general = should be the default for new threads
    //nolist = unlisted
    //deleted = removed by admin

    $data = [
        'success' => true,
        'id' => $post_id,
        'user' => [
            'id' => $userid,
            'name' => $user_name,
            'picture' => $user_picture,
        ],
        'date' => $date,
        'edit_date' => $edit_date,
        'views' => $views,
        'category' => $category,
        'title' => $title,
    ];

    return $data ?? [];
}

function getreplies($db, $post_id, $page) {
    $offset = ($page - 1) * $page_limit;

    $sql = "SELECT * FROM messages WHERE (parent = $post_id OR id = $post_id) AND deleted_at IS NULL ORDER BY timestamp ASC LIMIT $limit OFFSET $offset";
    $comResult = $db->query($sql);
    $rows = [];
    $blocked = [];

    if ($comResult->num_rows > 0) {
        while ($row = $comResult->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    if (!empty($rows)) {
        $userIds = array_column($rows, 'userid');
        $users = User::getUsers($userIds);

        if (loggedin()) {
            foreach($userIds as $userId) {
                $result = $conn2->query("SELECT userid, profileid FROM user_blocks WHERE userid = $userId OR profileid = $userId");

                while ($row = $result->fetch_assoc()) {
                    print_r($row);

                    $block_userid = (int)$row['userid'];
                    $block_profileid = (int)$row['profileid'];

                    if ($block_userid === $userId && $userId !== $block_profileid) {
                        $blocked[$block_profileid]['you_blocked'] = true;
                    }

                    if ($block_profileid === $userId && $userId !== $block_userid) {
                        $blocked[$userid]['they_blocked'] = true;
                    }
                }
            }
        }
    }
}

function getreplycount($db, $post_id) {
    global $page_limit;

    $count_result = $db->query("SELECT COUNT(*) as reply_count FROM messages WHERE parent = '$post_id' OR id = '$post_id'");
    $reply_count = $count_result->fetch_assoc()['reply_count'] ?? 0;
    $total_pages = ceil($reply_count / $page_limit);

    return [
        'replies' => $reply_count,
        'total_pages' => $total_pages,
    ];
}
?>