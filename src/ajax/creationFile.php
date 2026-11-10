<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/errors.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/build.php';

if(isset($_POST['queryFile']) && isset($_POST['id'])) {
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Content-Type: application/json');

    $can_edit = false;

    if(!isset($_SESSION['viewer_auth'])) {
        $_SESSION['viewer_auth'] = bin2hex(random_bytes(32));
        http_response_code(301);
        exit;
    }

    if(isset($_POST['auth']) && hash_equals($_SESSION['viewer_auth'], $_POST['auth'])) {
        $can_edit = true;
    }

    $model_id = $_POST['id'];
    $cdata = json_decode(fetch_build($model_id, $_SESSION['csrf']), true);

    if (isset($cdata['success']) && $cdata['success'] === false) {
        echo json_encode(["success" => false, "message" => ($cdata['message'] ?? null)]);
        exit;
    } else {
        if(isset($cdata['can_edit']) && $cdata['can_edit'] !== true && $can_edit !== true) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => ErrorRegistry::get('creation_file', 'cannot_edit')]);
            exit;
        }

        //$raw_json = @file_get_contents('../' . $cdata['model']);
        if(!file_exists('../' . $cdata['model'])) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => ErrorRegistry::get('creation_file', 'json_not_found')]);
            exit;
        }

        $tmp_creation = tempnam(sys_get_temp_dir(), $model_id);
        if(@copy('../' . $cdata['model'], $tmp_creation) === false) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => ErrorRegistry::get('creation_file', 'tmp_copy_fail')]);
            exit;
        }

        $raw_array = @json_decode(file_get_contents($tmp_creation), true);
        if($raw_array === false) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => ErrorRegistry::get('creation', 'format_invalid')]);
            exit;
        }

        // white people be like yeah i don't update my servers json i just do it when requested
        if(isset($raw_array['metadata'])) {
            if(isset($raw_array['metadata']['name'])) {
                $raw_array['metadata']['name'] = $cdata['name'] ?? null;
            }

            if(isset($raw_array['metadata']['description'])) {
                $raw_array['metadata']['description'] = $cdata['description_raw'] ?? null;
            }

            if(isset($raw_array['metadata']['generator'])) {
                $raw_array['metadata']['generator'] = "Gr8Brik PHP server";
            }
        }

        $raw_array['resource'] = [
            'id' => $model_id,
            'url' => $tmp_creation,
        ];

        $_SESSION['viewer_auth'] = bin2hex(random_bytes(32));

        if (file_exists($tmp_creation)) {
            unlink($tmp_creation);
        }

        echo json_encode($raw_array);
        exit;
    }
}

if(isset($_GET['get_viewer_auth'])) {
    header('Content-Type: text/plain');

    if(!isset($_SESSION['viewer_auth'])) {
        $_SESSION['viewer_auth'] = bin2hex(random_bytes(32));
    }
    echo $_SESSION['viewer_auth'];
    exit;
}
?>