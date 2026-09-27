<nav id="navbar">
        <ul>
            <li><a href="/"><img src="/img/logo/192.png" style="width: 25px; height: 25px; border-radius: 15px;">GR8BRIK </b><span style="border-radius: 0.5em;" class="container-fluid pico-background-blue-700">BETA</span></a></li>
        </ul>
        <ul>
            <li><a href="/modeler"><i class="fa fa-cubes" aria-hidden="true"></i>Modeler</a></li>
            <li><a href="/list?sort=all"><i class="fa fa-building-o" aria-hidden="true"></i>Creations</a></li>
            <li><a href="/com" ><i class="fa fa-commenting-o" aria-hidden="true"></i>Community</a></li>
    	    <li><a href="/acc/messages"><i class="fa fa-comments-o" aria-hidden="true"></i>Direct Messages</a></li>
            <li><a href="http://blog.gr8brik.rf.gd/" target="_blank"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>Blog</a></li>
        </ul>

        <!--<div class="gr8-navbarsearch-parent" xstyle="color: #000; display: inline-flex; height: 32px; padding-right: 0px; padding-left: 10px; border-radius: 6px;">
            <a class="fa fa-search gr8-navbarsearch" xstyle="background-color: #fff; cursor: pointer; padding-right: 6px; padding-left: 6px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);" id="search-button"></a>
            <input class="w3-input w3-border gr8-navbarsearch-input" type="text" id="search-input" placeholder="Search for...">
        </div><hr />-->
    
        <?php if ($current_user) {?>
            <!--<div class='w3-dropdown-hover w3-bar-block'>
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
            </div>-->

            <li>
                <details class="dropdown">
                    <summary>
                        <img id="navbar-profile-picture" src="<?php echo htmlspecialchars($current_user->picture) ?>" width="25px" height="25px">
                        <span id="navbar-username"><?php echo $users_row['display_name'] ?: "<span style='color:red'>Error</span>" ?></span>
                        <?php if($current_user->alert > 0) { ?>
                            <span id="navbar-account-notification-count" class='account-notification-count'><span><?php echo $current_user->alert ?></span></span>
                        <?php } ?>
                    </summary>
                    <ul style="width: 15%; left: auto;">
                        <li>
                            <?php if($users_row['id'] && $users_row['deactive'] == null) { ?>
                                <span>Authenticated as <?php echo $current_user->displayName . ' (@' . $current_user->username . ')' ?></span>
                            <?php } else { ?>
                                <span>Error fetching account details on backend</span>
                            <?php } ?>
                        </li>

                        <li><a href='/acc/index'>
                            <span><i class='fa fa-cog w3-padding-small' aria-hidden='true'></i> Account Settings</span>
                        </a></li>
                        
                        <li><a href="/user/<?php echo $current_user->id ?>">
                            <span><i class='fa fa-user-o w3-padding-small' aria-hidden='true'></i> My Profile</span>
                        </a></li>
                        
                        <li><a href='/acc/notifications'>
                            <span><i class='fa fa-bell-o w3-padding-small' aria-hidden='true'></i> Notifications <span class='account-notification-count'><span><?php echo (int)$users_row['alert'] ?></span></span></span>
                        </a></li>
                        
                        <li><a href='/acc/creations'>
                            <span><i class='fa fa-th w3-padding-small' aria-hidden='true'></i> My Creations</span>
                        </a></li>
                        
                        <li><a href='/acc/following'>
                            <span><i class='fa fa-address-book-o w3-padding-small' aria-hidden='true'></i> Social</span>
                        </a></li>
                        
                        <li><a href='/acc/logins'>
                            <span><i class='fa fa-lock w3-padding-small' aria-hidden='true'></i> Sessions</span>
                        </a></li>
                        
                        <li><a href='/acc/login?status=logout'>
                            <span><i class='fa fa-sign-out w3-padding-small' aria-hidden='true'></i> Logout</span>
                        </a></li>
                    </ul>
                </details>
            </li>
        <?php } else { ?>
            <ul class="float-right">
                <li><a href="#account-login" id="account-login" data-tooltip="Login to an existing account" data-placement="bottom"><i class='fa fa-sign-in' aria-hidden='true'></i>Login</a></li>
                <li><a href='/acc/register' id="account-create" data-tooltip="Create an account" data-placement="bottom"><i class='fa fa-user-plus' aria-hidden='true'></i>Register</a></li>
            </ul>
        <?php } ?>

        <hr /><div class="featured-builds" style="display: none;">
            <span class="w3-padding">Featured</span>
            <span class="gr8-build-count"></span><br />
        </div>
</nav>

<!-- <script>
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
    <a href="/index"><span class="w3-padding-small"><img src="/img/logo/192.png" width="30px" height="30px" class="w3-round"></span></a>
    <a href="/modeler"><span class="fa fa-cubes w3-xlarge w3-padding-small"></span></a>
    <a href="/list"><span class="fa fa-building-o w3-xlarge w3-padding-small"></span></a>
    <a href="/com/"><span class="fa fa-commenting-o w3-xlarge w3-padding-small"></span></a>
    <a href="/acc/messages"><span class="fa fa-comments-o w3-xlarge w3-padding-small"></span></a>
    <a href="http://blog.gr8brik.rf.gd/index"><span class="fa fa-pencil-square-o w3-xlarge w3-padding-small"></span></a>
    <a href="/acc/index"><span class="fa fa-user-o w3-xlarge w3-padding-small"></span></a>
</div> -->

<div id="root">
	<hgroup id="gr8brik-message">
    	<h4>Gr8brik & Altcha</h4>
        <p>Altcha dropped support for the original v1 API, so for now we won't have bot verification. <a href="http://blog.gr8brik.rf.gd/t/019cab18-b984-71b6-ae24-df92ae8d8566" target="_blank">Learn more</a>.</p>
	</hgroup><br />
    
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
            <input type="submit" value="Verify" id="verify_account" name="verify_account" class="pico-background-blue-600">
        </form>
    </div>
	<?php } ?>

    <span id="loginForm-container">
        <dialog id="loginForm-modal">
            <article>
                <article style="background-color:red;display:none;color:white;" id="error">
                    <span class="fa fa-times-circle-o" aria-hidden="true"></span>&nbsp;
                    <span id="error-text"></span>
                </article>

                <h2 style="margin-top: 0px;">Login <button id="loginForm-modal-close">&times;</button></h2>
                <form id="loginForm">
                    <fieldset role="group">
                        <input name="mail" type="email" placeholder="email or username" autocomplete="email" />
                        <input name="pwd" type="password" placeholder="and password" />
                        <button id="loginBtn" name='login' class="pico-background-blue-600"><span aria-busy="true"></span>Login</button>
                    </fieldset>
                </form>
                </footer>
            </article>
        </dialog>
    </span>
            
	<span id="popup-wrapper-global"></span>