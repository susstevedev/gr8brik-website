<!doctype html>
<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';
?>
<head>
    <title>Rules</title>
    <?php include 'header.php' ?>
</head>
<body class="w3-light-blue w3-container">
<?php 
    include('navbar.php');
    require_once 'com/bbcode.php';
    $bbcode = new BBCode;
?>
<center>

<h1>Rules</h1>

<h2>1. Disclaimer</h2>

<b style="color:red;">These rules may be changed anytime, and you may be banned from GR8BRIK temporarily or permanently for dismissing these guildlines.</b>

<h3>2. Using you're brain</h3>

<b>Using your common sense is how to avoid being banned most of the time.</b>

<h3>3. No spamming</h3>

<b>Spamming can cause web sever lag and can disrupt people. Spamming <i>includes</i> commenting the same things on multiple creations or posts, and not stopping when people ask you to. Spamming gets you banned for 1-day, aswell as the message(s) being deleted.</b>

<h3>4. Don't curse</h3>

<b>Cursing includes saying words that are usally taboo in western culture. Cursing will get you banned for 1-day, along with the message(s) with the curse being deleted.</b>

<h3>5. No NSFW</h3>

<b>Anything that people may thing isn't for Children is NSFW. This is an auto 7 day ban; and if repeated it will be raised to a month.</b>

<h3>6. No conflict</h3>

<b>This includes trolling, or politics. We don't want to start a fire in the community. You will be banned for 1 day for trolling; and 7 for politics.</b>

<hr /><h1>Site moderators</h1>

<div class="gr8-theme w3-card-2 w3-light-grey w3-padding-small w3-center">

    <h5>If you wish us to remove any personal details we hold about you, please email us at <i class="fa fa-envelope"><a href="mailto:evanrutledge226@gmail.com">evanrutledge226[at]gmail[dot]com</a></i></h5>

</div><br />

</center>

<div class="w3-container">
    <ul class="gr8-theme w3-ul w3-card-2 w3-light-grey w3-padding-small">
        <?php
            $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
            if ($conn->connect_error) {
                exit($conn->connect_error);
            }

            $sql = "SELECT * FROM users WHERE admin = '1' ORDER BY username ASC";
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

                    echo "<br /><li class='w3-padding-small'><a href='/user/" . $userid . "'>";
                    echo "<img id='pfp' style='width:100px;height:100px;border-radius:15px;border:1px solid skyblue;' src='/acc/users/pfps/" . $userid . ".jpg'>";
                    echo "&nbsp;<b class='w3-xlarge'>" . htmlspecialchars($user['username']) . "</a></b>";
                    if($user['verified'] != 0) {
                        echo '&nbsp;<i class="fa fa-check w3-blue w3-padding-tiny w3-xlarge" title="Verified/Offical Account" aria-hidden="true"></i>';
                    }
                    if($user['admin'] != 0) {
                        echo '&nbsp;<i class="fa fa-check w3-red w3-padding-tiny w3-xlarge" title="Admin/Mod" aria-hidden="true"></i>';
                    }
                    if(trim($token['user']) === trim($userid)) {
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