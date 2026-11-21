<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/mysql.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/errors.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/const/creation.php';

if(isset($_POST['queryFile']) && isset($_POST['id'])) {
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Content-Type: application/json');

    $conn = Database::get(DB_NAME2);
    $public_edit = false;
    $model_id = $_POST['id'];

    $public_edit = isset($_POST['auth'], $_SESSION['viewer']['auth'], $_SESSION['viewer']['creId'])
        && hash_equals($_SESSION['viewer']['auth'], $_POST['auth'])
        && hash_equals($_SESSION['viewer']['creId'], $model_id);

    $stmt = $conn->prepare
    ("SELECT m.model, m.name, m.description, m.can_edit, m.visibility, m.removed, u.id AS user_id, u.username, p.picture AS user_pic
    FROM model m
    INNER JOIN users u ON u.id = m.user AND u.deactive IS NULL
    LEFT JOIN user_profiles p ON p.userid = m.user
    WHERE m.id = ?
    AND removed = 0");

    $stmt->execute([$model_id]);
    $row = $stmt->get_result()->fetch_assoc();
    $is_admin = (isset($current_user->admin) && $current_user->admin === true);

    if (!$row || ($row['removed'] && !$is_admin)) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => ErrorRegistry::get('creation', 'not_found')]);
        exit;
    }

    $is_owner = (isset($current_user->id) && trim($current_user->id) === trim($row['user_id']));

    if ($row['visibility'] === 'private' && !$is_admin && !$is_owner) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => ErrorRegistry::get('creation_file', 'cannot_edit')]);
        exit;
    }

    if(!$row['can_edit'] && !$public_edit && !$is_admin && !$is_owner) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => ErrorRegistry::get('creation_file', 'cannot_edit')]);
        exit;
    }

    $file_path = '../' . $row['model'];

    if(!file_exists($file_path)) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => ErrorRegistry::get('creation_file', 'not_found')]);
        exit;
    }

    $raw_array = @json_decode(file_get_contents($file_path), true);
    if($raw_array === false) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => ErrorRegistry::get('creation', 'format_invalid')]);
        exit;
    }

    // white people be like yeah i don't update my servers json i just do it when requested
    if(isset($raw_array['metadata'])) {
        if(isset($raw_array['metadata']['name'])) {
            $raw_array['metadata']['name'] = $row['name'] ?? null;
        }

        if(isset($raw_array['metadata']['description'])) {
            $raw_array['metadata']['description'] = $row['description'] ?? null;
        }

        if(isset($raw_array['metadata']['generator'])) {
            $raw_array['metadata']['generator'] = "Gr8Brik PHP server";
        }
    }

    $raw_array['resource']['db'] = $row;

    view_creation($model_id);
    echo json_encode($raw_array);
    exit;
}


if(isset($_GET['auth']) && isset($_GET['creId'])) {
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Content-Type: application/json');

    $_SESSION['viewer']['auth'] = bin2hex(random_bytes(32));
    $_SESSION['viewer']['creId'] = $_GET['creId'];

    echo json_encode([
        'auth' => $_SESSION['viewer']['auth'],
        'creId' => $_GET['creId']
    ]);
    exit;
}
?>