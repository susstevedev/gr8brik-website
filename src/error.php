<?php
require_once 'acc/classes/user.php';

$error = $_GET['error'];

$user = $_SESSION['username'];

if($_POST){
    exit("<p>This page <b>does not support</b> server-side posts</p>");        
}

echo "<html><head><title>Gr8brik.rf.gd</title><link rel='stylesheet' href='https://www.w3schools.com/lib/w3.css'></head>";

echo "<body class='w3-center w3-light-blue'><center>";

echo "<div class='w3-card-2 w3-light-grey w3-round-small w3-padding-small' style='width:50vw'>";

echo "<a href='/'><img src='/img/banner.png' style='width:468px;height:60px;border:1px solid;' title='The newest and simplest block building app online! Join us at www.gr8brik.rf.gd'></a>";

if($error === '403') {
    header('HTTP/1.0 403 Forbidden');
	echo "<h1>Not Allowed</h1><p>You can't access this because we don't want you to.</p>";
}

if($error === '404') {
    header('HTTP/1.0 404 Not Found');
	echo "<h1>Not Found</h1><p>We can't find this resource on our servers.</p>";
}

if($error === '500') {
    header('HTTP/1.0 500 Internal Server Error');
	echo "<h1>Server Error</h1><p>Our servers are having trouble loading this resource.</p>";
}

if(!isset($error)) {
    header('HTTP/1.0 400 Bad Request');
    echo "<p>This page is to only be used when an error occurs.</p>";
}

echo "<a class='w3-btn w3-large w3-white w3-hover-blue we-mobile w3-border w3-round-large' href='/'>Homepage</a></center></div></body></html>";
?>