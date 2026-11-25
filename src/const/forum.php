<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/profile.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/notifications.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/const/utils/time.php";

$page_limit = 10;

$conn = Database::get(DB_NAME3);
$user_conn = Database::get(DB_NAME);

if (loggedin()) {
    $id = $current_user->id ?? 0;
}

function gettopic($conn, $post_id) {
    $stmt = $conn->prepare(
        "SELECT t.id, t.userid, u.username, p.picture, t.title, t.timestamp, t.edited, t.views, t.status
        FROM messages t
        INNER JOIN users u
        ON u.id = t.userid
        LEFT JOIN user_profiles p
        ON p.userid = t.userid
        WHERE t.id = ?
        AND (t.parent IS NULL OR t.parent = 0)
        AND t.deleted_at IS NULL
        AND u.deactive IS NULL
        AND u.suspended = 0"
    );
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post_row = $result->fetch_assoc();

    if (!$post_row) {
        http_response_code(404);
        return ['success' => false, 'error' => "This conversation does not exist."];
    }

    $title = $post_row['title'];
    $user_id = $post_row['userid'];
    $date = $post_row['timestamp'];
    $views = $post_row['views'];
    $category = $post_row['status'];
    $edit_date = $post_row['edited'] ?? null;

    $user_name = $post_row['username'] ?? null;
    $user_picture = $post_row['picture'] ?? null;

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
            'id' => $user_id,
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
    global $page_limit, $conn, $user_conn;

    $offset = ($page - 1) * $page_limit;
    $rows = [];
    $blocked = [];

    $stmt = $conn->prepare(
        "SELECT t.id, t.parent, t.content, t.timestamp, t.edited, u.id AS userid, u.username, u.admin AS user_admin, p.picture, p.verified_badge
        FROM messages t
        LEFT JOIN users u
        ON u.id = t.userid
        LEFT JOIN user_profiles p
        ON p.userid = t.userid
        WHERE (t.parent = ? OR t.id = ?)
        AND t.deleted_at IS NULL
        AND u.deactive IS NULL
        AND u.suspended = 0
        ORDER BY timestamp ASC
        LIMIT ? OFFSET ?"
    );
    $stmt->bind_param("iiii", $post_id, $post_id, $page_limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $rows[$row['id']] = $row;
    }

    if (!empty($rows)) {
        $userIds = array_column($rows, 'userid');

        if (loggedin()) {
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $params = array_merge($userIds, $userIds);
            $types = str_repeat('i', count($params));

            $stmt = $user_conn->prepare(
                "SELECT userid, profileid
                FROM user_blocks
                WHERE userid IN ($placeholders)
                OR profileid IN ($placeholders)"
            );
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $block_userid = (int)$row['userid'];
                $block_profileid = (int)$row['profileid'];

                //A user in our list blocked someone else
                if (isset($userIds[$block_userid]) && $block_userid !== $block_profileid) {
                    $blocked[$block_profileid]['you_blocked'] = true;
                }

                //Someone else blocked a user in our list
                if (isset($userIds[$block_profileid]) && $block_profileid !== $block_userid) {
                    $blocked[$block_profileid]['they_blocked'] = true;
                }
            }
        }
    } else {
        http_response_code(404);
        return [];
    }

    return [
        'blocked' => $blocked,
        'rows' => $rows,
    ];
}

function getreplycount($post_id) {
    global $page_limit, $conn;

    $stmt = $conn->prepare("SELECT COUNT(*) as reply_count FROM messages WHERE parent = ? OR id = ?");
    $stmt->execute([$post_id, $post_id]);
    $result = $stmt->get_result();

    $reply_count = $result->fetch_assoc()['reply_count'] ?? 0;
    $total_pages = ceil($reply_count / $page_limit);

    return [
        'replies' => $reply_count,
        'total_pages' => $total_pages,
    ];
}

function userreplycount($userid) {
    global $conn;

    $stmt = $conn->prepare("SELECT COUNT(*) as reply_count FROM messages WHERE userid = ? AND deleted_at IS NULL");
    $stmt->execute([$userid]);
    $result = $stmt->get_result();

    return $result->fetch_assoc()['reply_count'] ?? 0;
}

/**
 * Return object that includes a random bullshit sentence (raw and markdown)
 */
function RandomWord() {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/const/Autoloader.php';

    try {
        $file = $_SERVER['DOCUMENT_ROOT'] . '/lib/static/forum/randomwords.json';

        if (!file_exists($file)) {
            throw new Exception($file . ' Not Found');
        }

        $jsonstr = file_get_contents($file);

        try {
            $jsonobj = json_decode($jsonstr, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            exit('JSON ERROR: ' . $e->getMessage());
        }

        $words = $jsonobj->__Words->__Markdown;
        $random = $words[array_rand($words)];

        $Word = new stdClass();
        $Word->Display = htmlentities($random);
        $Word->Markdown = Slimdown::render($random);

        return $Word;
    } catch (Exception $e) {
        exit($e->getMessage());
    }
}
?>