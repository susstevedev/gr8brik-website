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

        $filename = $image['name'];
        $mimetype = $image['type'];
        $hash = md5_file($image["tmp_name"]);
        $success = false;

        $res_exists = self::get_by_hash($hash);
        if ($res_exists) {
            if(isset($res_exists['success']) && $res_exists['success']) {
                $success = (bool)$res_exists['success'] ? true : false;
                $error = $res_exists['message'] ?? '[unknown error]';
                $removed = (bool)$res_exists['image']['removed'] ? true : false;

                if($success && !$removed) {
                    $final = [
                        'uuid' => $res_exists['id'],
                        'message' => "Same image already found, using that one",
                        'url' => $res_exists['image']['url'] ?? null,
                        'filename' => $res_exists['name'] ?? null,
                        'mime' => $res_exists['image']['mime'] ?? null,
                        'user' => $res_exists['image']['userid'] ?? null,
                        'bbcode' => $res_exists['image']['bbcode'] ?? null,
                        'markdown' => $res_exists['image']['markdown'] ?? null,
                        'delete_url' => $res_exists['image']['remove'] ?? null,
                    ];
                } else if($removed) {
                    $success = false;
                    $error = "This image is blacklisted from being uploaded.";
                } else {
                    $success = false;
                    $error = "Couldn't upload this image right now.";
                }
            } else {
                $url = "https://api.imgbb.com/1/upload?key=" . urlencode($this->apikey);
                $payload = ['image' => new CURLFile($image['tmp_name'], $mimetype, $filename)];

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                
                if (curl_errno($ch)) {
                    $error = curl_errno($ch);
                    curl_close($ch);
                    return ['success' => false, 'message' => 'curl failed', 'oh' => ['curl' => $error, 'res' => $response]];
                }
                curl_close($ch);

                $res = json_decode($response, true);
                $success = (bool)$res['success'] ? true : false;
                $error = $res['error']['message'] ?? '[unknown error]';

                if ($success) {
                    $url = $res['data']['url'] ?? $res['data']['display_url'];
                    $final = [
                        'message' => "Image uploaded",
                        'url' => $url,
                        'filename' => $res['data']['image']['filename'],
                        'mime' => $res['data']['image']['mime'] ?? null,
                        'user' => $current_user->id ?? null,
                        'bbcode' => '[img]' . $url . '[/img]',
                        'markdown' => '![' . htmlspecialchars($res['data']['image']['filename']) . '](' . $url . ')',
                        'delete_url' => $res['data']['delete_url'] ?? $res['data']['url_viewer'],
                    ];

                    $stmt = $this->db->prepare("INSERT INTO attachments (userid, url, md5, mime, delete_url, name) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("isssss", $final['user'], $final['url'], $hash, $final['mime'], $final['delete_url'], $final['filename']);

                    if($stmt->execute()) {
                        $stmt->close();
                        $final['uuid'] = $this->db->insert_id;
                    }
                }
            }
        }

        if (isset($final) && $success === true) {
            return [
                'success' => true,
                'message' => $final['message'] ?? null,
                'id' => $final['uuid'],
                'name' => $final['filename'],
                //'insert' => '[attachment]' . $uuid . '[/attachment]',
                'insert' =>  '![' . htmlspecialchars($final['filename']) . '](/ajax/image?imgbb_image=true&id=' . $final['uuid'] . ')',
                'image' => [
                    'user' => User::getUser($final['user'])->username ?? null,
                    'mime' => $final['mime'],
                    'url' => $final['url'],
                    'bbcode' => $final['bbcode'],
                    'markdown' => $final['markdown'],
                    'remove' => $final['delete_url']
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
        if(empty($md5)) {
            return ['success' => false, 'message' => 'Hash is null'];
        }

        $stmt = $this->db->prepare("SELECT * FROM attachments WHERE md5 = ? LIMIT 1");
        $stmt->bind_param("s", $md5);
        $stmt->execute();

        $result = $stmt->get_result();
        if($result->num_rows === 0) {
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
            $url = $row['url'] ?? null;
            $filename = htmlspecialchars($row['name']) ?? null;
            $markdown = '![' . $filename . '](' . $url . ')';
            $bbcode = '[img]' . $url . '[/img]';

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            if (!curl_errno($ch)) {
                if(loggedin()) {
                    if(trim($me) === trim($userid)) {
                        $delete_url = $row['delete_url'] ?? null;
                    }
                }

                return [
                    'success' => true,
                    'message' => 'Image found',
                    'id' => $row['id'] ?? null,
                    'name' => $filename ?? null,
                    'image' => [
                        'user' => $username,
                        'userid' => $userid,
                        'mime' => $row['mime'] ?? null,
                        'url' => $url,
                        'bbcode' => $bbcode,
                        'markdown' => $markdown,
                        'remove' => $delete_url,
                        'timestamp' => $row['timestamp'] ?? null,
                        'removed' => (bool)$row['is_deleted'] ?? false,
                    ]
                ];
            }
            curl_close($ch);
        }
        return ['success' => false, 'message' => 'Image does not exist'];
    }
}
?>