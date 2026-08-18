<?php
    ini_set('display_errors', 1);
    ini_set('max_execution_time', 1000);
    header('Content-Type: application/json');

	require_once 'user.php';

    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

    if(!loggedin()) {
        echo json_encode(['error' => true, 'code' => "LOGGED_OUT"]); //invalid session or unset session
        exit;
    }

    $id = $current_user->id ?? 0;
    $parts_arr = [];

    if(User::isDeleted($id)) {
        echo json_encode(['error' => true, 'code' => "INV_LOGIN"]); //invalid login
        exit;
    }

    if(!User::isVerified()) {
        echo json_encode(['error' => true, 'code' => "USR_NOT_VERIFY"]); //invalid login
        exit;
    }

    $parts_stmt = $conn->prepare("SELECT id, name, part, reference, texture FROM parts WHERE userid = ?");
    $parts_stmt->bind_param("s", $id);
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
?>