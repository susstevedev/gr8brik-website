<div class="w3-hide-large w3-bar w3-top">

    <br /><div class="w3-large w3-hover-opacity w3-left w3-light-blue"><a href="/feed"><img src="/img/logo.jpg" width="50px" class="w3-round" height="50px"><b>GR8BRIK</b></a></div>

</div>
<script>

if (localStorage.getItem("toggleDarkMode") === "w3-dark-grey") {

    var element = document.body;

    element.classList.toggle("w3-dark-grey");

}

if (localStorage.getItem("toggleLightMode") === "w3-light-blue") {

    var element = document.body;

    element.classList.toggle("w3-light-blue");

}

var $buoop = {required:{e:-4,f:-3,o:-3,s:-1,c:-3},insecure:true,api:2024.06 }; 
    function $buo_f(){ 
    var e = document.createElement("script"); 
    e.src = "//browser-update.org/update.min.js"; 
    document.body.appendChild(e);
    };
    try {document.addEventListener("DOMContentLoaded", $buo_f,false)}
    catch(e){window.attachEvent("onload", $buo_f)}

const chromeVersion = parseInt(navigator.userAgent.match(/Chrome\\/([0-9]+)/)[1], 10);

if (chromeVersion < 106) {
    // Display a warning message to the user
    console.log("Your Chrome version is older than 106. Please consider updating.");
}

</script>
<style>

    .js_enabled #cookie-message {

        display: none;

    }

</style>
<br /><ul class="w3-navbar w3-black w3-card-8 w3-large w3-hide-small w3-top">
    <li><a href="/index" class="w3-hover-green"><img src="/img/logo.jpg" width="25px" class="w3-round" height="25px"><b>GR8BRIK</b></a></li>
    <li><a href="/modeler">MODELER</a></li>
    <li><a href="/list">CREATIONS</a></li>
    <li><a href="/com">COMMUNITY</a></li>
    <?php
        if(file_exists($_SERVER['DOCUMENT_ROOT'] . '/acc/users/' . $_SESSION['username'] . '.xml')){
            echo "<li style='float:right;'><a href='/acc'>" . $_SESSION['username'] . "</a></li>";
        } else {
            echo "<li style='float: right;'><a href='/acc/register.php'>REGISTER</a></li>";
            echo "<li style='float:right;'><a href='/acc/login.php'>LOGIN</a></li>";
        }
    ?>
</div>
</ul><br /><br />
<?php

    if(!file_exists($_SERVER['DOCUMENT_ROOT'] . '/acc/users/' . $_SESSION['username'] . '.xml')){

        echo '<p class="w3-red w3-border w3-padding w3-round">';

        echo '<img class="w3-mobile" src="/img/warning.jpg" width="25px" height="25px" alt="Warning"> You have to login to create, view, or post. You can still comment on the forums.';

        echo '<a class="w3-btn" href="/index.php">OKAY</a></p>';

    }

    if(basename($_SERVER['QUERY_STRING']) == 'bu'){

        echo '<p class="w3-red w3-border w3-padding w3-round">';

        echo '<img class="w3-mobile" src="img/warning.jpg" width="25px" height="25px" alt="Warning"> This user has been removed for violating are <a href="terms">Terms Of Service</a> or <a href="privacy">Privacy Policy</a>.';

        echo '<a class="w3-btn" href="/index.php">OKAY</a></p>';

    }

    if(basename($_SERVER['QUERY_STRING']) == 'du'){

        echo '<p class="w3-red w3-border w3-padding w3-round">';

        echo '<img class="w3-mobile" src="img/warning.jpg" width="25px" height="25px" alt="Warning"> This user does not exist, or has deleted their account.';

        echo '<a class="w3-btn" href="/index.php">OKAY</a>';

        echo '<a href="com/index.php" class="w3-btn w3-white w3-hover-green" target="_blank">LEARN MORE</a></p>';

    }

    if(basename($_SERVER['QUERY_STRING']) == 'dc'){

        echo '<p class="w3-red w3-border w3-padding w3-round">';

        echo '<img class="w3-mobile" src="img/warning.jpg" width="25px" height="25px" alt="Warning"> This creation has been removed for violating are <a href="terms">Terms Of Service</a> or <a href="privacy">Privacy Policy</a>.';

        echo '<a class="w3-btn" href="/index.php">OKAY</a></p>';

    }

    if(basename($_SERVER['QUERY_STRING']) == 'dp'){

        echo '<p class="w3-red w3-border w3-padding w3-round">';

        echo '<img class="w3-mobile" src="img/warning.jpg" width="25px" height="25px" alt="Warning"> This post has been removed for violating are <a href="terms">Terms Of Service</a> or <a href="privacy">Privacy Policy</a>.';

        echo '<a class="w3-btn" href="/index.php">OKAY</a></p>';

    }

    if(basename($_SERVER['QUERY_STRING']) == 'ob'){

        echo '<p class="w3-red w3-border w3-padding w3-round">';

        echo '<img class="w3-mobile" src="img/warning.jpg" width="25px" height="25px" alt="Warning"> Your browser is outdated. We will not support some older browsers on July 27, 2024.';

        echo '<a class="w3-btn" href="/index.php">OKAY</a>';

        echo '<a href="com/index.php" class="w3-btn w3-white w3-hover-green" target="_blank">LEARN MORE</a></p>';

    }

?>

<div id="cookie-message">
    <p class="w3-light-grey w3-border w3-padding w3-round">
        GR8BRIK uses cookies to ensure we give you the best experience on our website.
        <button onclick="javascript:window.location.reload();" class="w3-btn" target="_blank">ACCEPT</button>
        <a href="/privacy" class="w3-btn w3-white w3-hover-green" target="_blank">LEARN MORE</a>
    </p>
</div>

<!--[if lt IE 9]>
        <br /><p class="w3-red w3-border w3-padding w3-round"><img class="w3-mobile" src="img/warning.jpg" width="25px" height="25px" alt="Warning"> You are using an <b>outdated</b> browser. Please <a href="http://www.browsehappy.com" class="w3-btn">upgrade your browser</a> below, or <a href="http://www.google.com/chromeframe" class="w3-btn">activate Google Chrome Frame</a> to improve your experience.</p>
<![endif]-->