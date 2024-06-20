<?php
session_start();
if(file_exists('acc/users/' . $_SESSION['username'] . '.xml')){
    header('Location: feed?' .  basename($_SERVER['QUERY_STRING']));
}
?>
<!DOCTYPE html>
<html lang="en" uri="www.gr8brik.rf.gd">

<head>

    <title>GR8BRIK - Block building browser game</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <link rel="stylesheet" href="w3.css">
    <link rel="stylesheet" href="theme.css">
    <meta charset="UTF-8">
    <meta name="robots" content="noarchive"> 
    <meta name="description" content="Gr8brik is a block building browser game written in JavaScript, W3CSS and JSC3D. No download required">
    <meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx, gr8brik-3d, all online lego builder">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


</head>

<body class="w3-light-blue w3-container">

    <?php 

        include('navbar.php');

    ?>

    <img class="w3-left w3-card-8 w3-mobile" src="img/blured_model.jpg" width="50%" height="25%" alt="That square thingy"><br />

    <div class="w3-container" id="main">

        <h1>Welcome to GR8BRIK!</h1>

        <p>GR8BRIK is a block building browser game written in JavaScript, W3CSS and ThreeJS.</p> 

        <p>No download needed, just click the big white button below.</p>

        <a href="modeler.php" class="w3-btn w3-white w3-hover-green w3-large" target="_blank">START BUILDING!</a>

        <b>Or...</b>

        <a href="/acc/login.php" class="w3-btn w3-round">LOGIN</a>

        <a href="/acc/register.php" class="w3-btn w3-round">REGISTER</a>

    </div><hr />

            <div id="feed">
                <img class="w3-right w3-card-8 w3-mobile" src="img/com.jpg" width="25%" height="25%" alt="User PFP"><br />
                <h1>A feed just for you</h1>
                <p>When you <a href="acc/register.php">Register</a> on GR8BRIK; there is now a personalised feed of the 6 newest models uploaded to GR8BRIK. You can view these models by clicking on them and enabling the WebGl window; or download it and import it into <a href="http://www.github.com/gr8brik-locallife">GR8BRIK desktop</a>.</p>
                <a href="/acc/register.php" class="w3-btn w3-round">REGISTER</a><hr />
            </div>

            <div id="community">
                <img class="w3-right w3-card-8 w3-mobile" src="img/com.jpg" width="25%" height="25%" alt="User PFP"><br />
                <h1>Awesome community</h1>
                <p>When you <a href="acc/register.php">Register</a> on GR8BRIK; you can post on the community forums!</p>
                <a href="/acc/register.php" class="w3-btn w3-round">REGISTER</a><hr />
            </div>

            <div id="creations">
                <img class="w3-left w3-card-8 w3-mobile" src="img/creations.jpg" width="25%" height="25%" alt="Blured creation"><br />
                <h1>Full of builds</h1>
                <p>On GR8BRIK, you can scroll on for hours looking at people's builds via the <a href="cre/index.php">Creations</a> page.</p>
                <a href="list.php" class="w3-btn w3-round">CREATIONS</a><hr />
            </div>

            <div id="uploads">
                <img class="w3-right w3-card-8 w3-mobile" src="img/upload.jpg" width="25%" height="25%" alt="Blured creation"><br />
                <h1>Upload your creations</h1>
                <p>When you Login to you're GR8BRIK account; you can upload a model you've created from the <a herf="modeler.php">Modeler</a> (not other people's builds, you will be banned for stealing!). Once done, you can view it on the <a href="/cre/index.php">Creations</a> page.</p>
                <a href="/acc/index.php#upload" class="w3-btn w3-round">UPLOAD</a><hr />
            </div>

        <?php include('linkbar.php'); ?>


</div>

</body>
</html>