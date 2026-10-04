<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/config.php';

class Cleaner {
    private mysqli $_conn;
    private mysqli $_conn2;
    private mysqli $_conn3;

    public function __construct(mysqli $_conn, mysqli $_conn2, mysqli $_conn3) {
        $this->_conn = $_conn;
        $this->_conn2 = $_conn2;
        $this->_conn3 = $_conn3;
    }

    public function delete_old_sessions() {
        if (loggedin()) {
            $expiry_short = time() - (60 * 60 * 24 * 1);//1 day
            $expiry_long = time() - (60 * 60 * 24 * 15);//15 day
            
            $old_sessions_stmt = $this->_conn->prepare("DELETE FROM sessions WHERE timestamp < ? AND remember = 0");
            $old_sessions_stmt->bind_param("i", $expiry_short);
            $old_sessions_stmt->execute();
            $old_sessions_stmt->close();
            
            $old_sessions_stmt = $this->_conn->prepare("DELETE FROM sessions WHERE timestamp < ? AND remember = 1");
            $old_sessions_stmt->bind_param("i", $expiry_long);
            $old_sessions_stmt->execute();
            $old_sessions_stmt->close();
            return true;
        }
        return false;
    }

    public function delete_inactive_users($userid = null, $blacklist_email = false, $blacklist_username = false) {
        $user_ids = [];
        $user_pics = [];
        $existing_ban = false;

        if ($userid !== null) {
            $user_ids[] = (int)$userid;
            $existing_ban = User::isBannedByID($userid);
        } else {
            $stmt = $this->_conn->prepare(
                "SELECT
                    u.id,
                    p.picture,
                    p.picture_small
                FROM users u
                LEFT JOIN user_profiles p
                    ON u.id = p.userid
                WHERE u.deactive IS NOT NULL 
                AND STR_TO_DATE(u.deactive, '%Y-%m-%d %H:%i:%s') < NOW() - INTERVAL 14 DAY 
                LIMIT 20"
            );

            if (!$stmt || !$stmt->execute()) {
                echo "<div class='w3-light-grey w3-border w3-center w3-border-grey w3-round w3-card-2'>Could not query users</div>";
                return false;
            }

            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $user_ids[] = (int)$row['id'];

                if (!empty($row['picture']) || !empty($row['picture_small'])) {
                    $inactive_user_pics[] = [
                        'medium' => !empty($row['picture']) ? $row['picture'] : null,
                        'small'  => !empty($row['picture_small']) ? $row['picture_small'] : null
                    ];
                }
            }
            $stmt->close();
        }

        if (empty($user_ids)) {
            return true;
        }

        $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
        $types = str_repeat('i', count($user_ids));
        $usernames_blacklist = [];
        $emails_blacklist = [];

        $stmt_names = $this->_conn->prepare("SELECT username, email FROM users WHERE id IN ($placeholders)");
        if ($stmt_names) {
            $stmt_names->bind_param($types, ...$user_ids);
            $stmt_names->execute();
            $res_names = $stmt_names->get_result();

            while ($row = $res_names->fetch_assoc()) {
                if ($blacklist_username && !empty($row['username'])) {
                    $usernames_blacklist[] = $row['username'];
                }

                if (($blacklist_email || $existing_ban) && !empty($row['email'])) {
                    $emails_blacklist[] = hash('sha256', strtolower(trim($row['email'])));
                }
            }

            $stmt_names->close();
        }

        if (!empty($usernames_blacklist)) {
            $row_placeholders = implode(',', array_fill(0, count($usernames_blacklist), "(?, 'username', 'auto deleted user account')"));
            $name_types = str_repeat('s', count($usernames_blacklist));
            $sql_bl = "INSERT IGNORE INTO blacklist (value, type, reason) VALUES $row_placeholders";

            if ($stmt_bl = $this->_conn->prepare($sql_bl)) {
                $stmt_bl->bind_param($name_types, ...$usernames_blacklist);
                $stmt_bl->execute();
                $stmt_bl->close();
            }
        }

        if (!empty($emails_blacklist)) {
            $email_placeholders = implode(',', array_fill(0, count($emails_blacklist), "(?, 'email', 'auto banned by admin request')"));
            $email_types = str_repeat('s', count($emails_blacklist));
            $sql_el = "INSERT IGNORE INTO blacklist (value, type, reason) VALUES $email_placeholders";

            if ($stmt_el = $this->_conn->prepare($sql_el)) {
                $stmt_el->bind_param($email_types, ...$emails_blacklist);
                $stmt_el->execute();
                $stmt_el->close();
            }
        }

        function bulk($db, $sql, $types, $ids) {
            if ($stmt = $db->prepare($sql)) {
                $stmt->bind_param($types, ...$ids);
                if (!$stmt->execute()) {
                    throw new Exception($stmt->error);
                }
                $stmt->close();
            } else {
                throw new Exception($db->error);
            }
        };

        $this->_conn->begin_transaction();
        $this->_conn2->begin_transaction();
        $this->_conn3->begin_transaction();

        try {
            //forum
            bulk($this->_conn3, "UPDATE messages SET userid = 0 WHERE userid IN ($placeholders)", $types, $user_ids);

            //creations
            bulk($this->_conn2, "UPDATE model SET user = 0 WHERE user IN ($placeholders)", $types, $user_ids);
            bulk($this->_conn2, "UPDATE parts SET userid = 0 WHERE userid IN ($placeholders)", $types, $user_ids);

            //comments
            bulk($this->_conn2, "DELETE FROM comment_votes WHERE user_id IN ($placeholders)", $types, $user_ids);
            bulk($this->_conn2, "DELETE FROM comment_votes WHERE comment_id IN (SELECT id FROM comments WHERE hidden = 1 AND user IN ($placeholders))", $types, $user_ids);
            bulk($this->_conn2, "UPDATE comments SET user = 0 WHERE hidden = 0 AND user IN ($placeholders)", $types, $user_ids);
            bulk($this->_conn2, "DELETE FROM comments WHERE hidden = 1 AND user IN ($placeholders)", $types, $user_ids);

            //user interactions
            bulk($this->_conn2, "DELETE FROM votes WHERE user IN ($placeholders)", $types, $user_ids);
            bulk($this->_conn, "DELETE FROM attachments WHERE is_deleted = 0 AND userid IN ($placeholders)", $types, $user_ids);
            bulk($this->_conn, "UPDATE attachments SET userid = 0 WHERE is_deleted = 1 AND userid IN ($placeholders)", $types, $user_ids);
            bulk($this->_conn, "DELETE FROM follow WHERE userid IN ($placeholders) OR profileid IN ($placeholders)", $types . $types, array_merge($user_ids, $user_ids));
            bulk($this->_conn, "DELETE FROM user_blocks WHERE userid IN ($placeholders) OR profileid IN ($placeholders)", $types . $types, array_merge($user_ids, $user_ids));
            bulk($this->_conn, "DELETE FROM notifications WHERE user IN ($placeholders) OR profile IN ($placeholders)", $types . $types, array_merge($user_ids, $user_ids));
            bulk($this->_conn, "DELETE FROM subscriptions WHERE userid IN ($placeholders)", $types, $user_ids);

            //legacy
            bulk($this->_conn, "DELETE FROM bans WHERE user IN ($placeholders)", $types, $user_ids);
            bulk($this->_conn2, "DELETE FROM reported WHERE user IN ($placeholders)", $types, $user_ids);

            //mod records
            bulk($this->_conn, "DELETE FROM appeals WHERE user IN ($placeholders)", $types, $user_ids);
            bulk($this->_conn2, "DELETE FROM reports WHERE reporter_user_id IN ($placeholders)", $types, $user_ids);
            bulk($this->_conn2, "DELETE FROM reports WHERE reportable_type = 'profile' AND reportable_id IN ($placeholders)", $types, $user_ids);
            bulk($this->_conn, "DELETE FROM analytics WHERE my_user IN ($placeholders) OR their_user IN ($placeholders)", $types . $types, array_merge($user_ids, $user_ids));

            //dms
            bulk($this->_conn, "UPDATE direct_message SET userid = 0 WHERE userid IN ($placeholders)", $types, $user_ids);
            bulk($this->_conn, "DELETE FROM message_users WHERE userid IN ($placeholders)", $types, $user_ids);

            //the actual user
            if($existing_ban) {
                bulk($this->_conn, "DELETE FROM blacklist WHERE type = 'userid' AND value IN ($placeholders)", $types, $user_ids);
            }
            bulk($this->_conn, "DELETE FROM user_profiles WHERE userid IN ($placeholders)", $types, $user_ids);
            bulk($this->_conn, "DELETE FROM users WHERE id IN ($placeholders)", $types, $user_ids);
            bulk($this->_conn, "DELETE FROM sessions WHERE user IN ($placeholders)", $types, $user_ids);
            bulk($this->_conn, "DELETE FROM php_sessions WHERE userid IN ($placeholders)", $types, $user_ids);

            $this->_conn->commit();
            $this->_conn2->commit();
            $this->_conn3->commit();

            if (!empty($user_pics)) {
                foreach ($user_pics as $picture_group) {
                    foreach ($picture_group as $path) {
                        if (!empty($path) && file_exists($path) && is_file($path)) {
                            unlink($path);
                        }
                    }
                }
            }
        } catch (Exception) {
            $this->_conn->rollback();
            $this->_conn2->rollback();
            $this->_conn3->rollback();

            echo "<div class='w3-light-grey w3-border w3-center w3-border-grey w3-round w3-card-2'>The cleanup of deleted users has failed</div>";
            return false;
        }

        return true;
    }
}
?>