<!doctype html>
<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';

?>
<head>
    <title>Users</title>
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
    require_once 'com/bbcode.php';
    $bbcode = new BBCode;
?>
<center>

<h1>All Users</h1>

<form method="post">
    <label for="sortOrder"><b>Sort options:</b></label>
    <select name="sortOrder" id="sortOrder">
        <option value="NONE" disabled selected>Default results</option>
        <option value="ASC">Ascending results</option>
        <option value="DESC">Descending results</option>
    </select>
    <input class="w3-btn w3-blue w3-hover-white" type="submit" value="APPLY">
</form><br />

<div class="w3-card-2 w3-light-grey w3-padding-small w3-center">

    <h5>If you wish us to remove any personal details we hold about you, please email us at <i class="fa fa-envelope"><a href="mailto:evanrutledge226@gmail.com">evanrutledge226[at]gmail[dot]com</a></i></h5>

</div><br />

</center>

<div class="w3-container">
    <ul class="w3-ul w3-card-2 w3-light-grey w3-padding-small">
    <h4>If a user has been banned or has deleted their account, it will not show up here for privacy reasons.</h4>
        <?php
            $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
            if ($conn->connect_error) {
                exit($conn->connect_error);
            }

            $sortOrder = $_POST['sortOrder'];
            $sql = "SELECT * FROM users ORDER BY username $sortOrder";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $users = $result->fetch_all(MYSQLI_ASSOC);
            
                foreach ($users as $user) {
                    $userid = $user['id'];
                    $sql = "SELECT * FROM bans WHERE user = $userid";
                    $result2 = $conn->query($sql);
                    $row2 = $result2->fetch_assoc();

                    if(empty($user['username']) || $result2->num_rows != 0 && $row2['end_date'] >= time()) {
                        continue;
                    }

                    echo "<br /><li class='w3-padding-small'><a href='/@" . urlencode($user['username']) . "'>";
                    echo "<img id='pfp' style='width:100px;height:100px;border-radius:15px;border:1px solid skyblue;' src='/acc/users/pfps/" . $userid . ".jpg'>";
                    echo "&nbsp;<b class='w3-xlarge'>" . htmlspecialchars($user['username']) . "</a></b>";
                    if($user['verified'] != 0) {
                        echo '&nbsp;<i class="fa fa-check w3-blue w3-padding-tiny w3-xlarge" title="Verified/Offical Account" aria-hidden="true"></i>';
                    }
                    if($user['admin'] != 0) {
                        echo '&nbsp;<i class="fa fa-check w3-red w3-padding-tiny w3-xlarge" title="Admin/Mod" aria-hidden="true"></i>';
                    }
                    if(trim($_SESSION['username']) === trim($userid)) {
                        echo '&nbsp;<a href="/acc/index"><i class="fa fa-pencil w3-green w3-padding-tiny w3-xlarge" aria-hidden="true"></i></a>';
                    }
                    echo "<br /><b class='w3-text-grey'>" . $bbcode->toHTML(htmlspecialchars($user['description'])) . "</b></li>";
                }
            } else {
                echo "Please check your internet connection";
            }

        ?>
    </ul>
<?php include('linkbar.php') ?>
</body>
</html>