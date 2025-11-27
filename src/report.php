<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';

if(isset($_POST['report_1'])) {
    if($_SESSION['csrf'] === $_POST['csrf_token']) {
        if(isset($_COOKIE['token']) && $tokendata) {
            $id = $token['user'];
            $date = date("Y-m-d H:i:s");
            $model_id = (int)htmlspecialchars($_POST['model_id']);
            $reason = htmlspecialchars($_POST['reason']);
            $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

            if($_POST['other'] != null) {
                $reason = htmlspecialchars($_POST['other']);
            }

            $stmt = $conn->prepare("INSERT INTO reported (build, date, reason, user) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $model_id, $date, $reason, $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => 'Creation reported! Thanks for making our platform a safe space for everyone!']);
            } else {
                echo json_encode(['error' => 'Oops! We could\'/t report the submitted creation at this moment. Please try again later.']);
            }
            $stmt->close();
        } else {
            echo json_encode(['error' => 'Oops! Please login to report a creation.']);
        }
    } else {
        echo json_encode(['error' => 'Oops! Your CSRF token seems to be invalid.']);
    }
    exit;
}

if(isset($_POST['report'])){

        $username = $_POST['user'];

        $msg = $_POST['other'];
		
		$type = $_POST['type'];

        $xml = new SimpleXMLElement ('<user></user>');

        $xml->addChild('username', $username);

        $xml->addChild('msg', $msg);

        $xml->addChild('type', $type);

        $xml->asXml('com/report/creation-' . $_POST['id'] . '.xml');

        echo('Reported! We will check this in the next 72 hours!');
		
		die;
            
}
if(isset($_POST['delete'])){
    /*$creation = "cre/" . basename($_SERVER['QUERY_STRING']);
    unlink($creation);
	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
	$model = basename($_SERVER['QUERY_STRING']);

	$sql = "DELETE FROM model WHERE model = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("i", $model);
	$stmt->execute();*/
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Creation reported</title>
    <?php include 'header.php' ?>
</head>
<body class="w3-light-blue w3-container">

<script> 
    var $buoop = {required:{e:-4,f:-3,o:-3,s:-1,c:-3},insecure:true,api:2023.10 }; 
    function $buo_f(){ 
    var e = document.createElement("script"); 
    e.src = "//browser-update.org/update.min.js"; 
    document.body.appendChild(e);
    };
    try {document.addEventListener("DOMContentLoaded", $buo_f,false)}
    catch(e){window.attachEvent("onload", $buo_f)}
</script>

<?php include('navbar.php') ?>

        <b>
            Creation reported. We will check it out in the next 72 hours. If you'd like to reach out to us directly, join the discord server at 
            <div class="tooltip">
                <span class="w3-tag w3-round w3-blue tooltiptext"><i class="fa fa-info-circle" aria-hidden="true"></i>discord.gg/4a7pmTatfZ</span>
                <a href="https://discord.gg/4a7pmTatfZ">
                    <i class="fa fa-external-link" aria-hidden="true"></i>
                    discord.gg/4a7pmTatfZ
                </a>.
            </div>
        </b><br />

    <?php include('linkbar.php') ?>

</body>
</html>