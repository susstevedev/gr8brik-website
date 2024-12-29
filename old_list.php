<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Gr8brik.rf.gd | List</title>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="lib/theme.css">
    <script src="lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <meta charset="UTF-8">
    <meta name="description" content="Gr8brik is a block building browser game. No download required">
    <meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
</head>
<body class="w3-light-blue w3-container">

<?php 

    include('navbar.php');
    
?>

        <h1>Creations</h1>
        <div class="w3-container">
                <p>Want to build your own model? <a href="modeler.php" class="w3-btn w3-blue w3-hover-opacity">MODELER</a></p>
        </div>
		
		<form method="get" action="" class="w3-hide-small">
			<input class="w3-input w3-hover-opacity w3-border" type="text" value="<?php if (isset($_GET['q'])) { echo $_GET['q']; } ?>" placeholder="Search for posts..." name="q">
			<input class="w3-btn w3-hover-opacity w3-border w3-blue w3-third" type="submit" value="&#128270;">
		</form><br /><br />

        <table>
            <?php
			
			define('DB_NAME2', 'if0_36019408_creations');
			
			// Create connection
			$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

			// Check connection
			if ($conn->connect_error) {
				die("Connection failed: " . $conn->connect_error);
			}
			
            if (isset($_GET['q']) && $_GET['q']) {
                $query = htmlspecialchars($_GET['q']);
                echo "<p>Search results for <b>" . $query . "</b></p><br />";
			
				$sql = "SELECT id, user, model, description, name, date FROM model WHERE name LIKE ?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("s", $searchPattern);
				// Adjust the search pattern to include wildcards
				$searchPattern = "%" . $query . "%";
				$stmt->execute();
				$stmt->bind_result($id, $user, $model, $description, $name, $date);
				
				while ($stmt->fetch()) {
					echo "<a href='/build/" . $id . "'><img src='img/blured_model.jpg' width='320' height='240' loading='lazy'></a><br />";
                    echo "<b>Name:" . $name . "</b><br />";
                    echo "<b>Created:" . $date . "</b><br />";
				}
			}
			if (!isset($_GET['q']) && !($_GET['q'])) {
				// Retrieve all creations
				$sql = "SELECT id, user, model, description, name, date, screenshot FROM model ORDER BY date DESC";
				$result = $conn->query($sql);

				if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
					    echo "<div id='creation' class='w3-display-container w3-left w3-padding-small'>";
					    echo "<a href='/build/" . $row['id'] . "'><img src='/cre/" . $row['screenshot'] . "' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-border'></a>";
                        echo "<b class='w3-display-middle'>" . $row['name'] . "</b></div>";
				    }                  
				}
			} else {
				echo "<br /><b>No more results found</b>";
			}

            ?>
        </table><br /><br />

    <?php include('linkbar.php'); ?>


</body>
</html>