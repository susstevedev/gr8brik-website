<nav id="navbar" class="w3-sidenav w3-top w3-bar-block w3-hide-small w3-hide-medium w3-large w3-card-2 w3-light-grey w3-padding-tiny">

    <a href="/"><img src="/img/logo.jpg" style="width:25px;height:25px;border-radius:50%;">GR8BRIK</a>

	<a href="/modeler"><i class="fa fa-cubes" aria-hidden="true"></i>MODELER</a>

	<a href="/list"><i class="fa fa-building-o" aria-hidden="true"></i>CREATIONS</a>

	<a href="/com/"><i class="fa fa-commenting-o" aria-hidden="true"></i>COMMUNITY</a>

    <a href="http://blog.gr8brik.rf.gd/" target="_blank"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>BLOG</a>

    <div style="color: #000; display: inline-flex; height: 32px; padding-right: 0px; padding-left: 10px; border-radius: 6px;">
        <a class="fa fa-search" style="background-color: #fff; cursor: pointer; padding-right: 6px; padding-left: 6px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);" id="search-button"></a>
        <input class="w3-input w3-border" type="text" id="search-input" placeholder="Search for...">
    </div><hr />

	<?php
		
		if(!isset($_SESSION['username'])){

			echo "<a href='/acc/login'><i class='fa fa-sign-in' aria-hidden='true'></i>LOGIN</a>";
            echo "<a href='/acc/register'><i class='fa fa-user-plus' aria-hidden='true'></i>REGISTER</a>";

        } else {

            echo "<div class='w3-dropdown-hover'><button class='w3-button'><i class='fa fa-angle-down' aria-hidden='true'></i>" . strtoupper($user);
            if($alert != 0) {
                echo "&nbsp;<span class='w3-tag w3-circle w3-red'>" . $alert . "</span>";
            }
            echo "</button>";
            echo "<div class='w3-dropdown-content w3-bar-block'><a href='/acc' class='w3-bar-item w3-button'><i class='fa fa-cog' aria-hidden='true'></i>SETTINGS</a>";
            echo "<a href='/@" . urlencode($user) . "' class='w3-bar-item w3-button'><i class='fa fa-user-o' aria-hidden='true'></i>PROFILE</a>";
            echo "<a href='/acc/new' class='w3-bar-item w3-button'><i class='fa fa-inbox' aria-hidden='true'></i>NOTIFICATIONS&nbsp;<span class='w3-tag w3-circle w3-red'>" . $alert . "</span></a>";
            echo "<a href='/acc/creations' class='w3-bar-item w3-button'><i class='fa fa-building' aria-hidden='true'></i>MY CREATIONS</a>";
            echo "<a href='/acc/following' class='w3-bar-item w3-button'><i class='fa fa-user-plus' aria-hidden='true'></i>PEOPLE</a>";
            echo "<a href='/acc/login?status=logout' class='w3-bar-item w3-button'><i class='fa fa-sign-out' aria-hidden='true'></i>LOGOUT</a>";
            echo "</div></div>";

        }
		
    ?>
</nav>

    <nav id="sidebar" class="gr8-theme w3-sidenav w3-top w3-bar-block w3-hide-small w3-hide-medium w3-large w3-card-2 w3-light-grey w3-padding-tiny">
        <p>Popular right now</p>
        <p>Newest models with rising view counts.</p>
            <?php
                $connSidebar = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
                $connSidebar2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
                $resultSidebar = $connSidebar->query("SELECT * FROM model ORDER BY date DESC, views DESC");

                $i = 0;
                if(!isset($_SESSION['username'])) {
                    echo "<b>Error</b>";
                } else {
                    while ($rowSidebar = $resultSidebar->fetch_assoc()) {
                        $user_id = $rowSidebar['user'];
                        $resultSidebar2 = $connSidebar2->query("SELECT * FROM users WHERE id = $user_id");
                        $userResultSidebar = $resultSidebar2->fetch_assoc();

                        if(empty($userResultSidebar['username'])) {
                            continue;
                        }

                        echo "<div class='w3-border w3-border-white'>";
                        echo "<a href='/build/" . $rowSidebar['id'] . "'><img src='/cre/" . $rowSidebar['screenshot'] . "' width='200' height='100' loading='lazy' class='w3-hover-shadow w3-border w3-round'></a>";
                        echo "<h4 style='text-overflow: ellipsis;' class=''>" . $rowSidebar['name'] . "</h4>";
                        echo "<span class=''>" . $rowSidebar['views'] . " views</span>";
                        echo "<a href='/@" . urlencode(strtolower($userResultSidebar['username'])) . "'>" . $userResultSidebar['username'] . "</a></div><br />";
                        if (++$i == 6) break;
                    }
                }
            ?>
    </nav>

    <script>
    $(document).ready(function() {
        function checkCookieExists(n) {
            if (document.cookie.split(';').some((item) => item.trim().startsWith(n + '='))) {
                return true;
            }
            return false;
        }

        function loadSearch() {
            var searchTerm = $('#search-input').val();
            if (searchTerm.length > 2) {
                $.ajax({
                    url: '/list.php',
                    type: 'GET',
                    data: { q: searchTerm },
                    success: function(response) {
                        window.location.href = "/list?q=" + encodeURIComponent(searchTerm);
                        if (checkCookieExists('mode')) {
                            $("body").removeClass("w3-light-blue");
                            $("body").css({"background-color": "#121212", "color": "#FAF9F6"});
                            $('#navbar').removeClass('w3-light-grey').addClass('w3-dark-grey'); 
                            $('.gr8-theme').removeClass('w3-light-grey gr8-theme').addClass('w3-dark-grey w3-text-white'); 
                        }
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

        function getXMLHR(url, element) {
            $.ajax({
                url: url,
                type: 'GET',
                async: false,
                success: function(data) {
                    $(element).html(data);
                    if (checkCookieExists('mode')) {
                        $("body").removeClass("w3-light-blue");
                        $("body").css({"background-color": "#121212", "color": "#FAF9F6"});
                        $('#navbar').removeClass('w3-light-grey').addClass('w3-dark-grey'); 
                        $('.gr8-theme').removeClass('w3-light-grey gr8-theme').addClass('w3-dark-grey w3-text-white'); 
                    }
                }
            });
        }

        const pullToRefresh = $('.pull-to-refresh');
        let touchstartY = 0;

        $(document).on('touchstart', function(e) {
            e.preventDefault();
            touchstartY = e.touches[0].clientY;
        });

        $(document).on('touchmove', function(e) {
            let touchY = e.touches[0].clientY;
            let touchDiff = touchY - touchstartY;
            if (touchDiff > 0 && $(window).scrollTop() === 0) {
                pullToRefresh.addClass('visible');
            }
        });

        $(document).on('touchend', function() {
            if (pullToRefresh.hasClass('visible')) {
                pullToRefresh.removeClass('visible');
                getXMLHR(location.pathname + location.search, 'body');
            }
        });

        $(window).on('error', function(error) {
            getXMLHR('/oops.html', 'html');
            console.error(error);
        });

    });
    
    </script>

    <div id="mobilenav" class="w3-padding-small w3-xlarge w3-navbar w3-hide-large w3-show-medium w3-card-2 w3-black w3-left" style="width: 100%; flex-direction: row; vertical-align: top; position: relative; z-index: 999;">
        <a href="/" class="w3-padding-tiny"><img src="/img/logo.jpg" style="width:25px;height:25px;border-radius:50%;" alt="Home"></a>
        <a href="/modeler" class="fa fa-cubes w3-padding-tiny" alt="Modeler"></a>
        <a href="/list" class="fa fa-building-o w3-padding-tiny" alt="Creations"></a>
        <a href="/com" class="fa fa-commenting-o w3-padding-tiny" alt="Community"></a>
        <?php
        if(isset($_SESSION['username'])){
            echo '<a href="/acc/" class="fa fa-user-o" alt="You"></a>';
            if($alert > 0) {
                echo '<span class="fa fa-plus w3-red" alt="+">' . $alert . '</span>';
            }
        } else {
            echo '<a href="/acc/" class="fa fa-sign-in" alt="Sign in or Register"></a>';
        }
        ?>
    </div><br /><br />

	<div class="w3-main">

        <div class="pull-to-refresh">
            <center>
                <img src="/img/loading.gif" width="15%" height="15%">
            </center>
        </div>

        <div class="loading w3-top w3-padding-tiny w3-card-2 w3-blue" style="width: 0%; display: none;"></div>

        <div class="w3-card-2 w3-red w3-padding-tiny w3-hide">
            <img src="../img/warning.jpg" style="width:20px;height=20px;"><b>Error: </b>
        </div><br />
                    
        <div id="cookie-message" class="w3-card-2 w3-light-grey w3-padding-tiny">
            <img src="../img/info.jpg" style="width:20px;height=20px;"><b>Gr8brik uses cookies to ensure we give you the best experience on our website. <a href="/privacy" target="_blank">Learn more</a></b>
        </div>

        <button id="toggleModeMenu" class="w3-btn w3-blue w3-hover-white w3-circle w3-border w3-border-black w3-bottom w3-hide-large" style="width: 50px; height: 50px;">
            <span class="fa fa-lightbulb-o w3-xlarge" alt="Mode" aria-hidden="true"></span>
        </button>

        <div id="modeMenu" class="w3-padding w3-round w3-card-2 w3-border w3-border-white w3-black" style="display: none;">
            <span class="w3-large">DISPLAY COLORS</span><br />
            <button id="toggleDark" class="w3-btn w3-large w3-light-blue w3-hover-dark-grey"><i class="fa fa-moon-o" aria-hidden="true"></i>Night</button>
            <button id="toggleLight" class="w3-btn w3-large w3-dark-grey w3-hover-light-blue"><i class="fa fa-lightbulb-o" aria-hidden="true"></i>Day</button>
        </div>