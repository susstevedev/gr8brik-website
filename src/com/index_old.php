<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Gr8brik.rf.gd | Community</title>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="../lib/theme.css">
    <script src="../lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <meta charset="UTF-8">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
</head>

<body class="w3-light-blue w3-container">
    <?php include('../navbar.php'); ?>

        <div class="w3-center">

            <!-- <div class="w3-card-2 w3-light-grey w3-padding-small"><img src="../img/info.jpg" style="width:20px;height=20px;"><b>Please read the <a href="../rules">rules</a>, and maybe check out our <a href="../users">users</a>. We use <a href="https://www.bbcode.org/reference.php">BBcode</a> for formating.</b></div>
		
			<br />--><p>Wanna ask a question, or post something funny?</p><a href="post.php" class="w3-btn w3-blue w3-hover-opacity">POST A TOPIC</a><br /><br />
			
			<form method="get" action="search.php">
				<input class="w3-input w3-border w3-threequarter" type="text" value="<?php if (isset($_GET['q'])) { echo $_GET['q']; } ?>" placeholder="Search for posts..." name="q">
				<input class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border w3-quarter" type="submit" value="&#128270;">
			</form><br /><br />

            <?php
                define('DB_NAME3', 'if0_36019408_forum');
			    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
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
                echo '<a class="w3-btn w3-blue w3-hover-opacity w3-border w3-mobile" href="?p=' . ($page - 1) . '">BACK</a>';
        echo '<a class="w3-btn w3-blue w3-hover-opacity w3-border w3-mobile" href="?p=' . ($page + 1) . '">NEXT</a>';
            ?>

            <div class="w3-card-2 w3-light-grey w3-padding-small"><i class="fa fa-map-pin" aria-hidden="true"></i><b>Pinned</b></div>

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
                        ORDER BY date DESC;";
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

                        $conn2->close(); // Close the second connection
                        $shortTitle = substr($title, 0, 15);
                        if (strlen($title) > 15) {
                            $shortTitle .= "...";
                        }

                        echo "<tr><td><a href='/topic/" . $id . "'>" . htmlspecialchars($shortTitle) . "</a></td>";
                        echo "<td>" . $date . "</td>";
                        echo "<td><a href='../user/" . $post_user . "'><i class='fa fa-user' aria-hidden='true'></i>" . htmlspecialchars($username) . "</a></td></tr>";
                }

            ?>
            </tbody></table><br />

            <div class="w3-card-2 w3-light-grey w3-padding-small"><i class="fa fa-info-circle" aria-hidden="true"></i><b>General</b></div>

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
			
				    $sql = "SELECT id, user, title, post, date FROM posts WHERE category = 'general' ORDER BY date DESC LIMIT $limit OFFSET $offset";
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

                        $conn2->close(); // Close the second connection
                        $shortTitle = substr($title, 0, 15);
                        if (strlen($title) > 15) {
                            $shortTitle .= "...";
                        }

                        echo "<tr><td><a href='/topic/" . $id . "'>" . htmlspecialchars($shortTitle) . "</a></td>";
                        echo "<td>" . $date . "</td>";
                        echo "<td><a href='../user/" . $post_user . "'><i class='fa fa-user' aria-hidden='true'></i>" . htmlspecialchars($username) . "</a></td></tr>";
                    }
            ?>
        </tbody>
    </table><br />

        <div class="w3-card-2 w3-light-grey w3-padding-small"><i class="fa fa-lock" aria-hidden="true"></i><b>Locked</b></div>

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
			
				$sql = "SELECT id, user, title, post, date FROM posts WHERE category = 'locked' OR category = 'pinnedLocked' ORDER BY date DESC;";
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
                        $shortTitle = substr($title, 0, 15);
                        if (strlen($title) > 15) {
                            $shortTitle .= "...";
                        }

                        echo "<tr><td><a href='/topic/" . $id . "'>" . htmlspecialchars($shortTitle) . "</a></td>";
                        echo "<td>" . $date . "</td>";
                        echo "<td><a href='../user/" . $post_user . "'><i class='fa fa-user' aria-hidden='true'></i>" . htmlspecialchars($username) . "</a></td></tr>";
                }

            ?>
            </tbody></table><br />

        <div class="w3-card-2 w3-light-grey w3-padding-small"><i class="fa fa-pencil-square" aria-hidden="true"></i><b>Blog</b></div>
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
                define('DB_NAME4', 'if0_36019408_blog');
			    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME4);
			
				$sql = "SELECT id, user, title, post, date FROM posts ORDER BY date DESC";
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
                        $shortTitle = substr($title, 0, 15);
                        if (strlen($title) > 15) {
                            $shortTitle .= "...";
                        }

                        $date = date("Y-m-d H:i:s", $date);

                        echo "<tr><td><a href='http://blog.gr8brik.rf.gd/blog/" . $id . "' target='_blank'>" . htmlspecialchars($shortTitle) . "</a></td>";
                        echo "<td>" . $date . "</td>";
                        echo "<td><a href='http://blog.gr8brik.rf.gd/author/" . $username . "' target='_blank'><i class='fa fa-user' aria-hidden='true'></i>" . htmlspecialchars($username) . "</a></td></tr>";
                }

            ?>
            </tbody></table><br />
    <br />
    <br />

    </div>
        
    <?php include('../linkbar.php'); ?>

</body>
</html>