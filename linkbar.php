<?php
if(!loggedin()) {
    echo '
    <div class="w3-hide-small w3-show-medium w3-padding w3-card-2 w3-bottom w3-blue w3-text-white">
        <img src="/img/logo/384.png" width="50px" height="50px" title="gr8-logo" class="w3-circle w3-left" />
        <span class="w3-left w3-padding">
            <span><a href="/acc/login">Login</a> or <a href="/acc/register">Register</a> to see upload your own Lego-like creations to the cloud.</span>
            <span><a href="/terms.php">Terms of Service</a> -- <a href="/privacy.php">Privacy Policy</a>.</span>
    </div>
    ';
}
?>

<p>
    <button id="toggleAuto" class="w3-btn w3-large w3-white w3-hover-blue w3-round"><i class="fa fa-sun-o"></i><i class="w3-large">/</i><i class="fa fa-moon-o"></i>Auto Theme</button>
    <button id="toggleDark" class="w3-btn w3-large w3-white w3-hover-blue w3-round"><i class="fa fa-moon-o" aria-hidden="true"></i>Night Theme</button>
    <button id="toggleLight" class="w3-btn w3-large w3-white w3-hover-blue w3-round"><i class="fa fa-sun-o" aria-hidden="true"></i>Day Theme</button>
</p>

<div id="linkbar" class="gr8-theme w3-navbar w3-card-2 w3-padding-small w3-light-grey w3-center">
    <p class="w3-large">
		<span>©2023-2025 the Gr8Brik team. All rights reserved.</span>
        <a href="/terms.php">Terms and Conditions</a>.
        <a href="/privacy.php">Privacy Policy</a>.
        <a href="mailto:<?php echo DB_MAIL ?>" target="_blank" class="fa fa-envelope w3-xlarge w3-hover-text-red" alt="Email"></a>
        <a href="https://twitter.com/gr8brik" target="_blank" class="w3-hover-text-blue" alt="Twitter">
            <!-- <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px" fill="currentColor" class="bi bi-twitter-x" viewBox="0 0 16 16">
                <path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865z"/>
            </svg> -->
            <i class="fa fa-twitter w3-xlarge" aria-hidden="true"></i>
        </a>
        <a href="https://www.youtube.com/channel/UCUmhPF6BbVAMflLE9FPRPhQ" target="_blank" class="fa fa-youtube-play w3-xlarge w3-hover-text-red" alt="YouTube"></a>
        <a href="https://github.com/susstevedev/gr8brik-new" target="_blank" class="fa fa-github w3-xlarge w3-hover-text-green" alt="Github"></a>
        <a href="https://www.reddit.com/r/gr8brik" target="_blank" class="fa fa-reddit w3-xlarge w3-hover-text-orange" alt="Reddit"></a>
        <a href="https://discord.gg/4a7pmTatfZ" target="_blank" alt="Discord">
            <svg xmlns="http://www.w3.org/2000/svg" width="25px" height="25px" fill="currentColor" class="bi bi-discord" viewBox="0 0 16 16">
                <path d="M13.545 2.907a13.2 13.2 0 0 0-3.257-1.011.05.05 0 0 0-.052.025c-.141.25-.297.577-.406.833a12.2 12.2 0 0 0-3.658 0 8 8 0 0 0-.412-.833.05.05 0 0 0-.052-.025c-1.125.194-2.22.534-3.257 1.011a.04.04 0 0 0-.021.018C.356 6.024-.213 9.047.066 12.032q.003.022.021.037a13.3 13.3 0 0 0 3.995 2.02.05.05 0 0 0 .056-.019q.463-.63.818-1.329a.05.05 0 0 0-.01-.059l-.018-.011a9 9 0 0 1-1.248-.595.05.05 0 0 1-.02-.066l.015-.019q.127-.095.248-.195a.05.05 0 0 1 .051-.007c2.619 1.196 5.454 1.196 8.041 0a.05.05 0 0 1 .053.007q.121.1.248.195a.05.05 0 0 1-.004.085 8 8 0 0 1-1.249.594.05.05 0 0 0-.03.03.05.05 0 0 0 .003.041c.24.465.515.909.817 1.329a.05.05 0 0 0 .056.019 13.2 13.2 0 0 0 4.001-2.02.05.05 0 0 0 .021-.037c.334-3.451-.559-6.449-2.366-9.106a.03.03 0 0 0-.02-.019m-8.198 7.307c-.789 0-1.438-.724-1.438-1.612s.637-1.613 1.438-1.613c.807 0 1.45.73 1.438 1.613 0 .888-.637 1.612-1.438 1.612m5.316 0c-.788 0-1.438-.724-1.438-1.612s.637-1.613 1.438-1.613c.807 0 1.451.73 1.438 1.613 0 .888-.631 1.612-1.438 1.612"/>
            </svg>
        </a>
    </p>
	<p class="w3-small w3-text-grey">
		LEGO&#174;, the LEGO&#174; logo, the Minifigure&#8482;, and the Brick&#8482; and Knob&#8482; configurations are trademarks of the LEGO&#174; Group of Companies which does not sponsor, authorize, or endorse this site.
	</p><hr />
</div><br />