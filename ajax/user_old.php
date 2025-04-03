<?php
require_once 'constants.php';
function fetchUser($userId) {

    $error = false;
	
	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
	if ($conn->connect_error) {
		exit($conn->connect_error);
	}
			
    $sql = "SELECT * FROM users WHERE id = $userId";
    $result = $conn->query($sql);

    $result2 = $conn->query("SELECT * FROM bans WHERE user = $userId");
    $row2 = $result2->fetch_assoc();

    if($row2['end_date'] >= time()) {
        $error = true;
    }

    // return user
    $user = [];
    while ($row = $result->fetch_assoc()) {
		$user = $row;
		break;
	}
	
	$user['username'] = isset($user['username']) ? $user['username'] : '';
	
	$user['image'] = file_exists("../acc/users/pfps/{$userId}.jpg") ? "../acc/users/pfps/{$userId}.jpg" : "../img/avatar.png";

    if(empty($row['username'])) {
        $error = true;
    }

    if($error === true) {
        http_response_code(403);
        echo "An error occured";
        exit;
    }

	return $user;
}
?>