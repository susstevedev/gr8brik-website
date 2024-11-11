<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';
if(isset($_SESSION['username'])){
    header('Location: feed.php');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>About</title>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="lib/theme.css">
    <script src="lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <meta charset="UTF-8">
    <meta name="robots" content="noarchive"> 
    <meta name="description" content="Gr8brik is a block building browser game written in JavaScript, W3CSS and JSC3D. No download required">
    <meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx, gr8brik-3d, all online lego builder">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<!-- ios support -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/img/logo.jpg" />
    <meta name="apple-mobile-web-app-status-bar" content="#f1f1f1" />
    <meta name="theme-color" content="#f1f1f1" />
</head>

<body class="w3-light-blue w3-container">

    <?php 

        include('navbar.php');

    ?>

    <style>
        .main-image {
            max-width: 50%;
            height: 25%;
        }
        .main-text {
            max-width: 50%;
        }
        @media only screen and (max-width: 768px) {
            .main-image {
                max-width: 0px;
                height: 0px;
                display: none;
            }
            .main-text {
                min-width: 100%;
                max-width: 100%;
            }
        }
    </style>

    <div class="w3-container w3-light-grey w3-card-2 w3-text-grey"><h1>GR8BRIK</h1><h2>.rf.gd</h2></div><br />

    <img class="main-image w3-left w3-hover-shadow" src="/img/creations.jpg" loading='lazy'>

    <div class="main-text w3-container w3-right">

        <p style="word-wrap:break-word;">GR8BRIK is a block building browser game written in ThreeJS, for modern browsers. No download needed, just click the big white button below to go to the Modeler tab.</p>

        <a href="modeler" class="w3-btn w3-large w3-white w3-hover-blue" target="_blank">Start Building</a>

        <br /><b>Or...</b><br />

        <a href="/acc/login">Login</a><br />

        <a href="/acc/register">Register</a><br />

        <a href="/terms">Terms and Conditions</a><br />

        <a href="/privacy">Privacy Policy</a><br />

    </div>

        <div class="w3-container w3-center"><hr />

            <div id="feed">
                <h1>A feed just for you</h1>
                <p>When you login to GR8BRIK; there is now a personalised feed of the 6 newest models, posts and comments on our service. You can view these models by clicking on them and enabling the WebGl window.</p>
                <a href="/home" class="w3-btn w3-blue w3-hover-white">Feed</a><hr />
            </div>

            <div id="community">
                <h1>Awesome community</h1>
                <p>In the GR8BRIK community, you can view posts of other and if you login, you can post on the community forums!</p>
                <a href="/com/" class="w3-btn w3-blue w3-hover-white">Community</a><hr />
            </div>

            <div id="creations">
                <h1>Full of builds</h1>
                <p>On GR8BRIK, you can scroll on for hours looking at people's builds via the creations page.</p>
                <a href="/list" class="w3-btn w3-blue w3-hover-white">Creations</a><hr />
            </div>

            <div id="uploads">
                <h1>Upload your creations</h1>
                <p>When you login to GR8BRIK; you can upload a model you've created from the Modeler. Once done, you can view it on the feed or creations page.</p>
                <a href="/acc/index#upload" class="w3-btn w3-blue w3-hover-white">Upload</a><hr />
            </div>
        </div> 

<?php include('linkbar.php'); ?>

</body>
</html>