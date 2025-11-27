<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
    if(loggedin()) {
        header('Location:list.php');
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>All-in-browser Lego &reg; modeler</title>
    <?php include 'header.php' ?>
</head>

<body class="w3-light-blue w3-container">

    <?php include('navbar.php') ?>

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

    <div class="w3-container w3-light-grey w3-card-2 w3-text-grey"><h1>GR8BRIK</h1><h2>.rf.gd</h2></div><br />
    
    <div class="w3-content w3-card-2 w3-light-grey w3-padding-small w3-left" style="max-width: 525px;position: relative;">

        <a href="#feed"><img class="mySlides" src="img/feed.jpg" style="width:500px;height:250px;display:none;" class="w3-animate-right"></a>
        <a href="#community"><img class="mySlides" src="img/com.jpg" style="width:500px;height:250px;display:none;" class="w3-animate-right"></a>
        <a href="#creations"><img class="mySlides" src="img/creations.jpg" style="width:500px;height:250px;display:none;" class="w3-animate-right"></a>
        <a href="#uploads"><img class="mySlides" src="img/upload.jpg" style="width:500px;height:250px;display:none;" class="w3-animate-right"></a>

        <a class="w3-btn-floating w3-blue w3-xlarge w3-hover-white" style="position:absolute;top:45%;left:0" onclick="plusDivs(-1)"><i class="fa fa-arrow-left" aria-hidden="true"></i></a>
        <a class="w3-btn-floating w3-blue w3-xlarge w3-hover-white" style="position:absolute;top:45%;right:0" onclick="plusDivs(1)"><i class="fa fa-arrow-right" aria-hidden="true"></i></a>

    </div>

    <div class="main-text w3-container w3-right">
        
        <p style="word-wrap: break-word;">GR8BRIK is a block building browser game written in ThreeJS and uses the Ldraw parts library, for modern browsers. No download needed, just click the big white button below to go to the Modeler tab.</p>

        <a href="/modeler" class="w3-btn w3-large w3-white w3-hover-blue" target="_blank">Start Building!</a>

        <br /><b>Or...</b><br />

        <a href="/acc/login.php">Login</a><br />
        <a href="/acc/register.php">Create Account</a><br />
        <a href="/com/view.php?id=17">Rules</a><br />
        <a href="/terms.php">Terms and Conditions</a><br />
        
        <a href="/privacy.php">Privacy Policy</a><br />
    </div>

    <div class="w3-container w3-center"><hr />
        
        <div id="community">
            <h1>Awesome community</h1>
            <p>The GR8BRIK community is a forum of Lego lovers like you that use GR8BRIK and other tools. You can view posts of other and post on there.</p>
            <a href="/com/" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo">Community</a><hr />
        </div>

        <div id="creations">
            <h1>Full of builds</h1>
            <p>On GR8BRIK you can scroll view others public creation's for free, and download them in the GR8BRIK .gr8 or .json file format.</p>
            <a href="/list" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo">Creations</a><hr />
        </div>

        <div id="uploads">
            <h1>Fast, quick, easy-to-use, modeler</h1>
            <p>GR8BRIK's modeler may be one of the best part of our service; where you can model your own creation using Ldraw bricks.</p>
            <a href="/modeler" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo">Create</a><hr />
        </div>
    
    </div>

    <script>
    let slideIndex = 1;
    showDivs();

    setInterval(() => {
        slideIndex += 1;
        showdivs();
    }, 3000);

    function showDivs() {
        const x = $(".mySlides"); // array of slide elements
        if (slideIndex > x.length) slideIndex = 1;
        else if (slideIndex < 1) slideIndex = x.length;

        x.hide();
        x.eq(slideIndex - 1).show();
    }
    </script>

<?php include('linkbar.php'); ?>

</body>
</html>