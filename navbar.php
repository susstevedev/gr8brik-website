<nav id="navbar" class="w3-sidenav w3-hide-small w3-hide-medium w3-large w3-card-2 w3-light-grey">

    <a href="/"><img src="/img/logo/192.png" style="width: 25px; height: 25px; border-radius: 50%;">GR8BRIK&nbsp;</b><span class="w3-tag w3-round w3-green">BETA</span></a>

	<a href="/modeler"><i class="fa fa-cubes" aria-hidden="true"></i>MODELER</a>

	<a href="/list"><i class="fa fa-building-o" aria-hidden="true"></i>CREATIONS</a>

	<a href="/com"><i class="fa fa-commenting-o" aria-hidden="true"></i>COMMUNITY</a>

    <a href="http://blog.gr8brik.rf.gd/" target="_blank"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>BLOG</a>

    <div style="color: #000; display: inline-flex; height: 32px; padding-right: 0px; padding-left: 10px; border-radius: 6px;">
        <a class="fa fa-search gr8-navbarsearch" style="background-color: #fff; cursor: pointer; padding-right: 6px; padding-left: 6px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);" id="search-button"></a>
        <input class="w3-input w3-border" type="text" id="search-input" placeholder="Search for...">
    </div><hr />

    <?php if($loggedin === true) { ?>
        <div class='w3-dropdown-hover w3-bar-block'>
            <button class='gr8-theme w3-button w3-bar-item'>
                <span class='fa fa-angle-down'></span><?php echo strtoupper($users_row['username']) ?>
                <span class='w3-tag w3-round w3-red'>
                    <?php
                        if (!empty($users_row['alert']) && $users_row['alert'] != 0) { 
                            echo (int)$users_row['alert'];
                        }
                    ?>
                </span>
            </button>
            <div class='gr8-theme w3-light-grey w3-card-2 w3-dropdown-content'>
                <a href='/acc' class='w3-bar-item w3-button'>
                <span class='fa fa-cog' aria-hidden='true'></span>SETTINGS</a>
                <a href="/user/<?php echo $users_row['id'] ?>" class='w3-bar-item w3-button'><i class='fa fa-user-o' aria-hidden='true'></i>PROFILE</a>
                <a href='/acc/new' class='w3-bar-item w3-button'><i class='fa fa-inbox' aria-hidden='true'></i>NOTIFICATIONS <span class='w3-tag w3-round w3-red'><?php echo (int)$users_row['alert'] ?>
                </span></a>
                <a href='/acc/creations' class='w3-bar-item w3-button'><i class='fa fa-building' aria-hidden='true'></i>MY CREATIONS</a>
                <a href='/acc/following' class='w3-bar-item w3-button'><i class='fa fa-user-plus' aria-hidden='true'></i>PEOPLE</a>
                <a href='/acc/logins' class='w3-bar-item w3-button'><i class='fa fa-lock' aria-hidden='true'></i>SESSIONS</a>
                <a href='/acc/login?status=logout' class='w3-bar-item w3-button'><i class='fa fa-sign-out' aria-hidden='true'></i>LOGOUT</a>
            </div>
        </div>
    <?php } else { ?>
        <a href='/acc/login'><i class='fa fa-sign-in' aria-hidden='true'></i>LOGIN</a>
        <a href='/acc/register'><i class='fa fa-user-plus' aria-hidden='true'></i>REGISTER</a>
    <?php } ?>

    <hr /><div class="featured-builds" style="display: none;">
        <span class="w3-padding">Featured&nbsp;<span class="w3-blue w3-tag">NEW</span></span><br />
    </div>            
</nav>

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
    <a href="/index.php"><span class="fa fa-home w3-xlarge w3-padding-small"></span></a>
    <a href="/modeler.php"><span class="fa fa-cubes w3-xlarge w3-padding-small"></span></a>
    <a href="/list.php"><span class="fa fa-building-o w3-xlarge w3-padding-small"></span></a>
    <a href="/com/index.php"><span class="fa fa-commenting-o w3-xlarge w3-padding-small"></span></a>
    <a href="http://blog.gr8brik.rf.gd/index"><span class="fa fa-pencil-square-o w3-xlarge w3-padding-small"></span></a>
    <a href="/acc/index.php"><span class="fa fa-user-o w3-xlarge w3-padding-small"></span></a>
</div>
    
<div class="w3-main"><br />

<div class="w3-card-2 w3-light-grey w3-padding-tiny"><i class="fa fa-info-circle" aria-hidden="true"></i><b>A new modeler is out in Alpha now. You can use it <a href="/modeler.php">here</a>.</b></div><br />