<?php

session_start();

if(!file_exists('acc/users/' . htmlspecialchars($_SERVER['QUERY_STRING']) . '.xml')){

    header('Location: index.php?du');

    die;

}
$xml = new SimpleXMLElement('acc/users/' . urldecode($_SERVER['QUERY_STRING']) . '.xml', 0, true);

    if($xml->username == '' || $xml->password == '') {

        header('Location: index.php?bu');

        die;

    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo urldecode($_SERVER['QUERY_STRING']) ?></title>
    <link rel="stylesheet" href="w3.css">
    <link rel="stylesheet" href="theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
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

<?php 
    include('navbar.php') 
?>
        <?php
        ?>
            <img src="<?php echo 'acc/users/pfps/' . basename($_SERVER['QUERY_STRING']) . '..jpg' ?>" style='width: 250px; height: 250px; border-radius: 50%;cursor:zoom-in' class="w3-hover-shadow" onclick="document.getElementById('im').style.display='block'" />

            <div id="im" class="w3-modal" onclick="this.style.display='none'">

                <span class="w3-white w3-hover-red w3-padding w3-display-topright">&times;</span>

                <img class="w3-modal-content" src="<?php echo 'acc/users/pfps/' . basename($_SERVER['QUERY_STRING']) . '..jpg' ?>" style="width:75%;">

            </div>

            <div class='w3-container w3-card-8'>
                <b style="font-size:30px;"><?php echo urldecode($_SERVER['QUERY_STRING']) ?></b><br />
                <?php 
                    $userfile = 'acc/users/' . urldecode($_SERVER['QUERY_STRING']) . '.xml';
                ?>
                <b>@<?php echo $xml->username ?></b>
                <br /><b>Email:<?php echo $xml->email ?></b>
                <br /><b>Joined:<?php echo date ("F d Y H:i:s", filemtime($userfile)) ?></b>
                <br /><b>Age:<?php echo $xml->age ?></b>
                <br /><b>Description:<?php echo $xml->description ?></b>
                <p><a href="report.php" class="w3-btn w3-red w3-round w3-hover-white">REPORT</a></p>
                <table>
            <?php

                $query = urldecode($_SERVER['QUERY_STRING']);
                echo "<h2>" . $xml->username . "'s creations</h2>";
                echo "<h5>and related</h5>";
                foreach (glob("cre/*json") as $_FF) {
                    if (strpos($_FF, $query) !== false) {
                        echo "<a href='creation.php?" . $_FF . "'><img src='img/blured_model.jpg' width='320' height='240' ></a><br />";
                        echo "<b>Name:" . $_FF . "</b><br />";
                        echo "<b>Created:" . date("F d Y H:i:s", filemtime($_FF)) . "</b><br />";
                    }
                };

            ?>
        </table></div><br /><br />

    <?php include('linkbar.php') ?>


</div>

</body>
</html>