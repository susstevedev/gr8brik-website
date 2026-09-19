<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/imgbb.php';

$cooldb = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
$ImgBBO = new ImgBB($cooldb, IMGBB_API_KEY);

if(isset($_POST['upload']) && isset($_FILES['imagefile'])) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "use POST please"]);
        exit;
    }

    $image = $_FILES['imagefile'];

    $res = $ImgBBO->upload($image);
    echo json_encode($res);
    exit;
}

if(isset($_GET['imgbb_image']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $res = $ImgBBO->get($id);

    if(isset($res['success'])) {
        $url = $res['image']['url'] ?? null;
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($ch);

        if (!curl_errno($ch) && curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            header("Content-Type: $contentType");
            echo $data;
        } else {
            http_response_code(404);
            header("Content-Type: image/png");
            echo @file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/img/notavaliable.png');
        }

        curl_close($ch);
    } else {
        http_response_code(404);
        echo @file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/img/notavaliable.png');
    }
    exit;
}

header('Content-Type: application/json');
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "invalid json"]);
    exit;
}

if(isset($data['image'])) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "use POST please"]);
        exit;
    }

    $id = $data['id'];
    $res = $ImgBBO->get($id);
    echo json_encode($res);
    exit;
}
?>