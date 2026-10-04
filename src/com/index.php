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
		
			<a href="post">
                <button class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Create a Topic</button>
            </a><br /><br />
			
            <input value="<?php if (isset($_GET['q'])) { echo $_GET['q']; } ?>" type="text" id="search-input-2 w3-input w3-border w3-border-grey" placeholder="search for...">
            <button id="search-button-2" class="w3-btn w3-blue w3-hover-opacity w3-padding-small w3-round-small w3-border w3-border-indigo"><i class="fa fa-search" aria-hidden="true"></i></button>

            <?php
			    $conn = Database::get(DB_NAME3);
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = 8;
                $offset = ($page - 1) * $limit;

                if($page - 1 < 1) { 
                    $pDown = 1; 
                } else { 
                    $pDown = $page - 1; 
                }
                $pUp = $page + 1;

                $count_result = $conn->query("SELECT COUNT(*) as post_count FROM messages WHERE (parent IS NULL OR parent = 0) AND deleted_at IS NULL");
                $post_count = $count_result->fetch_assoc()['post_count'];
                $reply_count_result = $conn->query("SELECT COUNT(*) as post_count FROM messages WHERE (parent IS NOT NULL OR parent != 0) AND deleted_at IS NULL");

                $stats = array(
                    'post_count' => $post_count,
                    'reply_count' => $reply_count_result->fetch_assoc()['post_count'],
                    'total_pages' => ceil($post_count / $limit)
                );
            ?>

            <ul>
                <?php
                    echo "<li>" . $stats['post_count'] . " posts</li>";
                    echo "<li>" . $stats['reply_count'] . " replies</li>";
                    echo "<li>" . $stats['total_pages'] . " total pages</li>";
                    echo "<li>On page " . $page . "</li>";
                ?>
            </ul>
            
            <a href="?page=<?php echo $pDown ?>"><button class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Back</button></a>
            <a href="?page=<?php echo $pUp ?>"><button class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Foward</button></a><hr />

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
                    <th>Title</th>
                    <th>Date</th>
                    <th>User name</th>
                    <th>Last post by</th>
                </tr>
            </thead>
            <tbody>
            <?php
			    $conn = Database::get(DB_NAME3);

				$sql = "SELECT id, userid, title, content, timestamp, last_posted, last_page
                        FROM messages
                        WHERE status = 'pinned' OR status = 'pinnedLocked'
                        ORDER BY timestamp DESC LIMIT $limit OFFSET $offset;";
				$stmt = $conn->prepare($sql);
				$stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $user_row = User::getUser($row['userid']);
                    $username = $user_row->username ?? null;

                    if($row['last_posted'] != 0) {
                        $last_posted = $row['last_posted'];
                        $last_post_username = User::getUser($row['last_posted'])->username ?? null;
                    } else {
                        $last_posted = $row['userid'];
                        $last_post_username = $username;
                    }

                    if($row['last_page'] <= 0) {
                        $row['last_page'] = 1;
                    }

                    $shortTitle = substr($row['title'], 0, 25);
                    if (strlen($row['title']) > 25) {
                        $shortTitle .= "...";
                    }

                    if(empty($shortTitle)) {
                        $shortTitle = 'Untitled';
                    }

                    echo "<tr><td><a href='/topic/" . $row['id'] . "?p=" . $row['last_page'] . "'><i class='fa fa-map-pin w3-padding-small w3-text-grey' aria-hidden='true' title='Pinned Post'></i>" . htmlspecialchars($shortTitle) . "</a></td>";
                    echo "<td><i class='fa fa-calendar-o w3-padding-small w3-text-grey' aria-hidden='true'></i>" . $row['timestamp'] . "</td>";
                    echo "<td><a href='/user/" . $row['userid'] . "'><i class='fa fa-at w3-padding-small w3-text-grey' aria-hidden='true'></i>" . $username . "</a></td>";
                    echo "<td><a href='/user/" . $last_posted . "'><i class='fa fa-at w3-padding-small w3-text-grey' aria-hidden='true'></i>" . $last_post_username . "</a></td></tr>";
                    $username = null;
                    $last_post_username = null;
                }

				$sql = "SELECT id, userid, title, content, timestamp, last_posted, last_active_time, last_page
                        FROM messages
                        WHERE (status = 'general' OR status = 'locked') AND (parent IS NULL OR parent = 0)
                        ORDER BY last_active_time DESC
                        LIMIT $limit OFFSET $offset";
                
				$stmt = $conn->prepare($sql);
				$stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $user_row = User::getUser($row['userid']);
                    $username = $user_row->username ?? null;
                    
                    if($row['last_posted'] != 0) {
                        $last_posted = $row['last_posted'];
                        $last_post_username = User::getUser($last_posted)->username ?? null;
                    } else {
                      $last_posted = $row['userid'];
                      $last_post_username = $username;
                    }

                    if($row['last_page'] <= 0) {
                        $row['last_page'] = 1;
                    }

                    $shortTitle = substr($row['title'], 0, 25);
                    if (strlen($row['title']) > 25) {
                        $shortTitle .= "...";
                    }

                    if(empty($shortTitle)) {
                        $shortTitle = 'Untitled';
                    }

                    echo "<tr><td><a href='/topic/" . $row['id'] . "?p=" . $row['last_page'] . "'><i class='fa fa-users w3-padding-small w3-text-grey' aria-hidden='true' title='General Post'></i>" . htmlspecialchars($shortTitle) . "</a></td>";
                    echo "<td><i class='fa fa-calendar-o w3-padding-small w3-text-grey' aria-hidden='true'></i>" . $row['timestamp'] . "</td>";
                    echo "<td><a href='/user/" . $row['userid'] . "'><i class='fa fa-at w3-padding-small w3-text-grey' aria-hidden='true'></i>" . $username . "</a></td>";
                    echo "<td><a href='/user/" . $last_posted . "'><i class='fa fa-at w3-padding-small w3-text-grey' aria-hidden='true'></i>" . $last_post_username . "</a></tr>";
                    $username = null;
                    $last_post_username = null;
                }

			    $conn = Database::get(DB_NAME4);
				$sql = "SELECT id, user, title, post, date FROM posts ORDER BY date DESC LIMIT $limit OFFSET $offset";
				$stmt = $conn->prepare($sql);
				$stmt->execute();
				$result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
					$user_row = User::getUser($row['user']);
                    $username = $user_row->username ?? null;

                    $shortTitle = substr($row['title'], 0, 25);
                    if (strlen($row['title']) > 25) {
                        $shortTitle .= "...";
                    }

                    if(empty($shortTitle)) {
                        $shortTitle = 'Untitled';
                    }

                    $date = date("Y-m-d H:i:s", $row['date']);

                    echo "<tr><td><a href='http://blog.gr8brik.rf.gd/t/" . $row['id'] . "' target='_blank'><i class='fa fa-pencil-square w3-padding-small w3-text-grey' aria-hidden='true' title='Blog Post'></i>" . htmlspecialchars($shortTitle) . "</a></td>";
                    echo "<td><i class='fa fa-calendar-o w3-padding-small w3-text-grey' aria-hidden='true'></i>" . $date . "</td>";
                    echo "<td><i class='fa fa-at w3-padding-small w3-text-grey' aria-hidden='true'></i>" . $username . "</td>";
                    echo "<td><i class='fa fa-at w3-padding-small w3-text-grey' aria-hidden='true'></i>" . $username . "</td></tr>";
                    $username = null;
                }

            ?>
            </tbody></table><br />
    <br />
    <br />

    </div>
        
    <?php include '../linkbar.php' ?>

</body>
</html>