<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Community</title>
    <?php include '../header.php' ?>
</head>

<body class="w3-light-blue w3-container">
    <?php include '../navbar.php' ?>

        <div class="w3-center">
		
			<p>Wanna ask a question, or just post something funny?</p>&nbsp;<a href="post" class="w3-btn w3-blue w3-hover-white w3-border w3-border-indigo">Create a Topic</a><br /><br />
			
            <input class="w3-input w3-border w3-threequarter" value="<?php if (isset($_GET['q'])) { echo $_GET['q']; } ?>" type="text" id="search-input-2" placeholder="search for...">
            <button class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border w3-quarter" id="search-button-2"><i class="fa fa-search" aria-hidden="true"></i></button><br /><br />

            <?php
			    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = 8;
                $offset = ($page - 1) * $limit;
                $pDown = $page - 1;
                $pUp = $page + 1;

                $count_result = $conn->query("SELECT COUNT(*) as post_count FROM messages WHERE parent IS NULL OR parent = 0");
                $count_row = $count_result->fetch_assoc();
                $post_count = $count_row['post_count'];
                $total_pages = ceil($post_count / $limit);

                echo '<h3>' . $post_count . ' posts, ' . $total_pages . ' pages, on page ' . $page . '</h3>';
                echo '<a class="w3-btn w3-blue w3-hover-white w3-border w3-border-indigo" href="?page=' . $pDown . '">Back</a>&nbsp;';
                echo '<a class="w3-btn w3-blue w3-hover-white w3-border w3-border-indigo" href="?page=' . $pUp . '">Next</a>';
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
                                window.location.href = "/com/search?q=" + encodeURIComponent(searchTerm);
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

            <br /><table class="gr8-theme w3-table w3-card-2 w3-light-grey" style="color:black;">
            <thead>
                <tr>
                    <th>Post</th>
                    <th>Date</th>
                    <th>User</th>
                    <th>Last post by</th>
                </tr>
            </thead>
            <tbody>
            <?php
			    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
			
				$sql = "SELECT id, userid, title, content, timestamp
                        FROM messages
                        WHERE status = 'pinned' OR status = 'pinnedLocked'
                        ORDER BY timestamp DESC LIMIT $limit OFFSET $offset;";
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

                        echo "<tr><td><a href='view.php?id=" . $id . "'><i class='fa fa-map-pin w3-padding-small w3-text-grey' aria-hidden='true' title='Pinned Post'></i>" . htmlspecialchars($shortTitle) . "</a></td>";
                        echo "<td>" . $date . "</td>";
                        echo "<td><a href='../user/" . $post_user . "'><i class='fa fa-user' aria-hidden='true'></i>" . htmlspecialchars($username) . "</a></td></tr>";
                        $username = '';
                }

			    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
			
				$sql = "SELECT id, userid, title, content, timestamp, last_posted
                        FROM messages
                        WHERE (status = 'general' OR status = 'locked') AND parent IS NULL OR parent = 0
                        ORDER BY timestamp DESC
                        LIMIT $limit OFFSET $offset";
				$stmt = $conn->prepare($sql);
				$stmt->execute();
				$stmt->bind_result($id, $post_user, $title, $post, $date, $last_posted);
				
				while ($stmt->fetch()) {	
                    $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

                    $sql = "SELECT username FROM users WHERE id = ?";
                    $stmt2 = $conn2->prepare($sql);
                    $stmt2->bind_param("i", $post_user);
                    $stmt2->execute();
                    $stmt2->bind_result($username);
                    $stmt2->fetch();
                    $stmt2->close();

                    if($last_posted != null || $last_posted != 0) {
                        $sql = "SELECT username FROM users WHERE id = ?";
                        $stmt2 = $conn2->prepare($sql);
                        $stmt2->bind_param("i", $last_posted);
                        $stmt2->execute();
                        $stmt2->bind_result($last_post_username);
                        $stmt2->fetch();
                        $stmt2->close();
                    } else {
                        $last_post_username = null;
                    }

                    $conn2->close();

                    $shortTitle = substr($title, 0, 25);
                    if (strlen($title) > 25) {
                        $shortTitle .= "...";
                    }

                    if(empty($title)) {
                        $shortTitle = 'Untitled';
                    }

                    echo "<tr><td><a href='view.php?id=" . $id . "'><i class='fa fa-users w3-padding-small w3-text-grey' aria-hidden='true' title='General Post'></i>" . htmlspecialchars($shortTitle) . "</a></td>";
                    echo "<td>" . $date . "</td>";
                    echo "<td><a href='../profile?id=" . $post_user . "'><i class='fa fa-user' aria-hidden='true'></i>" . htmlspecialchars($username) . "</a></td>";
                    echo "<td><a href='../profile?id=" . $post_user . "'><i class='fa fa-user' aria-hidden='true'></i>" . htmlspecialchars($last_post_username) . "</a></tr>";
                    $username = null;
                    $last_post_username = null;
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

                        echo "<tr><td><a href='http://blog.gr8brik.rf.gd/t/" . $id . "' target='_blank'><i class='fa fa-pencil-square w3-padding-small w3-text-grey' aria-hidden='true' title='Blog Post'></i>" . htmlspecialchars($shortTitle) . "</a></td>";
                        echo "<td>" . $date . "</td>";
                        echo "<td><a href='http://blog.gr8brik.rf.gd/u/" . base64_encode($username) . "' target='_blank'><i class='fa fa-user' aria-hidden='true'></i>" . htmlspecialchars($username) . "</a></td></tr>";
                        $username = '';
                }

            ?>
            </tbody></table><br />
    <br />
    <br />

    </div>
        
    <?php include '../linkbar.php' ?>

</body>
</html>