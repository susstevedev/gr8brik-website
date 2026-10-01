<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/build.php';

$raw_inp = file_get_contents('php://input');
$data = json_decode($raw_inp, true);

if(isset($data)) {
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "invalid json"]);
        exit;
    }

    if(isset($data['iwantthejsonbruh']) && isset($data['creID'])) {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "use POST please"]);
            exit;
        }

        $is_viewer = isset($data['viewer']) ? true : false;
        $can_edit = false;

        if(!isset($_SESSION['viewer_auth'])) {
            $_SESSION['viewer_auth'] = uuid();
            http_response_code(301);
            echo json_encode(["success" => false, "message" => "Try again please"]);
            exit;
        }

        if($is_viewer && $data['viewer'] === $_SESSION['viewer_auth']) {
            $can_edit = true;
        }

        $model_id = $data['creID'];
        $cdata = json_decode(fetch_build($model_id, $_SESSION['csrf']), true);

        if ($cdata['message']) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => $cdata['message']]);
            exit;
        } else {
            if(isset($cdata['can_edit']) && $can_edit !== true) {
                if($cdata['can_edit'] !== true) {
                    http_response_code(401);
                    echo json_encode(["success" => false, "message" => "You are not allowed to edit this creation. Please contact the creator via direct messages."]);
                    exit;
                }
                $can_edit = $cdata['can_edit'];
            }

            $raw_json = @file_get_contents('../' . $cdata['model']);
            if($raw_json === false) {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "There is no valid JSON file for this creation."]);
                exit;
            }

            $raw_array = @json_decode($raw_json, true);
            if($raw_array === false) {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "There is no valid JSON file for this creation."]);
                exit;
            }

            // white people be like yeah i don't update my servers json i just do it when requested
            if(isset($raw_array['metadata'])) {
                if(isset($raw_array['metadata']['name'])) {
                    $raw_array['metadata']['name'] = $cdata['name'];
                }

                if(isset($raw_array['metadata']['description'])) {
                    $raw_array['metadata']['description'] = $cdata['description'];
                }

                if(isset($raw_array['metadata']['generator'])) {
                    $raw_array['metadata']['generator'] = "Gr8Brik PHP server";
                }
            }

            $res = ["success" => true, "message" => "JSON file found", "creation" => $raw_array];
            echo json_encode($res);
            exit;
        }
    }

    if(isset($data['getViewerSess'])) {
        header('Content-Type: text/plain');

        if(!isset($_SESSION['viewer_auth'])) {
            $_SESSION['viewer_auth'] = uuid();
        }
        echo $_SESSION['viewer_auth'];
        exit;
    }
}

function uuid(): string {
    $data = random_bytes(16);

    // set version to 4 (0100)
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // set variant to rfc 4122 (10xx)
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // format into the 8-4-4-4-12 format
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
?>