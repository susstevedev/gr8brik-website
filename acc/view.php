<?php
session_start();
if(isset($_POST['report'])){

        $username = basename($_SERVER['QUERY_STRING']);

        $reportxml = new SimpleXMLElement ('<user></user>');

        $reportxml->addChild('username', $username);

        $reportxml->asXml('../reported/' . '(user)' . $username . '.xml');

        echo('User reported. View reported creations and users <a href="../reported/index.php">here</a>.');
            
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo basename($_SERVER['QUERY_STRING']) ?></title>
    <link rel="stylesheet" href="../w3.css">
    <link rel="stylesheet" href="../theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <meta charset="UTF-8">
    <meta name="description" content="Gr8brik is a block building browser game. No download required">
    <meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script type="text/javascript" src="modeler/new/jsc3d.js"></script>
    <script type="text/javascript" src="modeler/new/jsc3d.webgl.js"></script>
    <script type="text/javascript" src="modeler/new/jsc3d.touch.js"></script>
    
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

<?php 
    include('../navbar.php') 
?>

        <div class='w3-container'>
                <p>Want to build your own model? <a href='../modeler.php' class='w3-btn w3-round'>MODELER</a></p>
        </div>
        <?php
        $xml = new SimpleXMLElement('users/' . basename($_SERVER['QUERY_STRING']) . '.xml', 0, true);
        if(file_exists('users/pfps/' . basename($_SERVER['QUERY_STRING']) . '..jpg')){
            echo "<div class='w3-card-4 w3-border' style='width: 25%; height: 25%'>
            <img src=" . 'users/pfps/' . basename($_SERVER['QUERY_STRING']) . '..jpg' . " style='width: 250px; height: 250px; border-radius: 50%;' />
            <h1>" . basename($_SERVER['QUERY_STRING']) . "</h1>
            <h4>@" . $xml->username . "</h4>
            </div>";
        }else{
            echo "<div class='w3-card-4' style='width: 25%; height: 25%'>
            <img src='../img/banned.jpg' style='width: 250px; height: 250px; border-radius: 50%;' />
            <h1>" . basename($_SERVER['QUERY_STRING']) . "</h1>
            <h4>@" . $xml->username . "</h4>
            <b>Has not uploaded a PFP</b>
            </div><br />";
        };
        ?>

        <?php
        if(file_exists('users/' . basename($_SERVER['QUERY_STRING']) . '.xml')){
            if($xml->username != ''){
                echo '<form method="post" action=""><input type="submit" value="REPORT" name="report" class="w3-btn w3-red w3-round w3-hover-white" /></form>';
                echo "<br /><b>User is active</b>";
                $userfile = 'users/' . basename($_SERVER['QUERY_STRING']) . '.xml';
                echo '<br /><b>Email:' . $xml->email . '</b>';
                echo "<br /><b>Joined:" . date ("F d Y H:i:s", filemtime($userfile)) . "</b><br />";
            }
        } else {
                echo '<center><h1>User not found</h1><b>Please check your spelling</b><br />';
                echo '<button class="w3-btn w3-round" onclick="window.history.go(-1);">BACK</button></center><br />';
        };
    ?>
    <br />
    <br />
    <br />

    <?php include '../linkbar.php' ?>


</div>

</body>
</html>