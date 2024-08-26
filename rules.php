<!doctype html>
<?php
    session_start();
?>
<head>
    <title>Rules / GR8BRIK</title>
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
<br /><br /><center>

<script>

    $(document).ready(function() {

        $(".message").hide();

        $(".sendMessage").hide();
        
        $(".error").hide();

        $(".messageBtn").click(function() {

            $(".message").show();

            $(".sendMessage").show();

            $(".messageBtn").hide();

             $(".sendMessage").click(function() {

                $(".error").show();

            });

        });

    });

</script>

<h1>Rules</h1>

<h2>1. Disclaimer</h2>

<b style="color:red;">These rules may be changed anytime, and you may be banned from GR8BRIK temperarily or permently for dismissing these.</b>

<h3>2. Using you're brain</h3>

<b>Using your common sense is how (most of the time) avoid being banned.</b>

<h3>3. Don't curse</h3>

<b>Cursing will get you banned for 1-day, along with the message(s) with the curse being deleted.</b>

<h3>4. No NSFW</h3>

<b>Children use this site. This is an auto 7 day ban; and twice a month ban.</b>

<h3>5. No conflict</h3>

<b>This includes trolling, or politics. You will be banned for 1 day; and 7 for politics.</b>

<hr /><h1>Site moderators</h1>

<button class="messageBtn w3-btn w3-large w3-white w3-hover-blue">MESSAGE MODERATORS</button>

<div class="message w3-bordered">

    <textarea placeholder="Message" class="w3-input w3-border w3-mobile" rows="4" cols="50"></textarea>

    <button class="messageSend w3-btn w3-large w3-white w3-hover-blue">SEND</button>

    <div class="error w3-red">An error has happened</div>

</div>

<?php
    // Create connection
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

	$sql = "SELECT id, username, email, handle, age FROM users WHERE admin = 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Fetch all results into an array
        $moderators = $result->fetch_all(MYSQLI_ASSOC);
    
        // Loop through the array and display usernames
        foreach ($moderators as $moderator) {
            echo "<hr /><img id='pfp' src='acc/users/pfps/" . htmlspecialchars($moderator['id']) . "..jpg'><br />";
            echo "<b><br /><a href='" . strtolower($moderator['handle']) . "'>" . htmlspecialchars($moderator['username']) . '</a></b>';
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