<!doctype html>
<?php
    session_start();
?>
<head>
    <title>Users / GR8BRIK</title>
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
<?php include('navbar.php') ?>
<center>

<h1>ALL USERS</h1>

<form method="post">
    <label for="sortOrder"><b>SORT BY:</b></label>
    <select name="sortOrder" id="sortOrder">
        <option value="NONE" disabled selected>Default results</option>
        <option value="ASC">Ascending results</option>
        <option value="DESC">Descending results</option>
    </select>
    <input class="w3-btn w3-large w3-white w3-hover-blue" type="submit" value="APPLY">
</form>

<?php
    // Create connection
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sortOrder = $_POST['sortOrder'];
	$sql = "SELECT id, username, email, handle, age FROM users ORDER BY username $sortOrder";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Fetch all results into an array
        $users = $result->fetch_all(MYSQLI_ASSOC);
    
        // Loop through the array and display usernames
        foreach ($users as $user) {
            echo "<hr /><img id='pfp' src='acc/users/pfps/" . htmlspecialchars($user['id']) . "..jpg'><br />";
            echo "<b><br /><a href='" . strtolower($user['handle']) . "'>" . htmlspecialchars($user['username']) . '</a></b>';
        }
    } else {
        echo "Please check your internet connection";
    }

    $conn->close();

?>

</center><br /><br />
<?php include('linkbar.php') ?>
</body>
</html>