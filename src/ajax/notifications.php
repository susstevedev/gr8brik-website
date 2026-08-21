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

    public function get_notifications(int $userId, int $page)
    {
        if (!loggedin()) {
            return;
        }

        if ($this->db->connect_error) {
            return $this->db->connect_error;
        }

        if ($page < 1) {
            $page = 1;
        }

        $limit = 8;
        $offset = ($page - 1) * $limit;

        $sql = "SELECT * FROM notifications WHERE user = ? AND category2 IS NOT NULL ORDER BY timestamp DESC";

        $usero = User::getUser($userId);
        if (User::isDeleted($userId)) {
            return;
        }

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
            $profile = $row['profile'];
            $content = $row['content'];
            $category = $row['category2'];
            $timestamp = is_numeric($row['timestamp']) ? (int)$row['timestamp'] : time();

            $user_data_o = User::getUser($profile);
            $username = htmlspecialchars($user_data_o->username) ?: '[unknown]';
            $userid = $user_data_o->id ?: 0;

            //groups matching content together
            if ($category === 'profile') {
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

        $total_groups = count($grouped_notifications);
        $sliced_notifications = array_slice($grouped_notifications, $offset, $limit);

        /*if ($page > 1) {
            echo '<a class="w3-btn w3-blue w3-hover-opacity w3-round w3-border w3-border-indigo" href="?page=' . $page - 1 . '">Back</a>&nbsp;&nbsp;';
        }

        if (($offset + $limit) < $total_groups) {
            echo '<a class="w3-btn w3-blue w3-hover-opacity w3-round w3-border w3-border-indigo" href="?page=' . $page + 1 . '">Next</a>';
        }*/

        $stmt->close();
        return $sliced_notifications;
    }
}