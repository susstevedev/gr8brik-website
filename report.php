<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/newsite/acc/classes/constants.php';
define('DB_NAME2', 'if0_36019408_creations');
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
    $creation = "cre/" . basename($_SERVER['QUERY_STRING']);
    //$file = fopen("test.txt","w");
    //echo fwrite($file,"Hello World. Testing!");
    //fclose($file);

    unlink($creation);

    // Create connection
	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
			
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}
			
	// Query to get creation by it's own ID
	$model = basename($_SERVER['QUERY_STRING']);

	$sql = "DELETE FROM model WHERE model = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("i", $model);
	$stmt->execute();

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo basename($_SERVER['QUERY_STRING']) ?></title>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="lib/theme.css">
    <script src="lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <meta charset="UTF-8">
    <meta name="description" content="Gr8brik is a block building browser game. No download required">
    <meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
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

        <b>Prosses(es) finished.</b><br />

    <?php include('linkbar.php') ?>


</body>
</html>