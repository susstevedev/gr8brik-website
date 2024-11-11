<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Community</title>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="../lib/theme.css">
    <script src="../lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <meta charset="UTF-8">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no"><!-- ios support -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/img/logo.jpg" />
    <meta name="apple-mobile-web-app-status-bar" content="#f1f1f1" />
    <meta name="theme-color" content="#f1f1f1" />
</head>

<body class="w3-light-blue w3-container">
    <?php include('../navbar.php'); ?>

        <div class="w3-center">
		
			<p>Wanna ask a question, or post something funny?</p><a href="post" class="w3-btn w3-blue w3-hover-white">Post Topic</a><br /><br />
			
            <input class="w3-input w3-border w3-threequarter" value="<?php if (isset($_GET['q'])) { echo $_GET['q']; } ?>" type="text" id="search-input-2" placeholder="Search for...">
            <button class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border w3-quarter" id="search-button-2"><i class="fa fa-search" aria-hidden="true"></i></button><br /><br />

            <?php
                define('DB_NAME3', 'if0_36019408_forum');
			    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = 8;
                $offset = ($page - 1) * $limit;
                $pDown = $page - 1;
                $pUp = $page + 1;

                $count_result = $conn->query("SELECT COUNT(*) as post_count FROM posts");
                $count_row = $count_result->fetch_assoc();
                $post_count = $count_row['post_count'];
                $total_pages = ceil($post_count / $limit);

                echo '<h3>' . $post_count . ' posts, ' . $total_pages . ' pages. Showing page ' . $page . '.</h3>';
                echo '<a class="w3-btn w3-blue w3-hover-white" href="?page=' . $pDown . '">Back</a>&nbsp;&nbsp;';
                echo '<a class="w3-btn w3-blue w3-hover-white" href="?page=' . $pUp . '">Next</a>';
            ?>

            <script>
            $(document).ready(function() {
                function loadSearch() {
                    var searchTerm = $('#search-input-2').val();
                        $.ajax({
                            url: '/com/search.php',
                            type: 'GET',
                            data: { q: searchTerm },
                            success: function(response) {
                                var goUrl = "/com/search?q=" + encodeURIComponent(searchTerm);
                                $("body").load(goUrl, function() {
                                    history.pushState(null, "", goUrl);
                                    if (localStorage.getItem('mode')) {
                                        $("body").addClass("w3-dark-grey");
                                        $('#navbar').removeClass('w3-light-grey').addClass('w3-black'); 
                                    }
                                    document.title = "";
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

            <br /><br />

            <table class="w3-table-all w3-card-2 w3-light-grey" style="color:black;">
            <thead>
                <tr>
                    <th>Post</th>
                    <th>Date</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
            <?php
			    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
			
				$sql = "SELECT id, user, title, post, date
                        FROM posts
                        WHERE category = 'pinned' OR category = 'pinnedLocked'
                        ORDER BY date DESC LIMIT $limit OFFSET $offset;";
				$stmt = $conn->prepare($sql);
				$stmt->execute();
				$stmt->bind_result($id, $post_user, $title, $post, $date);
				
				while ($stmt->fetch()) {
					$conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

                        $sql = "SELECT username FROM users WHERE id = ?";
                        $stmt2 = $conn2->prepare($sql);
                        $stmt2->bind_param("i", $post_user);
                        $stmt2->execute();
                        $stmt2->bind_result($username);
                        $stmt2->fetch();
                        $stmt2->close();

                        $conn2->close();

                        $shortTitle = substr($title, 0, 25);
                        if (strlen($title) > 25) {
                            $shortTitle .= "...";
                        }

                        if(empty($title)) {
                            $shortTitle = 'Untitled';
                        }

                        echo "<tr><td><a href='/topic/" . $id . "'><i class='fa fa-map-pin' aria-hidden='true' title='Pinned post'></i>" . htmlspecialchars($shortTitle) . "</a></td>";
                        echo "<td>" . $date . "</td>";
                        echo "<td><a href='../@" . urlencode($username) . "'><i class='fa fa-user' aria-hidden='true'></i>" . htmlspecialchars($username) . "</a></td></tr>";
                        $username = '';
                }

			    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
			
				$sql = "SELECT id, user, title, post, date FROM posts WHERE category = 'general' OR category = 'locked' ORDER BY date DESC LIMIT $limit OFFSET $offset";
				$stmt = $conn->prepare($sql);
				$stmt->execute();
				$stmt->bind_result($id, $post_user, $title, $post, $date);
				
				while ($stmt->fetch()) {	
                    $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

                    $sql = "SELECT username FROM users WHERE id = ?";
                    $stmt2 = $conn2->prepare($sql);
                    $stmt2->bind_param("i", $post_user);
                    $stmt2->execute();
                    $stmt2->bind_result($username);
                    $stmt2->fetch();
                    $stmt2->close();

                    $conn2->close();

                    $shortTitle = substr($title, 0, 25);
                    if (strlen($title) > 25) {
                        $shortTitle .= "...";
                    }

                    if(empty($title)) {
                        $shortTitle = 'Untitled';
                    }

                    echo "<tr><td><a href='/topic/" . $id . "'><i class='fa fa-users' aria-hidden='true' title='Colaberative post'></i>" . htmlspecialchars($shortTitle) . "</a></td>";
                    echo "<td>" . $date . "</td>";
                    echo "<td><a href='../@" . urlencode($username) . "'><i class='fa fa-user' aria-hidden='true'></i>" . htmlspecialchars($username) . "</a></td></tr>";
                    $username = '';
                }

                define('DB_NAME4', 'if0_36019408_blog');
			    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME4);
			
				$sql = "SELECT id, user, title, post, date FROM posts ORDER BY date DESC LIMIT $limit OFFSET $offset";
				$stmt = $conn->prepare($sql);
				$stmt->execute();
				$stmt->bind_result($id, $post_user, $title, $post, $date);
				
				while ($stmt->fetch()) {
					$conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

                        $sql = "SELECT username FROM users WHERE id = ?";
                        $stmt2 = $conn2->prepare($sql);
                        $stmt2->bind_param("i", $post_user);
                        $stmt2->execute();
                        $stmt2->bind_result($username);
                        $stmt2->fetch();
                        $stmt2->close();
                        $conn2->close();

                        $shortTitle = substr($title, 0, 25);
                        if (strlen($title) > 25) {
                            $shortTitle .= "...";
                        }

                        if(empty($title)) {
                            $shortTitle = 'Untitled';
                        }

                        $date = date("Y-m-d H:i:s", $date);

                        echo "<tr><td><a href='http://blog.gr8brik.rf.gd/blog/" . $id . "' target='_blank'><i class='fa fa-pencil-square' aria-hidden='true' title='Blog/Offical post'></i>" . htmlspecialchars($shortTitle) . "</a></td>";
                        echo "<td>" . $date . "</td>";
                        echo "<td><a href='http://blog.gr8brik.rf.gd/author/" . $username . "' target='_blank'><i class='fa fa-user' aria-hidden='true'></i>" . htmlspecialchars($username) . "</a></td></tr>";
                        $username = '';
                }

            ?>
            </tbody></table><br />
    <br />
    <br />

    </div>
        
    <?php include('../linkbar.php'); ?>

</body>
</html>