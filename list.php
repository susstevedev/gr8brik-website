<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>List</title>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="lib/theme.css">
    <script src="lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <meta charset="UTF-8">
    <meta name="description" content="Gr8brik is a block building browser game. No download required">
    <meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no"><!-- ios support -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/img/logo.jpg" />
    <meta name="apple-mobile-web-app-status-bar" content="#f1f1f1" />
    <meta name="theme-color" content="#f1f1f1" />
</head>
<body class="w3-light-blue w3-container">

<?php 

    include('navbar.php');
    
?>

        <h1>Creations</h1>
        <div class="w3-container">
                <p>Want to build your own model? <a href="modeler" class="w3-btn w3-blue w3-hover-white">Open Modeler</a></p>
        </div>

        <input class="w3-input w3-border w3-threequarter" type="text" value="<?php if (isset($_GET['q'])) { echo $_GET['q']; } ?>" id="search-input-2" placeholder="Search for...">
        <button class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border w3-quarter" id="search-button-2"><i class="fa fa-search" aria-hidden="true"></i></button><br /><br />

        <script>
            $(document).ready(function() {
                function loadSearch() {
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

        <table class="w3-table-all" style="color:black;">
        
        <?php
				
			define('DB_NAME2', 'if0_36019408_creations');
			
			$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
            $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
			if ($conn->connect_error || $conn2->connect_error) {
				die("Error: " . $conn->connect_error . ", " . $conn2->connect_error);
			}
			if (isset($_GET['q']) && $_GET['q']) {
                $query = htmlspecialchars($_GET['q']);
                $searchPattern = "%" . $query . "%";
				$sql = "SELECT * FROM model WHERE name LIKE '$searchPattern'";
                $result2 = $conn->query($sql);

                echo "<p>Search results for <b>" . $query . "</b></p><br />";
                while ($row = $result2->fetch_assoc()) {
                    $userid = $row['user'];
                    $result3 = $conn2->query("SELECT * FROM users WHERE id = $userid");
                    $user = $result3->fetch_assoc();

                    echo "<div class='w3-display-container w3-left w3-padding-small'>";
                    echo "<a href='creation.php?id=" . $row['id'] . "'><img src='/cre/" . $row['screenshot'] . "' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-border'></a>";
                    echo "<h4 class='w3-display-middle w3-text-grey'>" . $row['name'] . "</h4>";
                    echo "<b class='w3-display-bottommiddle w3-text-grey'>By <a href='/user/" . urlencode($user['username']) . "'>" . $user['username'] . "</a></b></div>";
                }
			}
			if (!isset($_GET['q']) && !($_GET['q'])) {
                $sql = "SELECT * FROM model ORDER BY date DESC";
                $result = $conn->query($sql);

                while ($row = $result->fetch_assoc()) {
                    $userid = $row['user'];
                    $sql2 = "SELECT * FROM users WHERE id = $userid";
                    $result2 = $conn2->query($sql2);
                    $user = $result2->fetch_assoc();

                    echo "<div class='w3-display-container w3-left w3-padding-small'>";
                    echo "<a href='creation.php?id=" . $row['id'] . "'><img src='cre/" . $row['screenshot'] . "' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-border'></a>";
                    echo "<h4 class='w3-display-middle w3-text-grey'>" . $row['name'] . "</h4>";
                    echo "<b class='w3-display-bottommiddle w3-text-grey'>By <a href='/user/" . urlencode($user['username']) . "'>" . $user['username'] . "</a></b></div>";
                }
            }

        ?>
			
        </table><br /><br />

    <?php include('linkbar.php'); ?>


</body>
</html>