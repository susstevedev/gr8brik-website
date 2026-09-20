<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';

class ImgBB {
    public string $apikey;
    public mysqli $db;

    public function __construct(mysqli $db, string $apikey)
    {
        $this->apikey = $apikey;
        $this->db = $db;
    }

    //todo use this later
    //was going to use for attachment ids but i dont really see the use
    private function uuid(): string {
        $data = random_bytes(16);

        // set version to 4 (0100)
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // set variant to rfc 4122 (10xx)
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // format into the 8-4-4-4-12 format
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Uploads image to ImgBB server using cURL and inserts into attachments database
     * If exists, will pull from database
     */
    public function upload(array $image) {
        global $current_user;

        if(!loggedin()) {
            return ['success' => false, 'message' => 'Session expired or doesn\'t exist'];
        }

        if($current_user->verify_token !== NULL) {
            return ['success' => false, 'message' => 'Please verify your account to upload images'];
        }

        if (!file_exists($image['tmp_name'])) {
            return ['success' => false, 'message' => 'Image does not exist'];
        }

        $imgstr = imagecreatefromstring(file_get_contents($image["tmp_name"]));
        if (!$imgstr) {
            return ['success' => false, 'message' => 'Image not valid'];
        }

        $servername = $image['tmp_name'];
        $filename = $image['name'];
        $mimetype = $image['type'];
        $hash = md5_file($image["tmp_name"]);
        $exists = false;

        $res_exists = self::get_by_hash($hash);
        if ($res_exists) {
            if(isset($res_exists['success']) && isset($res_exists['image'])) {
                $exists = true;
            }
        }

        if(!$exists) {
            $url = "https://api.imgbb.com/1/upload?key=" . urlencode($this->apikey);
            $payload = ['image' => new CURLFile($servername, $mimetype, $filename)];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                curl_close($ch);
                return ['success' => false, 'message' => 'curl failed'];
            }
            curl_close($ch);

            $res = json_decode($response, true);
            $success = (bool)$res['success'] ? true : false;
            $error = $res['error']['message'] ?? '[unknown error]';

            if ($success) {
                $message = "Image uploaded";
                $url = $res['data']['url'] ?? $res['data']['display_url'];
                $mime = $res['data']['image']['mime'] ?? null;
                $user = $current_user->id ?? null;
                $username = $current_user->username ?? null;
                $bbcode = '[img]' . $url . '[/img]';
                $delete_url = $res['data']['delete_url'] ?? $res['data']['url_viewer'];

                $stmt = $this->db->prepare("INSERT INTO attachments (userid, url, bbcode, md5, mime, delete_url) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssss", $user, $url, $bbcode, $hash, $mime, $delete_url);

                if($stmt->execute()) {
                    $stmt->close();
                    $uuid = $this->db->insert_id;
                }
            }
        } else {
            $res = self::get_by_hash($hash);
            $success = (bool)$res['success'] ? true : false;
            $error = $res['message'] ?? '[unknown error]';
            $removed = (bool)$res['image']['removed'] ? true : false;

            if($success && !$removed) {
                $uuid = $res['id'];
                $message = "Same image already found, using that one";
                $url = $res['image']['url'] ?? null;
                $mime = $res['image']['mime'] ?? null;
                $user = $res['image']['user'] ?? null;
                $username = $current_user->username ?? null;
                $bbcode = '[img]' . $url . '[/img]';
                $delete_url = $res['image']['remove'];
            } else {
                $success = false;
                $error = "This image is blacklisted from being uploaded.";
            }
        }

        if (isset($success) && $success === true) {
            return [
                'success' => true,
                'message' => $message ?? null,
                'id' => $uuid,
                'insert' => '[attachment]' . $uuid . '[/attachment]',
                'image' => [
                    'user' => $username,
                    'mime' => $mime,
                    'url' => $url,
                    'bbcode' => $bbcode,
                    'remove' => $delete_url
                ]
            ];
        } else {
            return ['success' => false, 'message' => $error ?? '[unknown error]'];
        }
    }

    /**
     * Returns an attachment from it's id
     */
    public function get(int $id) {
        $stmt = $this->db->prepare("SELECT * FROM attachments WHERE id = ? AND is_deleted = 0 LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result();
        if($result->num_rows <= 0) {
            return ['success' => false, 'message' => 'Image does not exist'];
        }

        $row = $result->fetch_assoc();

        if (isset($row)) {
            $stmt->close();

            return self::make_array($row);
        } else {
            return ['success' => false];
        }
    }

    /**
     * Returns an attachment from it's md5 hash
     */
    public function get_by_hash(string $md5) {
        $stmt = $this->db->prepare("SELECT * FROM attachments WHERE md5 = ? LIMIT 1");
        $stmt->bind_param("s", $md5);
        $stmt->execute();

        $result = $stmt->get_result();
        if($result->num_rows <= 0) {
            return ['success' => false, 'message' => 'Image does not exist'];
        }

        $row = $result->fetch_assoc();

        if (isset($row) && !empty($row['md5'])) {
            $stmt->close();

            return self::make_array($row);
        } else {
            return ['success' => false];
        }
    }

    /**
     * Turns raw DB row into custom array
     */
    private function make_array(?array $row) {
        global $current_user;

        if (isset($row)) {
            $me = $current_user->id ?? null;
            $userid = $row['userid'] ?? 0;
            $usero = User::getUser($userid);
            $username = $usero->username ?? null;
            $delete_url = null;

            if($usero->id === 0 || $usero->deactive !== null || $usero->private_profile !== false) {
                $username = null;
            }

            if(loggedin()) {
                if(trim($me) === trim($userid)) {
                    $delete_url = $row['delete_url'] ?? null;
                }
            }

            return [
                'success' => true,
                'message' => 'Image found',
                'id' => $row['id'] ?? null,
                'image' => [
                    'user' => $username,
                    'mime' => $row['mime'] ?? null,
                    'url' => $row['url'] ?? null,
                    'bbcode' => $row['bbcode'] ?? null,
                    'remove' => $delete_url,
                    'timestamp' => $row['timestamp'] ?? null,
                    'removed' => (bool)$row['is_deleted'] ?? false,
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Image does not exist'];
        }
    }
}
?>