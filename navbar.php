<?php if(isset($_COOKIE['topnav']) && $_COOKIE['topnav'] === "yes") { ?>
    <nav id="topnav" class="w3-bar w3-hide-small w3-hide-medium w3-card-2 w3-black w3-top w3-large">
        <a href="/" class="w3-bar-item w3-button"><img src="/img/logo.jpg" style="width: 25px; height: 25px; border-radius: 15px;">GR8BRIK&nbsp;</b><span class="w3-green w3-tag w3-round">BETA</span></a>
        <a href="/modeler" class="w3-bar-item w3-button"><i class="fa fa-cubes" aria-hidden="true"></i>MODELER</a>
        <a href="/list.php" class="w3-bar-item w3-button"><i class="fa fa-building" aria-hidden="true"></i>CREATIONS</a>
        <a href="/com/index.php" class="w3-bar-item w3-button"><i class="fa fa-commenting" aria-hidden="true"></i>COMMUNITY</a>
        <div style="color: #000; display: inline-flex; height: 32px; padding-right: 0px; padding-left: 10px; border-radius: 6px;">
            <a class="fa fa-search" style="background-color: #fff; cursor: pointer; padding-right: 6px; padding-left: 6px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);" id="search-button"></a>
            <input class="w3-input w3-border" type="text" id="search-input" placeholder="Search for...">
        </div>
        <?php if($loggedin === true) { ?>
            <div class="w3-right">
                <a href='/acc/index.php' class='w3-bar-item w3-button'><i class='fa fa-user-o' aria-hidden='true'></i><?php echo strtoupper($users_row['username']) ?></a>
                <?php if (!empty($users_row['alert']) && $users_row['alert'] != 0) { ?>
                    <span class='w3-red w3-tag w3-round'><?php (int)$users_row['alert'] ?></span>
                <?php } ?>
            </div>
        <?php } else { ?>
            <div class="w3-right">
                <a href='/acc/login.php' class='w3-bar-item w3-button'><i class='fa fa-sign-in' aria-hidden='true'></i>LOGIN</a>
                <a href='/acc/register.php' class='w3-bar-item w3-button'><i class='fa fa-user-plus' aria-hidden='true'></i>REGISTER</a>
            </div>
        <?php } ?>
    </nav><br />
<?php } else { ?>
    <nav id="navbar" class="w3-sidebar w3-bar-block w3-hide-small w3-hide-medium w3-large w3-card-2 w3-light-grey">
        <a href="/" class="w3-bar-item w3-button"><img src="/img/logo/192.png" style="width: 25px; height: 25px; border-radius: 15px;">GR8BRIK&nbsp;</b><span class="w3-green w3-tag w3-round">BETA</span></a>
        <a href="/modeler" class="w3-bar-item w3-button"><i class="fa fa-cubes" aria-hidden="true"></i>Modeler</a>
        <a href="/list" class="w3-bar-item w3-button"><i class="fa fa-building" aria-hidden="true"></i>Creations</a>
        <a href="/com" class="w3-bar-item w3-button"><i class="fa fa-commenting" aria-hidden="true"></i>Community</a>
        <a class="w3-bar-item w3-button w3-disabled" target="_blank"><i class="fa fa-pencil-square" aria-hidden="true"></i> Blog</a>
        <div style="color: #000; display: inline-flex; height: 32px; padding-right: 0px; padding-left: 10px; border-radius: 6px;">
            <a class="fa fa-search gr8-navbarsearch" style="background-color: #fff; cursor: pointer; padding-right: 6px; padding-left: 6px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);" id="search-button"></a>
            <input class="w3-input w3-border gr8-navbarsearch-input" type="text" id="search-input" placeholder="Search for...">
        </div><hr />
        <?php if($loggedin === true) { ?>
            <div class='w3-dropdown-hover w3-bar-block'>
                <button class='gr8-theme w3-button w3-bar-item'>
                    <i class='fa fa-angle-down' aria-hidden='true'></i>
                    <?php echo htmlspecialchars($users_row['username']) ?>

                    <span class='w3-red w3-tag w3-round'>
                        <?php
                            if (!empty($users_row['alert']) && $users_row['alert'] != 0) { 
                                echo (int)$users_row['alert'];
                            }
                        ?>
                    </span>
                </button>
                <div class='gr8-theme w3-light-grey w3-card-2 w3-dropdown-content'>
                    <a href='/acc' class='w3-bar-item w3-button'>
                    <i class='fa fa-cog' aria-hidden='true'></i>Account Settings</a>
                    <a href="/user/<?php echo $token['user'] ?>" class='w3-bar-item w3-button'><i class='fa fa-user' aria-hidden='true'></i>Profile</a>
                    <a href='/acc/new' class='w3-bar-item w3-button'><i class='fa fa-inbox' aria-hidden='true'></i>Notifications 
                        <span class='w3-red w3-tag w3-round'><?php echo (int)$users_row['alert'] ?></span>
                    </a>
                    <a href='/acc/creations' class='w3-bar-item w3-button'><i class='fa fa-building' aria-hidden='true'></i>My Creations</a>
                    <a href='/acc/following' class='w3-bar-item w3-button'><i class='fa fa-user-plus' aria-hidden='true'></i>People</a>
                    <a href='/acc/logins' class='w3-bar-item w3-button'><i class='fa fa-lock' aria-hidden='true'></i>Sessions</a>
                    <a href='/acc/login?status=logout' class='w3-bar-item w3-button'><i class='fa fa-sign-out' aria-hidden='true'></i>Logout</a>
                </div>
            </div>
        <?php } else { ?>
            <a href='/acc/login' class='w3-bar-item w3-button'><i class='fa fa-sign-in' aria-hidden='true'></i>Login</a>
            <a href='/acc/register' class='w3-bar-item w3-button'><i class='fa fa-user-plus' aria-hidden='true'></i>Register</a>
        <?php } ?>

        <hr /><div class="featured-builds" style="display: none;">
            <span class="w3-padding">Featured&nbsp;<span class="w3-blue w3-tag w3-round">NEW</span></span><br />
        </div>
    </nav>
<?php } ?>

<script>
    $(document).ready(function() {
        function load_search() {
            var search_term = $('#search-input').val();
            if (search_term.length > 1) {
                $.ajax({
                    url: '/list.php',
                    type: 'GET',
                    data: { q: search_term },
                    success: function(r) {
                        window.location.href = "/list?q=" + encodeURIComponent(search_term).replace(/%20/g, "+");
                    }
                });
            } else {
                alert('Search must contain at least two characters.');
            }
        }

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
    <a href="/index"><span class="fa fa-home w3-xlarge w3-padding-small"></span></a>
    <a href="/modeler"><span class="fa fa-cubes w3-xlarge w3-padding-small"></span></a>
    <a href="/list"><span class="fa fa-building-o w3-xlarge w3-padding-small"></span></a>
    <a href="/com/"><span class="fa fa-commenting-o w3-xlarge w3-padding-small"></span></a>
    <a href="http://blog.gr8brik.rf.gd/index"><span class="fa fa-pencil-square-o w3-xlarge w3-padding-small"></span></a>
    <a href="/acc/"><span class="fa fa-user-o w3-xlarge w3-padding-small"></span></a>
</div>

<?php if(isset($_COOKIE['topnav'])) { ?>
    <div class="w3-main" style="margin-left: 0% !important"><br />
<?php } else { ?>
    <div class="w3-main"><br />
<?php } ?>

<?php if(isset($users_row) && $users_row['verify_token'] != null) { ?>
    <div class="w3-card-2 w3-light-grey w3-padding">
        <i class="fa fa-lock" aria-hidden="true"></i>
        <b>Verify your account to unlock all features of Gr8Brik</b>
        <form action="/ajax/auth.php" method="GET">
            <altcha-widget 
            style="--altcha-border-width:0px;" 
            strings='{"verified":"I am not a robot","label":"I am not a robot","verifying": "I am not a robot","waitAlert": "An error occurred"}' 
            challengeurl='https://us.altcha.org/api/v1/challenge?apiKey=ckey_01d9f4ad018c16287ca6f3938a0f'
            ></altcha-widget>
            <input type="submit" value="Verify" id="verify_account" name="verify_account" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo">
        </form>
    </div><br />
<?php } ?>

<!-- <div class="w3-card-2 w3-red w3-padding-small"><i class="fa fa-info-circle" aria-hidden="true"></i><b>Gr8brik's old modeler will be removed in June. Creations using the old modeler format can still be opened using a local copy, as it's archived on <a href="https://github.com/susstevedev/gr8brik-old">GitHub</a>.</b></div><br /> -->