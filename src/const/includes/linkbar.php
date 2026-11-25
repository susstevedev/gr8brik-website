<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/const/utils/svgs.php';

    if(!loggedin() && !isset($_COOKIE['hide_login_ad'])) {
        ?>
        <div id="loginAd" class="w3-hide-small w3-show-medium w3-padding-small w3-border w3-border-grey w3-card-2 w3-bottom w3-blue w3-text-white w3-col m8">
            <span id="hideLoginAd" class="w3-display-topright w3-red w3-button">&times;</span>
            <span class="w3-left w3-padding">
                <span><a href="/acc/login">Login</a> or <a href="/acc/register">Register</a> to share & save your own Lego-like creations online.</span>
                <br />
                <span><a href="/terms">Terms of Service</a> • <a href="/privacy">Privacy Policy</a></span> • <a href="/rules">Community Guidelines</a>
            </span>
        </div>
        <?php
    }
    ?>
    <hr />

    <div>
        <button id="toggleAuto" class="w3-btn w3-large w3-light-grey w3-hover-opacity w3-round-small w3-border w3-border-grey"><i class="fa fa-sun-o"></i><i class="w3-large">/</i><i class="fa fa-moon-o"></i>Auto Theme</button>
        <button id="toggleDark" class="w3-btn w3-large w3-light-grey w3-hover-opacity w3-round-small w3-border w3-border-grey"><i class="fa fa-moon-o" aria-hidden="true"></i>Night Theme</button>
        <button id="toggleLight" class="w3-btn w3-large w3-light-grey w3-hover-opacity w3-round-small w3-border w3-border-grey"><i class="fa fa-sun-o" aria-hidden="true"></i>Day Theme</button>
    </div>

    <br /><footer id="linkbar" class="gr8-theme w3-navbar w3-card-2 w3-light-grey w3-round-small w3-padding-small w3-center">
        <p class="w3-large">©2023 - 2026 The Gr8Brik Team. All rights reserved.</p>

        <p><a href="/terms">Terms and Conditions</a> - <a href="/privacy">Privacy Policy</a> - <a href="/rules">Community Guidelines</a></p>

        <span class="linkbar-socials">
            <a href="mailto:<?php echo DB_MAIL ?>" target="_blank" class="w3-hover-text-red" alt="Email">
                <?php GenerateSVGs::get('bi-envelope-at'); ?>
            </a>

            <a href="<?php echo appSpace->__urls->__socials->twitter ?>" target="_blank" class="w3-hover-text-blue" alt="Twitter">
                <?php GenerateSVGs::get('bi-twitter-x'); ?>
            </a>

            <a href="<?php echo appSpace->__urls->__socials->youtube ?>" target="_blank" class="w3-hover-text-red" alt="YouTube">
                <?php GenerateSVGs::get('bi-youtube'); ?>
            </a>

            <a href="<?php echo appSpace->__urls->__socials->github ?>" target="_blank" class="w3-hover-text-white" alt="Github">
                <?php GenerateSVGs::get('bi-github'); ?>
            </a>

            <a href="<?php echo appSpace->__urls->__socials->reddit ?>" target="_blank" class="w3-hover-text-orange" alt="Reddit">
                <?php GenerateSVGs::get('bi-reddit'); ?>
            </a>

            <a href="<?php echo appSpace->__urls->__socials->discord ?>" target="_blank" alt="Discord">
                <?php GenerateSVGs::get('bi-discord'); ?>
            </a>
        </span>

        <p class="w3-small">Lego&#174;, the Lego&#174; logo, the Minifigure&#8482;, and the Brick&#8482; and Knob&#8482; configurations are trademarks of the Lego&#174; company, which does not sponsor, authorize, or endorse this site.</p>
    </footer><br />
</body></html>