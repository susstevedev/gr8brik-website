<?php
    ini_set('display_errors', 1);
    ini_set('max_execution_time', 1000);
    header('Content-Type: application/json');
	
	include 'user.php';
	
	if(isset($_GET['list_v1'])) {
        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

        if(loggedin()) {
            $id = $_SESSION['userid'];
            $main_parts_array;

            $parts_stmt = $conn->prepare("SELECT * FROM parts WHERE userid = ?");
            $parts_stmt->bind_param("s", $id);
            $parts_stmt->execute();
            $parts = $parts_stmt->get_result();

            while ($row = $parts->fetch_assoc()) {
                $main_parts_array[] = $row;
            }

            echo json_encode($main_parts_array);
            exit;
        } else {
            echo json_encode(['error' => 'Not logged in. Please authenticate using proper channels to receive a list of all your custom parts.']);
        }
    }
?>