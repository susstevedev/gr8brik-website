<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/profile.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/notifications.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/com/bbcode.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/jbroadway/Slimdown.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/ajax/time.php";

$bbcode = new BBCode;
$page_limit = 10;
$crud = new mysqli_crud(DB_NAME3);
$user_crud = new mysqli_crud(DB_NAME);

if (loggedin()) {
    $id = $current_user->id ?? 0;
}

function gettopic($conn, $post_id) {
    global $crud;

    $post_row = $crud->readone("messages", "id,userid,username,title,timestamp,edited,views,status", "id = ? and (parent IS NULL OR parent = 0) and deleted_at is null", [$post_id]);

    if (!$post_row) {
        http_response_code(404);
        return ['success' => false, 'error' => "This conversation does not exist."];
    }

    $title = $post_row['title'];
    $userid = $post_row['userid'];
    $date = $post_row['timestamp'];
    $views = $post_row['views'];
    $category = $post_row['status'];
    $edit_date = $post_row['edited'] ?? null;
    $user = User::getUser($userid);

    if(!$user || $user->deactive || $user->suspended) {
        http_response_code(404);
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

function getreplies($post_id, $page) {
    global $page_limit, $crud, $user_crud;

    $offset = ($page - 1) * $page_limit;
    $rows = $crud->readall("messages", "*", "(parent = ? OR id = ?) and deleted_at is null", [$post_id, $post_id], "timestamp asc", $page_limit, $offset);
    $blocked = [];
    $users = [];

    if (!empty($rows)) {
        $userIds = array_column($rows, 'userid');
        $users = User::getUsers($userIds);

        if (loggedin()) {
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $params = array_merge($userIds, $userIds);
            $blocked = $user_crud->readall('user_blocks', 'userid, profileid', "userid IN ($placeholders) OR profileid IN ($placeholders)", $params);

            foreach ($blocked as $row) {
                $block_userid = (int)$row['userid'];
                $block_profileid = (int)$row['profileid'];

                //A user in our list blocked someone else
                if (isset($users[$block_userid]) && $block_userid !== $block_profileid) {
                    $blocked[$block_profileid]['you_blocked'] = true;
                }

                //Someone else blocked a user in our list
                if (isset($users[$block_profileid]) && $block_profileid !== $block_userid) {
                    $blocked[$block_profileid]['they_blocked'] = true;
                }
            }
        }
    }

    return [
        'users' => $users,
        'blocked' => $blocked,
        'rows' => $rows,
    ];
}

function getreplycount($post_id) {
    global $page_limit, $crud;

    $count_result = $crud->readone('messages', 'COUNT(*) as reply_count', 'parent = ? OR id = ?', [$post_id, $post_id]);
    //$count_result = $crud->query("SELECT COUNT(*) as reply_count FROM messages WHERE parent = '$post_id' OR id = '$post_id'");
    $reply_count = $count_result['reply_count'] ?? 0;
    $total_pages = ceil($reply_count / $page_limit);

    return [
        'raw' => $count_result,
        'replies' => $reply_count,
        'total_pages' => $total_pages,
    ];
}

function userreplycount($userid) {
    global $crud;

    $count_result = $crud->readone('messages', 'COUNT(*) as reply_count', 'userid = ? and deleted_at is null', [$userid]);
    return $count_result['reply_count'] ?? 0;
}
?>