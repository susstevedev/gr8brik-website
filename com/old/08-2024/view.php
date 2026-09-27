<?php

session_start();

$p_xml = simplexml_load_file('posts/' . basename($_SERVER['QUERY_STRING']));

$p_xml_2 = new SimpleXMLElement('posts/' . basename($_SERVER['QUERY_STRING']), 0, true);

if(!file_exists('posts/' . basename($_SERVER['QUERY_STRING']))){
    echo '<b>404 post not in database</b><br />';
    die;
}

if ($p_xml === false) {
	die("<b>Can't load post via XML</b>");
}
				
	if (trim($p_xml->post) === "") {
		die("<b>Post has been removed from database</b><br />");
	}
	
if(isset($_POST['comment'])){
	require_once '../acc/classes/constants.php';

	// Create connection
	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

	// Check connection
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}
	$username = $_SESSION['username'];
	$sql = "SELECT id, username, email FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
	$stmt->bind_param("i", $username);
	$stmt->execute();
	$stmt->bind_result($id, $user, $mail);
	$stmt->fetch();
    if (isset($_SESSION['username'])) {
        $username = $_SESSION['username'];
    } else {
		$username = 'guest';
	}
    $commentbox = $_POST['commentbox'];
    $date = date("Y-m-d H:i:s");
	$p_xml_2->addChild('boxU', $id);
    $p_xml_2->addChild('boxT', $commentbox);
    $p_xml_2->addChild('boxD', $date);
    $p_xml_2->asXml('posts/' . basename($_SERVER['QUERY_STRING']));
    header('Location: view.php?posts/' . basename($_SERVER['QUERY_STRING']));     
}

if(isset($_POST['edit'])) {
	$p_xml_2->post = $_POST['post'];
    $p_xml_2->asXML('posts/' . basename($_SERVER['QUERY_STRING']));
    header('Location: view.php?' . basename($_SERVER['QUERY_STRING']));
    die;
}

include('bbcode.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>GR8BRIK community forums</title>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="../lib/theme.css">
    <script src="../lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script async defer src="https://cdn.jsdelivr.net/npm/altcha/dist/altcha.min.js" type="module"></script>
    <meta charset="UTF-8">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body class="w3-light-blue w3-container">

<?php include('../navbar.php') ?>

        <?php echo "<h1>" . urldecode($p_xml_2->title) . "</h1>" ?>
		
		<script>
		function editPost() {
			document.getElementById('post').style.display = 'block';
			document.getElementById('editBtn').style.display = 'none';
			document.getElementById('cancel').style.display = 'block';
			document.getElementById('postBtn').style.display = 'block';
		}
		
		function cancel() {
			document.getElementById('post').style.display = 'none';
			document.getElementById('editBtn').style.display = 'block';
			document.getElementById('cancel').style.display = 'none';
			document.getElementById('postBtn').style.display = 'none';
		}

        function editComment() {
			document.getElementById('comment').style.display = 'block';
			document.getElementById('editCommentBtn').style.display = 'none';
			document.getElementById('cancelComment').style.display = 'block';
			document.getElementById('commentBtn').style.display = 'block';
		}
		
		function cancelComment() {
			document.getElementById('comment').style.display = 'none';
			document.getElementById('editCommentBtn').style.display = 'block';
			document.getElementById('cancelComment').style.display = 'none';
			document.getElementById('commentBtn').style.display = 'none';
		}
		
		</script>


        <?php

        echo "<br /><form method='post' action=''><textarea name='commentbox' placeholder='Post body' rows='4' cols='50'></textarea><br /><input type='submit' value='POST' name='comment' class='w3-btn w3-blue w3-hover-opacity' /><altcha-widget challengeurl='https://eu.altcha.org/api/v1/challenge?apiKey=ckey_01d9f4ad018c16287ca6f3938a0f' style='background-color:white!important;border-radius:0px!important;'></altcha-widget></form><br />";

        // Create connection
        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Query to get username by user ID
        $uname = $p_xml_2->uname;

        // Prepare the SQL statement
        $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->bind_param("i", $uname); // Assuming $uname is an integer

        // Execute the statement
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $uname2 = $row['username'];

        echo '<img id="pfp" src="../acc/users/pfps/' . $uname . '..jpg">';
		echo '<article class="w3-card-24 w3-white w3-padding"><header><b>BY:<a href="../user/' . strtolower($uname2) . '">' . htmlspecialchars($uname2) . '</a>';
        echo '<br /><time id="postTime" datetime="' . $p_xml->date . '">POSTED:' . $p_xml->date . '</time></b></header>';
		echo '<h4>' . $p_xml->post . '</h4></article><br />';
			
		if (trim($_SESSION['username']) === trim($uname2)) {
				
			echo "<form method='post' action=''><textarea id='comment' name='comment' rows='4' cols='50' style='display:none'>" . $p_xml->post . "</textarea>";

			echo '<input type="submit" class="w3-btn w3-blue w3-hover-opacity" id="commentBtn" name="commentBtn" value="UPDATE" style="display:none;"></form>';

			echo '<button class="w3-btn w3-blue w3-hover-opacity" id="editCommentBtn" onclick="editPost();">EDIT</button><br />';

			echo '<button class="w3-btn w3-blue w3-hover-opacity" id="cancelComment" onclick="cancel();" style="display:none;">CANCEL</button><br />';
				
		} 
			
			// Create connection
			$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
			
			if ($conn->connect_error) {

				die("Connection failed: " . $conn->connect_error);

			}
			
			for ($i = 0; $i < count($p_xml_2->boxU); $i++) {
				// Query to get username by user ID
				$userId = (int)$p_xml_2->boxU[$i];
                $time = (string)$p_xml_2->boxD[$i];
				$commentText = (string)$p_xml_2->boxT[$i];
				$sql = "SELECT username FROM users WHERE id = $userId";
				$result = $conn->query($sql);
				$row = $result->fetch_assoc();
				$username = $row['username'];

				echo '<img id="pfp" src="../acc/users/pfps/' . $userId . '..jpg">';
				echo '<article class="w3-card-24 w3-white w3-padding"><header>';
                echo '<b>BY:<a href="../user/' . strtolower($username) . '">' . htmlspecialchars($username) . '</a>';
                echo '<br /><time id="postTime" datetime="' . $time . '">POSTED:' . $time . '</time></b></header>';
				echo '<h4>' . htmlspecialchars($commentText) . '</h4></article><br />';

                if (trim($_SESSION['username']) === trim($username)) {

				    echo "<form method='post' action=''><textarea id='comment' name='comment' rows='4' cols='50' style='display:none'>" . $commentText . "</textarea>";

				    echo '<input type="submit" class="w3-btn w3-blue w3-hover-opacity" id="commentBtn" name="commentBtn" value="UPDATE" style="display:none;"></form>';

				    echo '<button class="w3-btn w3-blue w3-hover-opacity" id="editCommentBtn" onclick="editComment();">EDIT</button><br />';

				    echo '<button class="w3-btn w3-blue w3-hover-opacity" id="cancelComment" onclick="cancelComment();" style="display:none;">CANCEL</button><br />';
				} 

			}

			$conn->close();

            echo '</div><br /><br />';

            include('../linkbar.php');

        ?>

</body>
</html>