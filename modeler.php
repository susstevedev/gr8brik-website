<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Modeler / GR8BRIK</title>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="lib/theme.css">
    <script src="lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <meta charset="UTF-8">
    <meta name="description" content="Gr8brik is a block building browser game. No download required">
    <meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="w3-light-blue w3-container">

	<?php include('navbar.php') ?>
	
	<div class="w3-container">
        <p>Want to build your creations offline, and upload them later?</p> 
		<p><button onclick="window.open('https://github.com/evanrutledge1/gr8brik-locallife/releases/latest', '_blank')" class="w3-btn w3-white w3-large  w3-hover-blue">DESKTOP APP</button></p>
		<p><button class="w3-btn w3-white w3-large w3-disabled">MOBILE APP</button></p>
    </div>

	<div class="w3-center">

		<iframe src="/mod/index.html?utm_source=newsite&import=false" style="width:80vw;height:60vh; border-color:black;" title="GR8BRIK modeler window"></iframe></div>

	</div>
	
	<?php include('linkbar.php') ?>

</body>
</html>