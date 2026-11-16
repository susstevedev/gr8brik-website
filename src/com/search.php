<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/const/utils/time.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>"<?php echo urldecode($_GET['q']) ?>" in Community Forums</title>
    <?php include '../header.php' ?>
</head>

<body class="w3-light-blue w3-container">
    <?php include '../navbar.php' ?>
    <div class="w3-center">
    <script>
        $(document).ready(function() {
            function loadSearch() {
                var searchTerm = $('#search-input-2').val();
                window.location.href = "?q=" + encodeURIComponent(searchTerm);
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
                function truncateStr(string $mystr): string {
                    $truncat = substr($mystr, 0, 30);
                    if (strlen($mystr) >= 30) {
                        $truncat .= '...';
                    }
                    return $truncat;
                }

                // Last refactor @4-11-25 9:10pm
                // Hopefully this doesn't need any more work
            
                // Update @10-11-25 4:24pm
                // What the fck is this sht
                // Refactor later please

                // Update @07-22-26 6:45pm
                // Okay yeah I'm refactoring
                if (isset($_GET['q']) && $_GET['q']) {
                    $conn = Database::get(DB_NAME3);
                    $conn2 = Database::get(DB_NAME);

                    $page = (!isset($_GET['page']) || (int)$_GET['page'] <= 0) ? 1 : (int)$_GET['page'];
                    $query = $conn->real_escape_string($_GET['q']);

                    $limit = 20;
                    $offset = ($page - 1) * $limit;

                    $count_result = $conn->query
                    ("SELECT COUNT(*) as post_count FROM messages
                    WHERE (title LIKE '%$query%' OR content LIKE '%$query%')
                    AND status != 'deleted'");

                    $post_count = $count_result->fetch_assoc()['post_count'];
                    $total_pages = ceil($post_count / $limit);
                ?>

                <h4><?php echo $post_count ?> threads & replies, <?php echo $total_pages ?> pages, on page <?php echo $page ?></h4>
                <a class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo" href="?q=<?php echo $query ?>&page=<?php echo $page - 1 ?>">Back</a>
                &NonBreakingSpace;
                <a class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo" href="?q=<?php echo $query ?>&page=<?php echo $page + 1 ?>">Next</a>

                <br />Search results for <strong><em><?php echo htmlspecialchars($query) ?></em></strong><hr />

            <?php
                    try {
                        $result = $conn->query
                        ("SELECT * FROM messages
                        WHERE (title LIKE '%$query%' OR content LIKE '%$query%')
                        AND status != 'deleted'
                        ORDER BY id DESC LIMIT $limit OFFSET $offset;");
                    } catch (\Throwable $th) {
                        throw $th;
                    }

                    while ($row = $result->fetch_assoc()) {
                        (int)$topic_user_id = $conn->real_escape_string($row['userid']);
                        $usero = User::getUser($topic_user_id);
                        (string)$username = htmlspecialchars($usero->username ?? '[deleted]');

                        if($row['parent'] === 0) {
                            $display = truncateStr(htmlspecialchars($row['content']));
                            $link = "/topic/" . $row['id'] . "?p=" . $row['last_page'];
                        } else {
                            $display = truncateStr(htmlspecialchars($row['content']));
                            $link = "/topic/" . $row['parent'] . "?p=" . $row['last_page'];
                        }

                        echo "<tr><td><a href='" . $link . "'>" . $display . "</a></td>";
                        echo "<td>" . time_ago($row['timestamp']) . "</td>";
                        echo "<td><a href='/@" . urlencode($username) . "'>" . truncateStr($username) . "</a></td></tr>";

                        unset($topic_user_id, $username, $usero, $display, $link);
                    }
                }
            ?>
        </tbody>
    </table><br /><br /></div>
    <?php include '../linkbar.php' ?>
</body>
</html>