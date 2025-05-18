<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';
    isLoggedin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>{"title": "Feed"}</title>
    <?php include 'header.php' ?>
</head>
<body class="w3-light-blue w3-container">

    <?php
        include 'com/bbcode.php';
        include 'navbar.php';
        include 'acc/panel.php';
        $bbcode = new BBCode;
    ?>

    <div class="w3-center">

        <h4 class="w3-padding w3-red">The feed page was discontinued on April 10th.<br />&nbsp;The creation's page in the side navigation now shows models from who your following by default.</h4>

        <div class="w3-navbar w3-half w3-center" style="flex-direction:row;">
            <a class='w3-btn w3-light-grey w3-quarter w3-hover-blue w3-border w3-border-grey' onclick="openTab('creationstab')">Creations</a>
            <a class='w3-btn w3-light-grey w3-quarter w3-hover-blue w3-border w3-border-grey' onclick="openTab('poststab')">Posts</a>
            <a class='w3-btn w3-light-grey w3-quarter w3-hover-blue w3-border w3-border-grey' onclick="openTab('commentstab')">Comments</a>
            <a class='w3-btn w3-light-grey w3-quarter w3-hover-blue w3-border w3-border-grey w3-padding-small' onclick="openTab('likestab')">Likes <span class="w3-blue w3-tag w3-padding-tiny">NEW</span></a>
        </div>
        <h2 id="text" style="text-transform: capitalize;"></h2>
        
        <h4 id="recommended" class="">An unknown error has occurred.</h4>

        <table class="w3-table-all" style="color:black;">
            <div id="creationstab" class="tab w3-hide">
                
            </div>

            <div id="commentstab" class="tab w3-hide">

            </div>

            <div id="likestab" class="tab w3-hide">
			
            </div>
        </table><br /><br />
    </div>

    <?php include('linkbar.php'); ?>
</body>
</html>