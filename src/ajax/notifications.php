<?php
class Notifications {
    public function __construct(private mysqli $db) {
        $this->db = $db;
    }

    public function subscribe(int $recipientId, string $category, int $contentId): void {
        $stmt = $this->db->prepare("INSERT IGNORE INTO subscriptions (userid, category, content) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $recipientId, $category, $contentId);
        $stmt->execute();
        $stmt->close();
    }

    public function notify_subscribers(string $category, int $contentId, int $actorId): void {
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

    public function remove_subscriber(string $category, int $contentId, int $recipientId): void {
        $stmt = $this->db->prepare("SELECT userid FROM subscriptions WHERE content = ? AND category = ? AND userid = ?");
        $stmt->bind_param("isi", $contentId, $category, $recipientId);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if($row) {
            $notif = $this->db->prepare("DELETE FROM subscriptions WHERE content = ? AND category = ? AND userid = ?");
            $notif->bind_param("isi", $contentId, $category, $recipientId);
            $notif->execute();

            $notif->close();
            $stmt->close();
        }
    }

    public function is_subscriber(string $category, int $contentId, int $recipientId): bool {
        $stmt = $this->db->prepare("SELECT userid FROM subscriptions WHERE content = ? AND category = ? AND userid = ?");
        $stmt->bind_param("isi", $contentId, $category, $recipientId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if($row) {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return false;
        }
    }
}
