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
    <?php include('../navbar.php'); ?>

        <div class="w3-center">
					
			<form method="get" action="">
				<input list="searchOptions" class="w3-input w3-border w3-threequarter" type="text" value="<?php if (isset($_GET['q'])) { echo $_GET['q']; } ?>" name="q">
				<input class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border w3-quarter" type="submit" value="&#128270;">
			</form><br /><br />

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