<nav id="navbar" class="w3-sidebar w3-bar-block w3-hide-small w3-hide-medium w3-large w3-card-2 w3-light-grey">
    <a href="/" class="w3-bar-item w3-button"><img src="/img/logo/192.png" style="width: 25px; height: 25px; border-radius: 15px;">Gr8Brik </b><span class="w3-green w3-tag w3-round">BETA</span></a>
    <a href="/modeler" class="w3-bar-item w3-button"><i class="fa fa-cubes" aria-hidden="true"></i>Modeler</a>
    <a href="/list?sort=all" class="w3-bar-item w3-button"><i class="fa fa-building-o" aria-hidden="true"></i>Creations</a>
    <a href="/com" class="w3-bar-item w3-button"><i class="fa fa-commenting-o" aria-hidden="true"></i>Community</a>
    <a href="http://blog.gr8brik.rf.gd/" class="w3-bar-item w3-button" target="_blank"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>Blog</a>

    <div class="gr8-navbarsearch-parent" xstyle="color: #000; display: inline-flex; height: 32px; padding-right: 0px; padding-left: 10px; border-radius: 6px;">
        <a class="fa fa-search gr8-navbarsearch" xstyle="background-color: #fff; cursor: pointer; padding-right: 6px; padding-left: 6px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);" id="search-button"></a>
        <input class="w3-input w3-border gr8-navbarsearch-input" type="text" id="search-input" placeholder="Search for...">
    </div><hr />

    <?php if(loggedin()) { ?>
        <div class='w3-dropdown-click w3-bar-block'>
            <button class='gr8-theme w3-button w3-bar-item' onclick="dropdown('dropdown-main-sidenav')">
                <i class='fa fa-at' aria-hidden='true'></i>&nbsp;<?php echo htmlspecialchars($current_user->username) ?><i class='fa fa-angle-down w3-right' aria-hidden='true'></i>

                <span class='w3-red w3-tag w3-round'>
                    <?php
                        if (!empty($current_user->alert) && $current_user->alert != 0) { 
                            echo (int)$current_user->alert;
                        }
                    ?>
                </span>
            </button>

            <div id="dropdown-main-sidenav" class='gr8-theme w3-light-grey w3-card-2 w3-dropdown-content'>
                <a href='/acc/index' class='w3-bar-item w3-button'>
                    <span><i class='fa fa-cog w3-padding-small' aria-hidden='true'></i>Account Settings</span>
                </a>
                
                <a href="/user/<?php echo $current_user->id ?>" class='w3-bar-item w3-button'>
                    <span><i class='fa fa-user-o w3-padding-small' aria-hidden='true'></i>My Profile</span>
                </a>
                
                <a href='/acc/notifications' class='w3-bar-item w3-button'>
                    <span><i class='fa fa-bell-o w3-padding-small' aria-hidden='true'></i>Notifications <span class='w3-red w3-tag w3-round'><?php echo (int)$current_user->alert ?></span></span>
                </a>
                
                <a href='/acc/creations' class='w3-bar-item w3-button'>
                    <span><i class='fa fa-th w3-padding-small' aria-hidden='true'></i>My Creations</span>
                </a>
                
                <a href='/acc/following' class='w3-bar-item w3-button'>
                    <span><i class='fa fa-address-book-o w3-padding-small' aria-hidden='true'></i>Social</span>
                </a>

                <a href="/acc/messages" class="w3-bar-item w3-button">
                    <span><i class="fa fa-comments-o w3-padding-small" aria-hidden="true"></i>Direct Messages <span class="w3-blue w3-tag w3-round">NEW</span></span>
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
    <a href="/index"><span class="w3-padding-small"><img src="/img/logo/192.png" width="30px" height="30px" class="w3-round"></span></a>
    <a href="/modeler"><span class="fa fa-cubes w3-xlarge w3-padding-small"></span></a>
    <a href="/list"><span class="fa fa-building-o w3-xlarge w3-padding-small"></span></a>
    <a href="/com/"><span class="fa fa-commenting-o w3-xlarge w3-padding-small"></span></a>
    <a href="/acc/messages"><span class="fa fa-comments-o w3-xlarge w3-padding-small"></span></a>
    <a href="http://blog.gr8brik.rf.gd/index"><span class="fa fa-pencil-square-o w3-xlarge w3-padding-small"></span></a>
    <a href="/acc/index"><span class="fa fa-user-o w3-xlarge w3-padding-small"></span></a>
</div>

<div class="w3-main gr8-main"><br />
	<span id="popup-wrapper-global"></span>