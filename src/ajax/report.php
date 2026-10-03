<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';

if (isset($_POST['reportv2'])) {
    header('Content-Type: application/json');

    if ($_SESSION['csrf'] === $_POST['csrf_token']) {
        if (loggedin() && isset($current_user)) {
            $id = $current_user->id;
            $reportable_id = (int)$_POST['reportable_id'];

            $type = isset($_POST['report_type']) ? trim($_POST['report_type']) : null;
            $desc = isset($_POST['other']) ? trim($_POST['other']) : null;
            $reason = isset($_POST['reason']) ? trim($_POST['reason']) : null;

            $conn = Database::get(DB_NAME2);
            if (!$conn) {
                echo json_encode(['error' => 'Database connection failed.']);
                exit;
            }

            if (!isset($type) || empty($type)) {
                echo json_encode(['error' => 'No report type provided']);
                exit;
            }

            if (!isset($reason) || empty($reason)) {
                echo json_encode(['error' => 'No reason provided']);
                exit;
            }

            $stmt_check = $conn->prepare("SELECT * FROM reports WHERE reporter_user_id = ? AND reportable_id = ? AND reportable_type = ?");
            $stmt_check->bind_param("iis", $id, $reportable_id, $type);
            $stmt_check->execute();
            $result = $stmt_check->get_result();

            if($result->num_rows !== 0) {
                echo json_encode(['error' => 'You have already reported this content.']);
                exit;
            }

            if ($reason === 'other' && empty($desc)) {
                echo json_encode(['error' => 'Please fill in the description box to explain your report.']);
                exit;
            }

            if ($desc !== null) {
                if(strlen($desc) > 500) {
                    echo json_encode(['error' => 'Description shall be under 500 characters.']);
                    exit;
                }
            }

            $stmt = $conn->prepare("INSERT INTO reports (reportable_id, reportable_type, reason, description, reporter_user_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", $reportable_id, $type, $reason, $desc, $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => 'Content reported! Thanks for making our platform a safe space for everyone!']);
            } else {
                echo json_encode(['error' => 'Oops! We couldn\'t report the submitted content at this moment. Please try again later.']);
            }

            $stmt->close();
            $conn->close();
        } else {
            echo json_encode(['error' => 'Oops! Please login to report content.']);
        }
    } else {
        echo json_encode(['error' => 'Oops! Your CSRF token seems to be invalid.']);
    }
    exit;
}
?>