<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Modeler</title>
    <?php include 'header.php' ?>
</head>
<body class="w3-light-blue w3-container">

    <style>
        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>

	<?php include 'navbar.php' ?>

    <script>
        function fullscreen() {
            const iframe = document.getElementsByTagName('iframe')[0];

            if (iframe.requestFullscreen) {
                iframe.requestFullscreen();
            } else if (iframe.mozRequestFullScreen) {
                iframe.mozRequestFullScreen();
            } else if (iframe.webkitRequestFullscreen) {
                iframe.webkitRequestFullscreen();
            } else if (iframe.msRequestFullscreen) {
                iframe.msRequestFullscreen();
            } else {
                alert('Could not full screen.')
            }
        }
    </script>

    <input id="easteregg" type="text" style="display:none;" />
	
	<div class="w3-container">
        <p>Want to build your creations offline, and upload them later?</p> 
		<p><a href="https://github.com/susstevedev/gr8brik-locallife/releases/latest" target="_blank" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo">Desktop App</a></p>
    </div>

    <p><button id="fullscreenBtn" onclick="fullscreen();" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo">Fullscreen</button></p>

    <p><a href="/new-modeler.php" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo">New Modeler</a></p>

	<iframe class="modeler" src="/mod/index.html?utm_source=newsite&import=false" title="GR8BRIK modeler window" allowfullscreen></iframe>
	
	<?php include 'linkbar.php' ?>

</body>
</html>