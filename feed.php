<?php
session_start();

if(!isset($_SESSION['username'])){

    header('Location: acc/login.php');

    die;

}

require_once $_SERVER['DOCUMENT_ROOT'] . '/newsite/acc/classes/constants.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/newsite/acc/classes/user.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Home / GR8BRIK</title>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="lib/theme.css">
    <script src="lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <meta charset="UTF-8">
    <meta name="description" content="Gr8brik is a block building browser game. No download required">
    <meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="w3-light-blue w3-container">

<?php 

    include('navbar.php');
    
?>
        <div class="w3-container">
                <p>Want to build your own model? <a href="modeler.php" class="w3-btn w3-blue w3-hover-opacity">MODELER</a></p>
        </div>

        <div class="w3-light-grey w3-card-24 w3-center">
            <?php 
				if($alert != 0){
					echo '<h1 style="color:red;">You have new notifications!</h1>';
				}
                echo "<h3>Welcome,</h3><img id='pfp' src='acc/users/pfps/" . $id . "..jpg' class='w3-hover-opacity w3-border w3-card-24' onclick=document.getElementById('im').style.display='block' />";
                echo '<h1>' . $user . '</h1><h3>@' . $handle . '</h3>';
            ?>
            <a href="<?php echo $handle ?>" class="w3-btn w3-large w3-white w3-hover-blue"><li class="fa fa-user" aria-hidden="true"></li><b>PROFILE</b></a>
			<a href="acc/new.php" class="w3-btn w3-large w3-white w3-hover-blue"><i class="fa fa-bell" aria-hidden="true"></i><b>NOTIFICATIONS</b></a>
            <a href="acc/creations.php" class="w3-btn w3-large w3-white w3-hover-blue"><i class="fa fa-building" aria-hidden="true"></i><b>MY CREATIONS</b></a>
        </div>
		
		<div id="im" class="w3-modal w3-center" onclick="this.style.display='none'">

            <span class="w3-closebtn w3-red w3-hover-white w3-padding w3-display-topright">&#10006;</span>

            <img class="w3-modal-content" src="<?php echo 'acc/users/pfps/' . $id . '..jpg' ?>" style="width:75%;" loading="lazy">

        </div>

        <div class="w3-center"><h3>Here are some things you might be interested in</h3>
		
		<a href='list.php' class='w3-btn w3-blue w3-hover-opacity'>ALL</a><br /></div>

        <table class="w3-table-all" style="color:black;">
        
            <?php
				
			define('DB_NAME2', 'if0_36019408_creations');
			
			// Create connection
			$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

			// Check connection
			if ($conn->connect_error) {
				die("Connection failed: " . $conn->connect_error);
			}
			
			// Retrieve all creations
			$sql = "SELECT id, user, model, description, name FROM model";
			$result = $conn->query($sql);

			if ($result->num_rows > 0) {
				$i = 0;
				while ($row = $result->fetch_assoc()) {
					echo "<div class='w3-display-container w3-left'>";
					echo "<a href='creation.php?id=" . $row['id'] . "'><img src='img/blured_model.jpg' width='320' height='240' loading='lazy' class='w3-hover-opacity w3-round w3-border'></a>";
                    echo "<b class='w3-display-middle'>" . $row['name'] . "</b></div>";
					if (++$i == 4) break;
				}
			}

            ?>
			
        </table><br /><br />


    <?php include('linkbar.php'); ?>


</body>
</html>