<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo urldecode($_GET['q']) ?></title>
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
					
			<form method="get" action="">
				<input list="searchOptions" class="w3-input w3-border w3-threequarter" type="text" value="<?php if (isset($_GET['q'])) { echo $_GET['q']; } ?>" name="q">
				<input class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border w3-quarter" type="submit" value="&#128270;">
			</form><br /><br />

            <table class="gr8-theme w3-table w3-card-2 w3-light-grey w3-text-black">
            <thead>
                <tr>
                    <th>Post</th>
                    <th>Date</th>
                    <th>User</th>
                </tr>
            </thead>
        <tbody>
            <?php
                // Last refactor @4-11-25 9:10pm
                // Hopefully this doesn't need any more
                if (isset($_GET['q']) && $_GET['q']) {
                    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
                    $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

                    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                    $query = $conn->real_escape_string($_GET['q']);

                    $limit = 8;
                    $offset = ($page - 1) * $limit;
                    $pDown = $page - 1;
                    $pUp = $page + 1;

                    $count_result = $conn->query("SELECT COUNT(*) as post_count FROM posts WHERE title LIKE '%" . $query . "%' AND category != 'deleted'");
                    $count_row = $count_result->fetch_assoc();
                    $post_count = $count_row['post_count'];
                    $total_pages = ceil($post_count / $limit);

                    echo '<h3>' . $post_count . ' posts, ' . $total_pages . ' pages, on page ' . $page . '</h3>';
                    echo '<a class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" href="?page=' . $pDown . '">Back</a>&nbsp;';
                    echo '<a class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" href="?page=' . $pUp . '">Next</a>&nbsp;';

                    echo "<br />Search results for <b>" . htmlspecialchars($query) . "</b><br />";
			
				    $result = $conn->query("SELECT id, user, title, post, date FROM posts WHERE title LIKE '%$query%' AND category != 'deleted' ORDER BY date DESC LIMIT $limit OFFSET $offset");
				
				    while ($row = $result->fetch_assoc()) {
                        $topic_user_id = $row['user'];

                        $result2 = $conn2->query("SELECT id, username FROM users WHERE id = $topic_user_id");
                        while ($row2 = $result2->fetch_assoc()) {
                            $username = $conn->real_escape_string($row2['username']);
                        }

                        echo "<tr><td><a href='/topic/" . $row['id'] . "'>" . htmlspecialchars($row['title']) . "</a></td>";
                        echo "<td>" . time_ago($row['date']) . "</td>";
                        echo "<td><a href='../user/" . $row['user'] . "'>" . htmlspecialchars($username) . "</a></td></tr>";
                    }
                }

            ?>
        </tbody>
    </table><br /><br /></div>
        
    <?php include('../linkbar.php'); ?>

</body>
</html>