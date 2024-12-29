<!DOCTYPE html>
<html lang="en">
<head>
    <title>List</title>
    <?php include 'header.php' ?>
</head>
<body class="w3-light-blue w3-container">

        <?php 
            require_once 'ajax/user.php';
            require_once 'ajax/bbcode.php';
            $bbcode = new BBCode;

            if(isset($_SESSION['username'])) {
                $id = $_SESSION['username'];
                $row = fetchUser($id);

                $user = $row['username'];
                $pwd = $row['password'];
                $email = $row['email'];
                $about = $row['description'];
                $x = $row['twitter'];
                $admin = $row['admin'];
                $alert = $row['alert'];
                $age = $row['age'];
                $pic = $row['image'];
            }
            
            include 'navbar.php';
        ?>

        <h2>Creations</h2>
        <div class="w3-container w3-border">
            <p>Want to build your own model? <a href="modeler" class="w3-btn w3-blue w3-hover-white w3-border w3-border-indigo">Open Modeler</a></p>
        </div><br />

        <input class="w3-input w3-border w3-threequarter" type="text" value="<?php if (isset($_GET['q'])) { echo $_GET['q']; } ?>" id="search-input-2" placeholder="Search for...">
        <button class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border w3-quarter" id="search-button-2"><i class="fa fa-search" aria-hidden="true"></i></button><br /><br />

        <script>
            $(document).ready(function() {
                function loadSearch() {
                    document.title = "";
                    var searchTerm = $('#search-input-2').val();
                        $.ajax({
                            url: '/list.php',
                            type: 'GET',
                            data: { q: searchTerm },
                            success: function(response) {
                                var goUrl = "/list?q=" + encodeURIComponent(searchTerm);
                                $("body").load(goUrl, function() {
                                    history.pushState(null, "", goUrl);
                                    if (localStorage.getItem('mode')) {
                                        $("body").addClass("w3-dark-grey");
                                        $('#navbar').removeClass('w3-light-grey').addClass('w3-black'); 
                                    }
                                    document.title = $('title').text();
                                    window.scrollTo(0, 0);
                                });
                            }
                        });
                }

                $('#search-input-2').on('keyup', function(e) {
                    if (e.keyCode === 13) {
                        loadSearch();
                    }
                })
                $('#search-button-2').on('click', function() {
                    loadSearch();
                });
            });
        </script>

        <form method="get">
            <label for="sort"><b>Sort options:</b></label>
            <select name="sort" id="sort">
                <option value="views" selected>Most viewed</option>
                <option value="az" selected>Alphabetical A-Z</option>
                <option value="za" selected>Alphabetical Z-A</option>
                <option value="oldest" selected>Oldest creations</option>
            </select>
            <input class="w3-btn w3-blue w3-hover-white w3-border w3-border-indigo" type="submit" value="Apply Filters">
        </form><br />

        <table class="w3-table-all" style="color:black;">
        
        <?php
							
			$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
            $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
            $sql = "SELECT * FROM model ORDER BY date DESC";
			
			if (isset($_GET['q']) && $_GET['q']) {
                $query = htmlspecialchars($_GET['q']);
                $searchPattern = "%" . $query . "%";
				$sql = "SELECT * FROM model WHERE name LIKE '$searchPattern'";
                echo "<p>Search results for <b>" . $query . "</b></p><br />";
            }

            if (isset($_GET['sort']) && $_GET['sort']) {
                if($_GET['sort'] === 'views') {
                    $sql = "SELECT * FROM model ORDER BY views DESC";
                    $sort = "Most viewed";
                }
                if($_GET['sort'] === 'az') {
                    $sql = "SELECT * FROM model ORDER BY name ASC";
                    $sort = "Alphabetical A-Z";
                }
                if($_GET['sort'] === 'za') {
                    $sql = "SELECT * FROM model ORDER BY name DESC";
                    $sort = "Alphabetical Z-A";
                }
                if($_GET['sort'] === 'oldest') {
                    $sql = "SELECT * FROM model ORDER BY date ASC";
                    $sort = "Oldest creations";
                }
                if(!empty($sort)) {
                    echo "<p>Sorting by <b>" . $sort . "</b></p><br />";
                }
            }

            $result2 = $conn2->query($sql);
            while ($row = $result2->fetch_assoc()) {
                $model_id = $row['id'];
                $userid = $row['user'];
                $result3 = $conn->query("SELECT * FROM users WHERE id = $userid");
                $user = $result3->fetch_assoc();
                $username = isset($user['username']) ? $user['username'] : '';

                $result4 = $conn2->query("SELECT COUNT(*) as count FROM votes WHERE creation = $model_id");
                $likes = $result4->fetch_assoc();

                $banResult = $conn->query("SELECT * FROM bans WHERE user = $userid");
                $banRow = $banResult->fetch_assoc();

                if(empty($row['name'])) {
                    $row['name'] = $username . "'s creation";
                }

                if(empty($user['username']) || $banRow['end_date'] >= time() || !file_exists('cre/' . $row['screenshot'])) {
                    $row['screenshot'] = "../img/no_image.png";
                }

                $truncatedName = substr($row['name'], 0, 20);
                if (strlen($row['name']) > 20) {
                    $truncatedName .= "...";
                }

                echo "<div class='w3-display-container w3-left w3-padding'>";
                echo "<a href='/build/" . $row['id'] . "'><img src='/cre/" . $row['screenshot'] . "' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-border w3-border-white'></a>";
                echo "<span class='gr8-theme w3-large w3-display-middle w3-card-2 w3-light-grey w3-padding-small'>" . $truncatedName . "</span>";
                echo "<span class='gr8-theme w3-display-bottommiddle w3-card-2 w3-light-grey w3-padding-small'>" . $row['views'] . " views - " . $likes['count'] . " likes - By "; 
                echo "<a href='/user/" . $row['user'] . "'>" . $username . "</a></span></div>";
            }

        ?>
			
        </table><br /><br />

    <?php include('linkbar.php'); ?>


</body>
</html>