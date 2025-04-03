<nav id="navbar" class="w3-sidenav w3-top w3-bar-block w3-hide-small w3-hide-medium w3-large w3-card-2 w3-hover-shadow w3-light-grey w3-padding-tiny">

    <a href="/"><img src="/img/logo.jpg" style="width:25px;height:25px;border-radius:50%;">GR8BRIK&nbsp;</b><span class="w3-tag w3-round w3-green">ALPHA</span></a>

	<a href="/modeler"><i class="fa fa-cubes" aria-hidden="true"></i>Modeler</a>

	<a href="/list"><i class="fa fa-building-o" aria-hidden="true"></i>Creations</a>

	<a href="/com/"><i class="fa fa-commenting-o" aria-hidden="true"></i>Community</a>

    <a href="http://blog.gr8brik.rf.gd/" target="_blank"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>Blog</a>

    <div style="color: #000; display: inline-flex; height: 32px; padding-right: 0px; padding-left: 10px; border-radius: 6px;">
        <a class="fa fa-search gr8-navbarsearch" style="background-color: #fff; cursor: pointer; padding-right: 6px; padding-left: 6px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);" id="search-button"></a>
        <input class="w3-input w3-border" type="text" id="search-input" placeholder="Search for...">
    </div><hr />

    <?php if(isset($_COOKIE['token'])) { ?>
        <div class="user-menu">
            <div class='w3-dropdown-hover'>
                <button class='w3-button gr8-theme'>
                    <i class='fa fa-angle-down' aria-hidden='true'></i>
                    <span>
                        <i>@</i><span id='gr8-user'><?php echo $users_row['username'] ?></span>
                        <span class='gr8-notif-quick w3-tag w3-round w3-red'>
                            <?php
                                if (!empty($users_row['alert']) && $users_row['alert'] != 0) { 
                                    echo (int)$users_row['alert'];
                                }
                            ?>
                        </span>
                    </span>
                </button>
                <div class='gr8-theme w3-border w3-card-2 w3-light-grey w3-dropdown-content w3-bar-block'>
                    <a href='/acc' class='w3-bar-item w3-button'>
                    <span class='fa fa-cog' aria-hidden='true'></span>Settings</a>
                    <a id='gr8-profilelink' href="/user/<?php echo $token['user'] ?>" class='w3-bar-item w3-button'><i class='fa fa-user-o' aria-hidden='true'></i>Profile</a>
                    <a href='/acc/new' class='w3-bar-item w3-button'><i class='fa fa-inbox' aria-hidden='true'></i>Notifications<span class='gr8-notif w3-tag w3-round w3-red'>
                    <?php
                        if (!empty($users_row['alert']) && $users_row['alert'] != 0) { 
                            echo (int)$users_row['alert'];
                        }
                    ?>
                    </span></a>
                    <a href='/acc/creations' class='w3-bar-item w3-button'><i class='fa fa-building' aria-hidden='true'></i>My Creations</a>
                    <a href='/acc/following' class='w3-bar-item w3-button'><i class='fa fa-user-plus' aria-hidden='true'></i>People</a>
                    <a href='/acc/logins' class='w3-bar-item w3-button'><i class='fa fa-lock' aria-hidden='true'></i>Sessions</a>
                    <a href='/acc/login?status=logout' class='w3-bar-item w3-button'><i class='fa fa-sign-out' aria-hidden='true'></i>Logout</a>
                </div>
            </div>
        </div>
    <?php } else { ?>
        <div class="login-menu">
            <a href='/acc/login'><i class='fa fa-sign-in' aria-hidden='true'></i>Login</a>
            <a href='/acc/register'><i class='fa fa-user-plus' aria-hidden='true'></i>Register</a>
        </div>
    <?php } ?>

    <hr /><div class="featured-builds" style="display: none;">
        <span class="w3-padding">Featured&nbsp;<span class="w3-blue w3-tag">NEW</span></span><br />
    </div>            
    </nav>

    <script>
    $(document).ready(function() {
        function loadSearch() {
            var searchTerm = $('#search-input').val();
            if (searchTerm.length > 2) {
                $.ajax({
                    url: '/list.php',
                    type: 'GET',
                    data: { q: searchTerm },
                    success: function(response) {
                        window.location.href = "/list?q=" + encodeURIComponent(searchTerm).replace(/%20/g, "+");
                    }
                });
            } else {
                alert('Search query must be more than three characters');
            }
        }

        $('#search-input').on('keyup', function(e) {
            if (e.keyCode === 13) {
                loadSearch();
            }
        })
        $('#search-button').on('click', function() {
            loadSearch();
        });

        $("#toggleModeMenu").click(function(event) {     
            $("#modeMenu").toggle();
	    });
        
        window.loadFeaturedCreations();
    });
    </script>

    <div id="mobilenav" class="w3-hide-large w3-light-grey gr8-theme w3-card-2 w3-xlarge w3-show-medium w3-bottom w3-padding w3-center" style="width: 100%; z-index: 1000;">
        <a href="/" class="fa fa-home w3-padding" alt="Homepage" title="Homepage"></a>
        <a href="/modeler" class="fa fa-cubes w3-padding" alt="Modeler" title="Modeler"></a>
        <a href="/list" class="fa fa-building-o w3-padding" alt="Creations" title="Creations"></a>
        <a href="/com" class="fa fa-commenting-o w3-padding" alt="Community" title="Community"></a>
        <a href="http://blog.gr8brik.rf.gd/index" class="fa fa-pencil-square-o w3-padding" alt="Gr8brik Blog" title="Gr8brik Blog"></a>
    </div>

    <!-- <div class="loading">
        <img class="refresh-loading" src="/img/loading.gif" style="width: 25px; height: 25px;" />
    </div> 
    
    <div class="consoleErrorBox w3-card-2 w3-red w3-padding-tiny" style="display: none;">
        <i class="fa fa-times-circle-o" aria-hidden="true"></i><b class="consoleError"></b>
    </div><br /> -->
                    
    <button id="toggleModeMenu" class="w3-right w3-btn w3-blue w3-hover-white w3-circle w3-border w3-border-indigo w3-bottom w3-hide-large w3-fixed w3-margin-bottom"
        style="width: 50px; height: 50px; bottom: 60px; z-index: 1000;">
        <span class="fa fa-lightbulb-o w3-xlarge" alt="Mode" aria-hidden="true"></span>
    </button>

    <div class="w3-hide-large w3-light-grey gr8-theme w3-card-2 w3-xlarge w3-show-medium w3-top w3-padding-small" style="width: 100%; max-width: 100%; z-index: 1000;">
        <a href="/?src=mobile_home_button" alt="Gr8brik Logo" class="w3-left w3-padding">
            <img src="/img/logo.jpg" style="width:35px;height:35px;border-radius:50%;">
        </a>

        <?php if(isset($_COOKIE['token'])) { ?>
            <a href="/acc" alt="You" class="w3-right w3-padding">
                <img src="/acc/users/pfps/<?php echo $id; ?>.jpg" style="width:35px;height:35px;border-radius:50%;">
            </a>
            <?php if($alert > 0) { ?>
                <span class="fa fa-plus w3-red" alt="+"><?php echo $alert; ?></span>
            <?php } ?>
        <?php } else { ?>
            <a href="/acc/" class="fa fa-sign-in" alt="Sign in or Register"></a>
        <?php } ?>
    </div><br /><br />

    <div id="modeMenu" class="w3-padding-small w3-card-2 gr8-theme w3-light-grey" style="display: none;">
        <span class="w3-large">DISPLAY COLORS</span><br />

        <button id="toggleDark" class="w3-btn w3-large w3-light-blue w3-hover-dark-grey">
            <span class="fa fa-moon-o" aria-hidden="true">Night</span>
        </button>

        <button id="toggleLight" class="w3-btn w3-large w3-dark-grey w3-hover-light-blue">
            <span class="fa fa-lightbulb-o" aria-hidden="true">Day</span>
        </button>

        <button id="toggleAuto" class="w3-btn w3-large w3-black w3-hover-white">
            <span class="fa fa-moon-o" aria-hidden="true">Auto</span>
        </button>
    </div>
    
	<div id="w3-main" class="w3-main">
        <div class="w3-card-2 w3-light-grey w3-padding-tiny"><i class="fa fa-info-circle" aria-hidden="true"></i><b>A new modeler is out in Alpha now. You can use it <a href="/new-modeler.php">here</a>.</b></div><br />

        <!-- <div id="logout-modal" class="w3-modal">
            <div class="w3-modal-content w3-animate-top w3-card-24 w3-light-grey w3-center w3-round w3-padding">
                <div class="w3-container">
                    <span onclick="document.getElementById('logout-modal').style.display='none'" class="w3-closebtn w3-red w3-hover-white w3-padding-small w3-round-small w3-display-topright">&times;</span>
                    <form method='post' action=''>
                        <h2>Are you sure you want to logout?</h2>
                        <span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('logout-modal').style.display='none'">No</span>&nbsp;
                        <a href="/acc/login?status=logout" class="w3-btn w3-large w3-white w3-hover-red">Yes</a>
                    </form>
                </div>
            </div>
        </div>

        <div id="very-important-modal" class="w3-modal" style="display: block;">
            <div class="w3-modal-content w3-animate-zoom w3-card-2 w3-light-grey w3-center">
                <div class="w3-container">
                    <span onclick="document.getElementById('very-important-modal').style.display='none'" class="w3-closebtn w3-red w3-hover-white w3-padding w3-display-topright">&times;</span>
                    <form method='post' action=''>
                        <h2>Important Notice</h2>
                        <p>Gr8brik is being sold to XAI, a small company of only a billion dollars, made by Elon Musk.</p>
                        <span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('very-important-modal').style.display='none'">Okay/Close</span>&nbsp;
                        <a href="/important" class="w3-btn w3-large w3-white w3-hover-blue">Read more</a>
                    </form>
                </div>
            </div>
        </div> -->