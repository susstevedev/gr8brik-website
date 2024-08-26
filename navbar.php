<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';

?>


<!-- Device scale for all pages, will be moved to them individually later-->
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">

<nav id="navbar" class="gr8-desktop-nav w3-sidenav w3-bar-block w3-light-grey w3-hide-small w3-large w3-card-24 w3-top">

	<a href="/home"><img src="/img/logo.jpg" style="width:30px;height:30px;border-radius:50%;border:1px solid #000;">GR8BRIK</a>

	<a class="w3-hover-opacity" href="/modeler"><i class="fa fa-cubes" aria-hidden="true"></i>MODELER</a>

	<a class="w3-hover-opacity" href="/list"><i class="fa fa-building" aria-hidden="true"></i>CREATIONS</a>

	<a class="w3-hover-opacity" href="/com/"><i class="fa fa-commenting" aria-hidden="true"></i>COMMUNITY</a>

    <a class="w3-hover-opacity" href="/com/docs"><i class="fa fa-file" aria-hidden="true"></i>DOCUMENTATION</a>

    <a class="w3-hover-opacity w3-disabled" href=""><i class="fa fa-shopping-cart" aria-hidden="true"></i>SHOP<span class='w3-tag w3-round w3-blue'>SOON</span></a>

	<form method="get" action="../list.php?">

        <input class="w3-input w3-border" type="text" placeholder="&#128270;Search for..." name="q" style="width:100%">

        <input class="w3-btn w3-large w3-white w3-hover-blue" type="submit" value="&#128270;">

    </form><hr />

	<?php
		
		if(!isset($_SESSION['username'])){
			echo "<a class='w3-hover-opacity' href='/acc/login'><i class='fa fa-sign-in' aria-hidden='true'></i>
LOGIN</a>";
            echo "<a class='w3-hover-opacity' href='/acc/register'><i class='fa fa-user-plus' aria-hidden='true'></i>
REGISTER</a>";
            // Check login using another method
        } elseif($session != $id) {
            echo "<p>An error has occered. Please login again.</p>";
            echo "<a class='w3-hover-opacity' href='/acc/login?status=loggedout'><i class='fa fa-sign-in' aria-hidden='true'></i>LOGIN</a>";
        } else {
            echo "<a class='w3-hover-opacity' href='/acc/index'><i class='fa fa-user' aria-hidden='true'></i>" . strtoupper($user) . "</a>";
			echo "<a class='w3-hover-opacity' href='/acc/new'><i class='fa fa-envelope' aria-hidden='true'></i><span class='w3-tag w3-round w3-red'>" . $alert . " INBOX</span>&nbsp;<span class='w3-tag w3-round w3-blue'>NEW</span></a>";
			echo "<a class='w3-hover-opacity' href='/acc/login?status=loggedout'><i class='fa fa-sign-out' aria-hidden='true'></i>LOGOUT</a>";
        }
		
    ?>

</nav>

    <div class="gr8-mobile-nav w3-card-24 w3-top w3-light-grey w3-hide-large">

        <a href="/feed.php">
            <img src="/img/logo.jpg" style="width:50px;height:50px;border-radius:15px;float:left;">
            <b class="w3-left">GR8BRIK</b>
        </a>

		<?php
			if(!isset($_SESSION['username'])) {
                echo '<a href="/acc/register.php" class="fa fa-user-plus w3-xlarge w3-right w3-text-blue w3-left w3-padding"></a>';
                echo '<a href="/acc/login.php" class="fa fa-sign-in w3-xlarge w3-right w3-text-blue w3-left w3-padding"></a>';
			} else {
				echo '<a href="/acc/index.php"><img src="/newsite/acc/users/pfps/' . $id . '..jpg" style="width:50px;height:50px;border-radius:50%;float:right"></a>';
				echo '<a href="/acc/new.php" class="fa fa-envelope-o w3-xlarge w3-right w3-text-blue w3-padding"><span class="w3-badge w3-red">' . $alert . '</span></a>';
			}
		?>

        <form type="get" action="/list.php">

		    <span id="search" class="fa fa-search w3-xlarge w3-right w3-text-blue w3-padding"></span>

		    <input type="text" id="searchBox" class="w3-input w3-border" placeholder="Search" name="q">

		    <input id="searchBtn" class="w3-btn w3-blue w3-hover-opacity" type="submit" id="submit" value="&#128270;" name="submit" />

	    </form>

    </div><br /><br />
	
	<div id="cookie-message">

		<p class="w3-light-grey w3-border w3-padding w3-round">

			GR8BRIK uses cookies to ensure we give you the best experience on our website.

			<button onclick="javascript:window.location.reload();" class="w3-btn w3-blue w3-border w3-hover-opacity" target="_blank">ACCEPT</button>

			<a href="/privacy" class="w3-btn w3-white w3-border" target="_blank">LEARN MORE</a>

		</p>

	</div>
	
	<!--[if lt IE 9]>
        <br /><p class="w3-red w3-border w3-padding w3-round"><img class="w3-mobile" src="img/warning.jpg" width="25px" height="25px" alt="Warning"> You are using an <b>outdated</b> browser. Please <a href="http://www.browsehappy.com">upgrade your browser</a> below, or <a href="http://www.google.com/chromeframe">activate Google Chrome Frame</a> to improve your experience.</p>
	<![endif]-->

	<div class="w3-main">

	<br /><br /><div id="overlay"></div>