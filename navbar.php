<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';
    if(isset($_SESSION['username'])) {
        $home = "/feed.php";
    } else {
        $home = "/index.php";
    }
?>

<nav id="navbar" class="w3-sidenav w3-top w3-bar-block w3-hide-small w3-show-medium w3-large w3-card-2 w3-light-grey w3-padding-tiny">

    <a href="<?php echo $home ?>">
        <b><img src="/img/logo.jpg" style="width:25px;height:25px;border-radius:15%;border:1px solid #000;">GR8BRIK</b>
    </a>

	<a href="/modeler"><i class="fa fa-cubes" aria-hidden="true"></i>MODELER</a>

	<a href="/list"><i class="fa fa-building-o" aria-hidden="true"></i>CREATIONS</a>

	<a href="/com/"><i class="fa fa-commenting-o" aria-hidden="true"></i>COMMUNITY</a>

    <a href="http://blog.gr8brik.rf.gd/" target="_blank"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>BLOG</a>

    <input class="w3-input w3-border w3-threequarter" type="text" id="search-input" placeholder="Search for...">
    <button class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border w3-quarter" style="height:5%" id="search-button"><i class="fa fa-search" aria-hidden="true"></i></button><br />

	<?php
		
		if(!isset($_SESSION['username'])){

			echo "<a href='/acc/login'><i class='fa fa-sign-in' aria-hidden='true'></i>LOGIN</a>";
            echo "<a href='/acc/register'><i class='fa fa-user-plus' aria-hidden='true'></i>REGISTER</a>";

        } else {

            echo "<div class='w3-dropdown-hover'><button class='w3-button'><i class='fa fa-angle-down' aria-hidden='true'></i>" . strtoupper($user) . "<span class='w3-tag w3-red w3-round'>" . $alert . "</span></button>";
            echo "<div class='w3-dropdown-content w3-bar-block'><a href='/@" . urlencode(strtolower($user)) . "' class='w3-bar-item w3-button'><i class='fa fa-user-o' aria-hidden='true'></i>PROFILE</a>";
            echo "<a href='/acc' class='w3-bar-item w3-button'><i class='fa fa-cog' aria-hidden='true'></i>SETTINGS</a>";
            echo "<a href='/acc/new' class='w3-bar-item w3-button'><i class='fa fa-inbox' aria-hidden='true'></i>NOTIFICATIONS</a>";
                echo "<a href='/acc/login?status=logout' class='w3-bar-item w3-button'><i class='fa fa-sign-out' aria-hidden='true'></i>LOGOUT</a>";
            echo "</div></div>";

            /*if($alert > 0) {
                echo "<a href='/acc/new'><i class='fa fa-user-o' aria-hidden='true'></i>" . strtoupper($user) . "<span class='w3-tag w3-red'>" . $alert . "</span></a>";
            } else {
                echo "<a href='/acc/index'><i class='fa fa-user-o' aria-hidden='true'></i>" . strtoupper($user) . "</a>";
            }
			echo "<a href='/acc/login?status=logout'><i class='fa fa-sign-out' aria-hidden='true'></i>LOGOUT</a>"; */

        }
		
    ?>

</nav>

    <script>
    $(document).ready(function() {
        function loadSearch() {
            var searchTerm = $('#search-input').val();
            if (searchTerm.length > 1) {
                $.ajax({
                    url: '/list.php',
                    type: 'GET',
                    data: { q: searchTerm },
                    success: function(response) {
                        var goUrl = "/list.php?q=" + encodeURIComponent(searchTerm);
                        $("body").load(goUrl, function() {
                            history.pushState(null, "", goUrl);
                            function getCookie(name) {
                                function escape(s) { return s.replace(/([.*+?\^$(){}|\[\]\/\\])/g, '\\$1'); }
                                var match = document.cookie.match(RegExp('(?:^|;\\s*)' + escape(name) + '=([^;]*)'));
                                return match ? match[1] : '';
                            }
                            if (getCookie("theme") === "dark") {
                                $("body").addClass("w3-dark-grey");
                                $("#navbar").removeClass("w3-light-grey");
                                $("#navbar").addClass("w3-black");   
                            }
                            document.title = "Gr8brik.rf.gd | List";
                            window.scrollTo(0, 0);
                        });
                    }
                });
            } else {
                alert('Search term must be more than two characters.');
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
    });
    </script>

    <div class="w3-top w3-padding-small w3-xlarge w3-navbar w3-hide-large w3-hide-medium w3-card-2 w3-black" style="width:100%;display:block;flex-direction:row;">
        <a href="/" class="fa fa-home w3-padding-tiny" aria-hidden="true"></a>
        <a href="/modeler" class="fa fa-cubes w3-padding-tiny" aria-hidden="true"></a>
        <a href="/list" class="fa fa-building-o w3-padding-tiny" aria-hidden="true"></a>
        <a href="/com" class="fa fa-commenting-o w3-padding-tiny" aria-hidden="true"></a>
        <?php
        if(isset($_SESSION['username'])){
            if($alert > 0) {
                echo '<a href="/acc/new" class="fa fa-user-o" aria-hidden="true"></a><span class="fa fa-plus w3-small w3-red" aria-hidden="true"></span>';
            } else {
                echo '<a href="/acc" class="fa fa-user-o" aria-hidden="true"></a>';
            }
        } else {
            echo '<a href="/acc" class="fa fa-sign-in" aria-hidden="true"></a>';
        }
        ?>
    </div><br /><br />

	<div class="w3-main">
    
        <div class="loading w3-top w3-padding-small w3-card-2 w3-blue" style="width:0%; display:none;"></div>
        
        <div id="cookie-message" class="w3-card-2 w3-light-grey w3-padding-tiny">
            <img src="../img/info.jpg" style="width:20px;height=20px;"><b>Gr8brik uses cookies to ensure we give you the best experience on our website. <a href="/privacy" target="_blank">Learn more</a></b>
        </div>

        <a href="/modeler" style="width:50px;height:50px;position:fixed;z-index:1;bottom: 0;" class="w3-btn-floating w3-blue w3-hover-white w3-card-24">+</a>