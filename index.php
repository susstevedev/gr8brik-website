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
    <?php include 'header.php' ?>
</head>

<body class="w3-light-blue w3-container">

    <?php 

        include('navbar.php');

    ?>

    <style>
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

    <script>
    $(document).ready(function() {
        var slideIndex = 1;
        showDivs(slideIndex);

        function plusDivs(n) {
            showDivs(slideIndex += n);   
        }

        setInterval(function() {
            plusDivs(1);
        }, 3000);

        function showDivs(n) {
            var x = $(".mySlides");
            if (n > x.length) {
                slideIndex = 1;
            }
            if (n < 1) {
                slideIndex = x.length;
            }
            // Remove unnecessary line
            // slideIndex = 1;
            x.hide();
            x.eq(slideIndex - 1).show();
        }
    });
    </script>

    <div class="w3-container w3-light-grey w3-card-2 w3-text-grey"><h1>GR8BRIK</h1><h2>.rf.gd</h2></div><br />

    <div class="w3-content w3-card-2 w3-light-grey w3-padding-small w3-left" style="max-width: 525px;position: relative;">

        <a href="#feed"><img class="mySlides" src="img/feed.jpg" style="width:500px;height:250px;display:none;" loading='lazy' class="w3-animate-right"></a>
        <a href="#community"><img class="mySlides" src="img/com.jpg" style="width:500px;height:250px;display:none;" loading='lazy' class="w3-animate-right"></a>
        <a href="#creations"><img class="mySlides" src="img/creations.jpg" style="width:500px;height:250px;display:none;" loading='lazy' class="w3-animate-right"></a>
        <a href="#uploads"><img class="mySlides" src="img/upload.jpg" style="width:500px;height:250px;display:none;" loading='lazy' class="w3-animate-right"></a>

        <a class="w3-btn-floating w3-blue w3-xlarge w3-hover-white" style="position:absolute;top:45%;left:0" onclick="plusDivs(-1)"><i class="fa fa-arrow-left" aria-hidden="true"></i></a>
        <a class="w3-btn-floating w3-blue w3-xlarge w3-hover-white" style="position:absolute;top:45%;right:0" onclick="plusDivs(1)"><i class="fa fa-arrow-right" aria-hidden="true"></i></a>

    </div>

    <div class="main-text w3-container w3-right">

        <p style="word-wrap: break-word;">GR8BRIK is a block building browser game written in ThreeJS, for modern browsers. No download needed, just click the big white button below to go to the Modeler tab.</p>

        <a href="/modeler/" class="w3-btn w3-large w3-white w3-hover-blue" target="_blank">Start Building NOW!</a>

        <br /><b>Or...</b><br />

        <a href="/acc/login">Login</a><br />

        <a href="/acc/register">Create Account</a><br />

        <a href="/rules">Rules</a><br />

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

            <div id="tweet-container" class="w3-container w3-left">

                <a class="twitter-timeline" data-lang="en" data-width="800" data-height="400" data-dnt="true" data-theme="dark" href="https://twitter.com/Evan85908317?ref_src=twsrc%5Etfw">Tweets by Evan85908317</a> <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>

            </div>

            <div id="discord-container" class="w3-container w3-right">

                <iframe src="https://discord.com/widget?id=1307420772377165884&theme=dark" width="350" height="500" allowtransparency="true" frameborder="0" sandbox="allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts"></iframe>

            </div>
        </div> 

<?php include('linkbar.php'); ?>

</body>
</html>