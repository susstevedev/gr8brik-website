<?php
    ini_set('display_errors', 1);
    ini_set('max_execution_time', 1000);
    header('Content-Type: application/json');

	require_once 'user.php';
    require_once 'imgbb.php';
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

    class CustomParts {
        public mysqli $db;
        public int $userid;
        public mysqli $membership_db;
        public string $imgbb_api_key;

        public function __construct($db, $membership_db, $imgbb_api_key) {
            $this->db = $db;
            $this->membership_db = $membership_db;
            $this->imgbb_api_key = $imgbb_api_key;
        }

        public function upload(array $data) {
            global $current_user;

            if(!loggedin()) {
                return ['success' => false, 'message' => "Please login to create new custom parts."];
            }

            $id = $current_user->id ?? 0;

            if(!isset($current_user) || User::isDeleted($id)) {
                return ['success' => false, 'message' => "Invalid login."];
            }

            if($current_user->verify_token !== null) {
                return ['success' => false, 'message' => "Please verify your account to upload custom parts."];
            }

            $message = "Part uploaded";
            $username = $current_user->username ?? null;
            $reference = trim($data['reference'] ??= uniqid());
            $name = $data['name'] ??= 'Unnamed Part';

            $find_stmt = $this->db->prepare("SELECT id FROM parts WHERE reference = ? LIMIT 1");
            $find_stmt->bind_param("s", $reference);
            $find_stmt->execute();
            $parts = $find_stmt->get_result();
            $row = $parts->fetch_assoc();

            if($row) {
                return ['success' => false, 'message' => "Reference has been taken"];
            }

            if(!isset($data['ldraw'])) {
                return ['success' => false, 'message' => "LDraw part ID is missing from request."];
            }

            if(!isset($data['matrix']) || !isset($data['matrix']['world']) || !isset($data['matrix']['local']) || !isset($data['matrix']['size'])) {
                return ['success' => false, 'message' => "Matrix or children is missing from request."];
            }

            if(!isset($data['texture']) || !isset($data['texture']['url'])) {
                return ['success' => false, 'message' => "Texture URL is missing from request."];
            }

            $matrix_world = json_encode($data['matrix']['world']);
            $matrix_local = json_encode($data['matrix']['local']);
            $matrix_size = json_encode($data['matrix']['size']);

            if (preg_match('/^data:([^;]+);base64,(.*)$/', $data['texture']['url'], $matches)) {
                $mimeType = $matches[1];
                $base64Data = $matches[2];
            } else {
                return ['success' => false, 'message' => "Invalid base64 image."];
            }

            $decodedData = base64_decode($base64Data);
            $tmpFilePath = tempnam(sys_get_temp_dir(), 'b64');
            file_put_contents($tmpFilePath, $decodedData);

            $extensions = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webP' => 'webp'
            ];
            $ext = $extensions[$mimeType] ?? 'bin';

            $imgbb_image = [
                'name' => $reference . '.' . $ext,
                'type' => $mimeType,
                'tmp_name' => $tmpFilePath,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($tmpFilePath),
            ];

            $ImgBBO = new ImgBB($this->membership_db, $this->imgbb_api_key);
            $imgbb_res = $ImgBBO->upload($imgbb_image);

            if(!$imgbb_res || $imgbb_res['success'] !== true || !$imgbb_res['image'] || !$imgbb_res['image']['url']) {
                return ['success' => false, 'message' => "Failed to upload image to ImgBB"];
            }

            $stmt = $this->db->prepare("INSERT INTO parts (userid, part, texture, reference, name, matrix_local, matrix_world, size) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssss", $id, $data['ldraw'], $imgbb_res['image']['url'], $reference, $name, $matrix_local, $matrix_world, $matrix_size);

            if($stmt->execute()) {
                $partid = $this->db->insert_id;

                $stmt->close();
                return [
                    'success' => true,
                    'message' => $message,
                    'id' => $partid,
                    'part' => [
                        'user' => $username,
                        'reference' => $reference,
                        'name' => $name,
                        'ldraw' => $data['ldraw'],
                        'texture' => [
                            'url' => $imgbb_res['image']['url']
                        ],
                        'matrix' => [
                            'world' => $data['matrix']['world'],
                            'local' => $data['matrix']['local'],
                            'size' => $data['matrix']['size'],
                        ]
                    ]
                ];
            } else {
                return ['success' => false, 'message' => "Couldn\'t create this part"];
            }
        }

        public function get(int $id) {
            $parts_stmt = $this->db->prepare("SELECT * FROM parts WHERE id = ?");
            $parts_stmt->bind_param("i", $id);
            $parts_stmt->execute();
            $parts = $parts_stmt->get_result();
            $row = $parts->fetch_assoc();

            if($row) {
                $userid = $row['userid'];
                $usero = User::getUser($userid);

                if(!isset($usero) || User::isDeleted($userid)) {
                    return ['success' => false, 'message' => "Invalid user for custom part."];
                }

                $username = $usero->username ?? null;

                return [
                    'success' => true,
                    'message' => 'Part found',
                    'id' => $row['id'],
                    'part' => [
                        'user' => $username,
                        'reference' => $row['reference'] ?? null,
                        'name' => $row['name'] ?? null,
                        'ldraw' => $row['part'] ?? null,
                        'texture' => [
                            'url' => $row['texture'] ?? null,
                        ],
                        'matrix' => [
                            'world' => json_decode($row['matrix_world']) ?? null,
                            'local' => json_decode($row['matrix_local']) ?? null,
                            'size' => json_decode($row['size']) ?? null,
                        ]
                    ]
                ];
            } else {
                return ['success' => false, 'message' => "Part not found in database."];
            }
        }
    }

    $cooldb = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
    $membership_db = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    $customparts = new CustomParts($cooldb, $membership_db, IMGBB_API_KEY);

    if(isset($_GET['fetch'])) {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "use GET please"]);
            exit;
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $res = $customparts->get($id);
        echo json_encode($res);
        exit;
    }

    $raw_input = file_get_contents('php://input');
    $data = json_decode($raw_input, true);

    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "invalid json"]);
        exit;
    }

    if(isset($data['upload'])) {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "use PUT please"]);
            exit;
        }

        $res = $customparts->upload($data);
        echo json_encode($res);
        exit;
    }

    if(isset($data['all'])) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "use POST please"]);
            exit;
        }

        if(!loggedin()) {
            echo json_encode(['error' => true, 'code' => "LOGGED_OUT"]); //invalid session or unset session
            exit;
        }

        $id = $current_user->id ?? 0;
        $token = $current_user->verify_token ?? null;
        $parts_arr = [];

        if(User::isDeleted($id)) {
            echo json_encode(['error' => true, 'code' => "INV_LOGIN"]); //invalid login
            exit;
        }

        if($token !== null) {
            echo json_encode(['error' => true, 'code' => "USR_NOT_VERIFY"]); //invalid login
            exit;
        }

        $parts_stmt = $conn->prepare("SELECT id, name, part, reference, texture FROM parts WHERE userid = ? ORDER BY id DESC");
        $parts_stmt->bind_param("i", $id);
        $parts_stmt->execute();
        $parts = $parts_stmt->get_result();

        while ($row = $parts->fetch_assoc()) {
            $parts_arr[] = $row;
        }

        if(empty($parts_arr)) {
            echo json_encode(['error' => true, 'code' => "NO_PARTS"]);
            exit;
        }

        echo json_encode($parts_arr);
        exit;
    }
?>