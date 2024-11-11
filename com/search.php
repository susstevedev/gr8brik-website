<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo urldecode($_GET['q']) ?></title>
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
	
	<style>
	
	#loading {
		position: fixed;
		width: 100%;
		height: 100%;
		top: 0;
		left: 0;
		background: rgba(255, 255, 255, 0.8);
		display: flex;
		justify-content: center;
		align-items: center;
		z-index: 9999;
		border: 1px;
		border-radius: 15px;
	}
	
	</style>
	
	<script>
	window.onload = function() {
		setTimeout(function() {
			document.getElementById('loading').style.display = 'none';
		}, 3000);
	};
	</script>


        <div class="w3-center">
		
			<br />--><p>Wanna ask a question, or post something funny?</p><a href="post.php" class="w3-btn w3-blue w3-hover-opacity">POST A TOPIC</a><br /><br />
			
			<form method="get" action="">
				<input list="searchOptions" class="w3-input w3-border w3-threequarter" type="text" value="<?php if (isset($_GET['q'])) { echo $_GET['q']; } ?>" placeholder="Search for posts..." name="q">
                <datalist id="searchOptions">
                    <?php
                        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
                
                        $sql = "SELECT id, user, title, post, date FROM posts ORDER BY date DESC";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute();
                        $stmt->bind_result($id, $post_user, $title, $post, $date);
                    
                        while ($stmt->fetch()) {
                            echo "<option value='" . $title . "'>";
                        }
                    ?>
                </datalist>
				<input class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border w3-quarter" type="submit" value="&#128270;">
			</form><br /><br />

            <?php 
                $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
                $limit = 10;
                $offset = ($page - 1) * $limit;

                $count_sql = "SELECT COUNT(*) as post_count FROM posts";
                $count_result = $conn->query($count_sql);
                if (!$count_result) {
                    die("Failed: " . $conn->error);
                }
                $count_row = $count_result->fetch_assoc();
                $post_count = $count_row['post_count'];

                $total_pages = ceil($post_count / $limit);
                echo '<h3>' . $post_count . ' posts, ' . $total_pages . ' pages. Showing page ' . $page . '.</h3>';
                echo '<a class="w3-btn w3-blue w3-hover-opacity w3-border w3-mobile" href="index.php?p=' . ($page - 1) . '">BACK</a>';
        echo '<a class="w3-btn w3-blue w3-hover-opacity w3-border w3-mobile" href="index.php?p=' . ($page + 1) . '">NEXT</a>';
            ?>

            <div class="w3-card-2 w3-light-grey w3-padding-small"><i class="fa fa-search" aria-hidden="true"></i><b>Searched</b></div>

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
                if (isset($_GET['q']) && $_GET['q']) {
				
			        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
				    $query = htmlspecialchars($_GET['q']);
                    echo "<tr><td>Search results for <b>" . $query . "</b></td></tr><br />?";
			
				    $sql = "SELECT id, user, title, post, date FROM posts WHERE title LIKE ?";
				    $stmt = $conn->prepare($sql);
				    $stmt->bind_param("s", $searchPattern);
				    // Adjust the search pattern to include wildcards
				    $searchPattern = "%" . $query . "%";
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

                        $conn2->close(); // Close the second connection

                        echo "<tr><td><a href='/topic/" . $id . "'>" . htmlspecialchars($title) . "</a></td>";
                        echo "<td>" . $date . "</td>";
                        echo "<td><a href='../user/" . $post_user . "'>" . htmlspecialchars($username) . "</a></td></tr>";
                    }
                }

            ?>
        </tbody>
    </table><br /><br /></div>
        
    <?php include('../linkbar.php'); ?>

</body>
</html>