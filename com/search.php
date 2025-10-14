<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo urldecode($_GET['q']) ?> | Gr8Brik community forums</title>
    <?php include '../header.php' ?>
</head>

<body class="w3-light-blue w3-container">
    <?php
        include '../navbar.php' ;
        require_once "bbcode.php";
        require_once "../ajax/time.php";
        $bbcode = new BBCode;
    ?>

        <div class="w3-center">
          
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
          
         <input class="w3-input w3-border w3-threequarter" value="<?php if (isset($_GET['q'])) { echo $_GET['q']; } ?>" type="text" id="search-input-2" placeholder="search for...">
            <button class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border w3-quarter" id="search-button-2"><i class="fa fa-search" aria-hidden="true"></i></button><br /><br />
					
			<!-- <form method="get" action="">
				<input list="searchOptions" class="w3-input w3-border w3-threequarter" type="text" value="<?php if (isset($_GET['q'])) { echo $_GET['q']; } ?>" name="q">
				<input class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border w3-quarter" type="submit" value="&#128270;">
			</form><br /><br /> -->

            <table class="gr8-theme w3-table w3-card-2 w3-light-grey w3-text-black">
            <thead>
                <tr>
                    <th>Post or Reply</th>
                    <th>Date</th>
                    <th>User</th>
                </tr>
            </thead>
        <tbody>
            <?php
                 
                  function truncateStr($mystr) {
                    $truncatedName = substr($mystr, 0, 30);
                    if (strlen($mystr) >= 30) {
                      $truncatedName .= '...';
                    }
                    return $truncatedName;
                  }
          
                // Last refactor @4-11-25 9:10pm
                // Hopefully this doesn't need any more work
          
                // Update @10-11-25 4:24pm
                // What the fuck is this shit
                // Refactor later please
                if (isset($_GET['q']) && $_GET['q']) {
                    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
                    $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

                    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                    $query = $conn->real_escape_string($_GET['q']);

                    $limit = 8;
                    $offset = ($page - 1) * $limit;
                    $pDown = $page - 1;
                    $pUp = $page + 1;

                    $count_result = $conn->query("SELECT COUNT(*) as post_count FROM messages WHERE (title LIKE '%" . $query . "%' OR content LIKE '%" . $query . "%') AND status != 'deleted'");
                    $count_row = $count_result->fetch_assoc();
                    $post_count = $count_row['post_count'];
                    $total_pages = ceil($post_count / $limit);

                    echo '<h3>' . $post_count . ' posts, ' . $total_pages . ' pages, on page ' . $page . '</h3>';
                    echo '<a class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" href="?page=' . $pDown . '">Back</a>&nbsp;';
                    echo '<a class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" href="?page=' . $pUp . '">Next</a>&nbsp;';

                    echo "<br />Search results for <b>" . htmlspecialchars($query) . "</b><br />";
			
                    $result = $conn->query("SELECT * FROM messages WHERE (title LIKE '%" . $query . "%' OR content LIKE '%" . $query . "%') AND status != 'deleted'");
				
				    while ($row = $result->fetch_assoc()) {
                        $topic_user_id = $row['userid'];

                        $result2 = $conn2->query("SELECT id, username FROM users WHERE id = $topic_user_id");
                        while ($row2 = $result2->fetch_assoc()) {
                            $username = htmlspecialchars($conn->real_escape_string($row2['username']));
                        }
                      
                        if($row['parent'] == 0) {
                          $display = truncateStr(htmlspecialchars($row['title']));
                          $link = "/topic/" . $row['id'];
                        } else {
                          $display = truncateStr(htmlspecialchars($row['content']));
                          $link = "/topic/" . $row['parent'];
                        }

                        echo "<tr><td><a href='" . $link . "'>" . $display . "</a></td>";
                        echo "<td>" . time_ago($row['timestamp']) . "</td>";
                        echo "<td><a href='/user/" . $row['userid'] . "'>" . $username . "</a></td></tr>";
                      $username = null;
                      $display = null;
                    }
                }

            ?>
        </tbody>
    </table><br /><br /></div>
        
    <?php include('../linkbar.php'); ?>

</body>
</html>