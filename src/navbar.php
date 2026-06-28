<nav id="navbar" class="w3-sidebar w3-bar-block w3-hide-small w3-hide-medium w3-large w3-card-2 w3-light-grey">
        <a href="/" class="w3-bar-item w3-button"><img src="/img/logo/192.png" style="width: 25px; height: 25px; border-radius: 15px;">GR8BRIK </b><span class="w3-green w3-tag w3-round">BETA</span></a>
        <a href="/modeler" class="w3-bar-item w3-button"><i class="fa fa-cubes" aria-hidden="true"></i>Modeler</a>
        <a href="/list?sort=all" class="w3-bar-item w3-button"><i class="fa fa-building-o" aria-hidden="true"></i>Creations</a>
        <a href="/com" class="w3-bar-item w3-button"><i class="fa fa-commenting-o" aria-hidden="true"></i>Community</a>
    	<a href="/acc/messages" class="w3-bar-item w3-button"><i class="fa fa-comments-o" aria-hidden="true"></i>Direct Messages <span class="w3-blue w3-tag w3-round">NEW</span></a>
        <a href="http://blog.gr8brik.rf.gd/" class="w3-bar-item w3-button" target="_blank"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>Blog</a>
    
        <div class="gr8-navbarsearch-parent" xstyle="color: #000; display: inline-flex; height: 32px; padding-right: 0px; padding-left: 10px; border-radius: 6px;">
            <a class="fa fa-search gr8-navbarsearch" xstyle="background-color: #fff; cursor: pointer; padding-right: 6px; padding-left: 6px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);" id="search-button"></a>
            <input class="w3-input w3-border gr8-navbarsearch-input" type="text" id="search-input" placeholder="Search for...">
        </div><hr />
    
        <?php if($loggedin === true) { ?>
            <div class='w3-dropdown-hover w3-bar-block'>
                <button class='gr8-theme w3-button w3-bar-item'>
                    <i class='fa fa-at' aria-hidden='true'></i>&nbsp;<?php echo htmlspecialchars($users_row['username']) ?><i class='fa fa-angle-down w3-right' aria-hidden='true'></i>

                    <span class='w3-red w3-tag w3-round'>
                        <?php
                            if (!empty($users_row['alert']) && $users_row['alert'] != 0) { 
                                echo (int)$users_row['alert'];
                            }
                        ?>
                    </span>
                </button>
                <div class='gr8-theme w3-light-grey w3-card-2 w3-dropdown-content'>
                    <a href='/acc/index' class='w3-bar-item w3-button'>
                        <span><i class='fa fa-cog w3-padding-small' aria-hidden='true'></i>Account Settings</span>
                    </a>
                    
                    <a href="/user/<?php echo $token['user'] ?>" class='w3-bar-item w3-button'>
                        <span><i class='fa fa-user-o w3-padding-small' aria-hidden='true'></i>My Profile</span>
                    </a>
                    
                    <a href='/acc/notifications' class='w3-bar-item w3-button'>
                        <span><i class='fa fa-bell-o w3-padding-small' aria-hidden='true'></i>Notifications <span class='w3-red w3-tag w3-round'><?php echo (int)$users_row['alert'] ?></span></span>
                    </a>
                    
                    <a href='/acc/creations' class='w3-bar-item w3-button'>
                        <span><i class='fa fa-th w3-padding-small' aria-hidden='true'></i>My Creations</span>
                    </a>
                    
                    <a href='/acc/following' class='w3-bar-item w3-button'>
                        <span><i class='fa fa-address-book-o w3-padding-small' aria-hidden='true'></i>Social</span>
                    </a>
                    
                    <a href='/acc/logins' class='w3-bar-item w3-button'>
                        <span><i class='fa fa-lock w3-padding-small' aria-hidden='true'></i>Sessions</span>
                    </a>
                    
                    <a href='/acc/login?status=logout' class='w3-bar-item w3-button'>
                        <span><i class='fa fa-sign-out w3-padding-small' aria-hidden='true'></i>Logout</span>
                    </a>
                </div>
            </div>
        <?php } else { ?>
            <a href='/acc/login' class='w3-bar-item w3-button'><i class='fa fa-sign-in' aria-hidden='true'></i>Login</a>
            <a href='/acc/register' class='w3-bar-item w3-button'><i class='fa fa-user-plus' aria-hidden='true'></i>Register</a>
        <?php } ?>

        <hr /><div class="featured-builds" style="display: none;">
            <span class="w3-padding">Featured</span>
            <span class="gr8-build-count"></span><br />
        </div>
</nav>

<script>
    $(document).ready(function() {
        $('#search-input').on('keyup', function(e) {
            if (e.keyCode === 13) {
                load_search();
            }
        })
        $('#search-button').on('click', function() {
            load_search();
        });
        
        window.loadFeaturedCreations();
    });
</script>

<div id="mobilenav" class="w3-hide-large w3-light-grey gr8-theme w3-card-2 w3-show-medium w3-bottom w3-padding w3-center" style="width: 100%; z-index: 1000;">
    <!-- <a href="/index"><span class="fa fa-home w3-xlarge w3-padding-small"></span></a> -->
    <a href="/index"><span class="w3-padding-small"><img src="/img/logo/192.png" width="30px" height="30px" class="w3-round"></span></a>
    <a href="/modeler"><span class="fa fa-cubes w3-xlarge w3-padding-small"></span></a>
    <a href="/list"><span class="fa fa-building-o w3-xlarge w3-padding-small"></span></a>
    <a href="/com/"><span class="fa fa-commenting-o w3-xlarge w3-padding-small"></span></a>
    <a href="/acc/messages"><span class="fa fa-comments-o w3-xlarge w3-padding-small"></span></a>
    <a href="http://blog.gr8brik.rf.gd/index"><span class="fa fa-pencil-square-o w3-xlarge w3-padding-small"></span></a>
    <a href="/acc/index"><span class="fa fa-user-o w3-xlarge w3-padding-small"></span></a>
</div>

<div class="w3-main gr8-main">
    
	<div id="gr8brik-message" class="w3-card-2 w3-light-grey w3-padding w3-margin-top w3-margin-bottom gr8-theme">
    	<h4>Gr8brik & Altcha</h4>
        <p>Altcha dropped support for the original v1 API, so for now we won't have bot verification. <a href="http://blog.gr8brik.rf.gd/t/019cab18-b984-71b6-ae24-df92ae8d8566" target="_blank">Learn more</a>.</p>
	</div><br />
    
	<?php if(loggedin() && isset($users_row) && $users_row['verify_token'] != null) { ?>
    <div class="w3-card-2 w3-light-grey w3-padding w3-margin-top gr8-theme">
        <i class="fa fa-lock" aria-hidden="true"></i>
        <b>Verify your account to unlock all features of Gr8Brik</b>
        <form action="/ajax/auth.php" method="GET">
            <altcha-widget 
            style="--altcha-border-width:0px;" 
            strings='{"verified":"I am not a robot","label":"I am not a robot","verifying": "I am not a robot","waitAlert": "An error has occurred"}' 
            challengeurl='https://us.altcha.org/api/v1/challenge?apiKey=ckey_01d9f4ad018c16287ca6f3938a0f'
            ></altcha-widget>
            <input type="submit" value="Verify" id="verify_account" name="verify_account" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo">
        </form>
    </div>
	<?php } ?>
            
	<span id="popup-wrapper-global"></span>