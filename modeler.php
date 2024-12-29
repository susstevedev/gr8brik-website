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

	<?php include('navbar.php') ?>

    <script>
        function fullScreenModeler() {
            document.getElementById('fullscreenBtn').style.zIndex = 9999;
            document.getElementsByTagName('iframe')[0].className = 'fullScreen'; 
            document.getElementById('navbar').style.display = 'none';
            document.getElementById('mobilenav').style.display = 'none';
            document.getElementById('sidebar').style.display = 'none';
        }
    </script>
	
	<div class="w3-container">
        <p>Want to build your creations offline, and upload them later?</p> 
		<p><button onclick="window.open('https://github.com/evanrutledge1/gr8brik-locallife/releases/latest', '_blank')" class="w3-btn w3-white w3-large  w3-hover-blue">Desktop app</button></p>
    </div>

    <p><button id="fullscreenBtn" onclick="fullScreenModeler();" class="w3-btn w3-white w3-large w3-hover-blue">Fullscreen</button></p>

	<iframe class="modeler" src="/mod/index.html?utm_source=newsite&import=false" title="GR8BRIK modeler window"></iframe></div>
	
	<?php include 'linkbar.php' ?>

</body>
</html>