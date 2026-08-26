<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';

class Notifications
{
    public function __construct(private mysqli $db)
    {
        $this->db = $db;
    }

    public function subscribe(int $recipientId, string $category, int $contentId): void
    {
        if (!loggedin()) {
            return;
        }

        $stmt = $this->db->prepare("INSERT IGNORE INTO subscriptions (userid, category, content) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $recipientId, $category, $contentId);
        $stmt->execute();
        $stmt->close();
    }

    public function notify_subscribers(string $category, int $contentId, int $actorId): void
    {
        $stmt = $this->db->prepare("SELECT userid FROM subscriptions WHERE content = ? AND category = ? AND userid != ?");
        $stmt->bind_param("isi", $contentId, $category, $actorId);
        $stmt->execute();

        $result = $stmt->get_result();
        $notif = $this->db->prepare("INSERT INTO notifications (user, profile, timestamp, content, category2) VALUES (?, ?, ?, ?, ?)");
        $alert = $this->db->prepare("UPDATE users SET alert = alert + 1 WHERE id = ?");

        $time = time();

        while ($row = $result->fetch_assoc()) {
            $recipientId = (int)$row['userid'];
            $notif->bind_param("iiiis", $recipientId, $actorId, $time, $contentId, $category);
            $notif->execute();

            $alert->bind_param("i", $recipientId);
            $alert->execute();
        }

        $notif->close();
        $alert->close();
        $stmt->close();
    }

    public function remove_subscriber(string $category, int $contentId, int $recipientId): void
    {
        $stmt = $this->db->prepare("SELECT userid FROM subscriptions WHERE content = ? AND category = ? AND userid = ?");
        $stmt->bind_param("isi", $contentId, $category, $recipientId);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            $notif = $this->db->prepare("DELETE FROM subscriptions WHERE content = ? AND category = ? AND userid = ?");
            $notif->bind_param("isi", $contentId, $category, $recipientId);
            $notif->execute();

            $notif->close();
            $stmt->close();
        }
    }

    public function is_subscriber(string $category, int $contentId, int $recipientId): bool
    {
        $stmt = $this->db->prepare("SELECT userid FROM subscriptions WHERE content = ? AND category = ? AND userid = ?");
        $stmt->bind_param("isi", $contentId, $category, $recipientId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return false;
        }
    }

    public function get_subscribers(string $category, int $contentId)
    {
        $stmt = $this->db->prepare("SELECT userid FROM subscriptions WHERE content = ? AND category = ?");
        $stmt->bind_param("is", $contentId, $category);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = [];

        while ($row = $result->fetch_assoc()) {
            $recipientId = (int)$row['userid'];
            $userObj = User::getUser($recipientId);

            $users[] = [
                'id' => $recipientId,
                'username' => $userObj->username,
                'picture' => $userObj->picture,
            ];
        }

        $stmt->close();
        return $users;
    }

    public function get_notifications(int $userId, int $page)
    {
        if ($this->db->connect_error) {
            return $this->db->connect_error;
        }

        if (!loggedin()) {
            return;
        }

        $usero = User::getUser($userId);
        if (User::isDeleted($userId)) {
            return;
        }

        if ($page < 1) {
            $page = 1;
        }

        $limit = 8;
        $offset = ($page - 1) * $limit;

        $sql = "SELECT * FROM notifications WHERE user = ? ORDER BY timestamp DESC";
        $notif_count = $usero->alert ?? 0;

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $grouped_notifications = [];

        if ($result->num_rows !== 0 && $notif_count > 0) {
            $alertsql = "UPDATE users SET alert = 0 WHERE id = ?";
            $alertstmt = $this->db->prepare($alertsql);
            $alertstmt->bind_param("i", $userId);
            if ($alertstmt->execute()) {
                $alertstmt->close();
            } else {
                echo $alertstmt->error;
                exit;
            }
        }

        while ($row = $result->fetch_assoc()) {
            $profile = $row['profile'] ?? 0;
            $content = $row['content'] ?? 0;
            $category = $row['category2'];
            $timestamp = is_numeric($row['timestamp']) ? (int)$row['timestamp'] : time();

            $user_data_o = User::getUser($profile);
            $username = htmlspecialchars($user_data_o->username) ?: '[unknown]';
            $userid = $user_data_o->id ?: 0;

            //groups matching content together
            if ($category === 'follow') {
                $group_key = $category . "_" . date("Y-m-d", $timestamp);
            } else {
                $group_key = $category . "_" . $row['content'] . "_" . date("Y-m-d", $timestamp);
            }

            if (!isset($grouped_notifications[$group_key])) {
                $grouped_notifications[$group_key] = [
                    'category' => $category,
                    'content' => $content,
                    'timestamp' => $timestamp,
                    'users' => [],
                    'fallback_pic' => $user_data_o->picture ?: '/img/no_image.png'
                ];
            }

            if (!in_array($userid, array_column($grouped_notifications[$group_key]['users'], 'id'))) {
                $grouped_notifications[$group_key]['users'][] = [
                    'name' => $username,
                    'id'   => $userid
                ];
            }
        }

        $sliced_notifications = array_slice($grouped_notifications, $offset, $limit);
        $sliced_notifications['count'] = count($grouped_notifications);
        $sliced_notifications['success'] = true;
        $stmt->close();
        return $sliced_notifications;
    }

    public function toHTML($notifs) {
        foreach ($notifs as $group_name => $group) {
            if(!is_array($group)) {
                continue;
            }

            $url = null;
            $post = null;
            $img = null;

            $category = $group['category'];
            $content = $group['content'];
            $users = $group['users'];
            $user_count = count($users);

            if ($user_count === 1) {
                $user_string = "<a href='/user/" . $users[0]['id'] . "'><i class='fa fa-at' aria-hidden='true'></i>" . $users[0]['name'] . "</a>";
            } elseif ($user_count === 2) {
                $user_string = "<a href='/user/" . $users[0]['id'] . "'><i class='fa fa-at' aria-hidden='true'></i>" . $users[0]['name'] . "</a> and <a href='/user/" . $users[1]['id'] . "'>" . $users[1]['name'] . "</a>";
            } else {
                $user_string = "<a href='/user/" . $users[0]['id'] . "'><i class='fa fa-at' aria-hidden='true'></i>" . $users[0]['name'] . "</a> and " . ($user_count - 1) . " others";
            }

            switch ($category):
                case 'follow':
                    $url = "/acc/following?p=followerstab";

                    if ($user_count > 1) {
                        $title = 'New followers';
                    } else {
                        $title = "New follower";
                    }

                    $post = '';
                    $img = $group['fallback_pic'];
                    break;
                case 'comment':
                    $stmt2 = $this->db->prepare("SELECT screenshot, name FROM `" . DB_NAME2 . "`.`model` WHERE id = ?");
                    $stmt2->bind_param("i", $content);
                    $stmt2->execute();
                    $res2 = $stmt2->get_result();
                    
                    if ($row2 = $res2->fetch_assoc()) {
                        $img = $row2['screenshot'];
                        $url = "/build/" . urlencode($content);
                        $title = !empty($row2['name']) ? $row2['name'] : "[unknown]";
                        $post = 'commented on by';
                    }

                    $stmt2->close();
                    break;
                case 'forum_reply':
                    $stmt2 = $this->db->prepare("SELECT title FROM `" . DB_NAME3 . "`.`messages` WHERE id = ?");
                    $stmt2->bind_param("i", $content);
                    $stmt2->execute();
                    $res2 = $stmt2->get_result();

                    if ($row2 = $res2->fetch_assoc()) {
                        $img = '../img/com.jpg';
                        $url = "/topic/" . urlencode($content);
                        $title = !empty($row2['title']) ? $row2['title'] : "[unknown]";
                        $post = "replied to by";
                    }

                    $stmt2->close();
                    break;
                case 'creation_remove':
                    $stmt2 = $this->db->prepare("SELECT screenshot, name FROM `" . DB_NAME2 . "`.`model` WHERE id = ?");
                    $stmt2->bind_param("i", $content);
                    $stmt2->execute();
                    $res2 = $stmt2->get_result();

                    if ($row2 = $res2->fetch_assoc()) {
                        $img = $row2['screenshot'];
                        $url = "/build/" . urlencode($content);
                        $title = !empty($row2['name']) ? $row2['name'] : "[unknown]";
                        $post = $title . " was removed by";
                    }

                    $stmt2->close();
                    break;
                case 'creation_fav':
                    $stmt2 = $this->db->prepare("SELECT screenshot, name FROM `" . DB_NAME2 . "`.`model` WHERE id = ?");
                    $stmt2->bind_param("i", $content);
                    $stmt2->execute();
                    $res2 = $stmt2->get_result();

                    if ($row2 = $res2->fetch_assoc()) {
                        $img = $row2['screenshot'];
                        $url = "/build/" . urlencode($content);
                        $title = !empty($row2['name']) ? $row2['name'] : "[unknown]";
                        $post = "Favorited by";
                    }

                    $stmt2->close();
                    break;
                default:
                    $img = '/img/no_image.png';
                    $url = '';
                    $title = '[unknown]';
                    $post = 'Users:';
                endswitch;

            $time = time_ago(date("Y-m-d H:i:s", $group['timestamp']));
        ?>

        <article id="<?php echo $group_name ?>" class='w3-card-4 w3-hover-shadow gr8-theme w3-padding w3-round w3-large'>
            <div class="w3-row">
                <div class="w3-col s2 m3">
                    <img src="<?php echo htmlspecialchars($img); ?>" class="w3-round" style='background: #ddd; height: 150px;' alt='Image' title='Image'>
                </div>

                <div class="w3-col s6 m7">
                    <a href="<?php echo htmlspecialchars($url); ?>">
                        <strong><?php echo htmlspecialchars($title); ?></strong>
                    </a><br />
                    <?php echo htmlspecialchars($post); ?> <?php echo $user_string ?> 
                </div>

                <time class="w3-right-align w3-col m2"><?php echo htmlspecialchars($time); ?></time>
            </div>
        </article><br />

        <?php
        }
    }
}

if(isset($_GET['get'])) {
    header('Content-Type: application/json');
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $id = $current_user->id ?? 0;

    try {
        $notifications = new Notifications($conn);
        $sliced_notifications = $notifications->get_notifications($id, $page);
        echo json_encode($sliced_notifications);
        $conn->close();
        exit;
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['success' => false, 'message' => "An unknown error has occured. Please try again later."]);
    }
}