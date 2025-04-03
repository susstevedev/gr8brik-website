<!doctype html>
<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';

?>
<head>
    <title>Users</title>
    <?php include 'header.php' ?>
</head>
<body class="w3-light-blue w3-container">
<?php 
    include('navbar.php');
    require_once 'com/bbcode.php';
    $bbcode = new BBCode;
?>
<center>

<h1>All Users</h1>

<div class="w3-card-2 w3-light-grey w3-padding-small w3-center">

    <h5>If you wish us to remove any personal details we hold about you, please email us at <i class="fa fa-envelope"><a href="mailto:evanrutledge226@gmail.com">evanrutledge226[at]gmail[dot]com</a></i></h5>

</div><br />

</center>

<div class="w3-container">
    <ul class="w3-ul w3-card-2 w3-light-grey w3-padding-small">
    <form method="get">
        <label for="sort"><b>Sort options:</b></label>
        <select name="sort" id="sort">
            <option value="desc" selected>Newest registered</option>
            <option value="username_a">A-Z</option>
            <option value="username_d">Z-A</option>
            <option value="age">Creation date</option>
            <option value="verified">Important Accounts</option>
            <option value="admin">Moderator Accounts</option>
        </select>
        <input class="w3-btn w3-blue w3-hover-white w3-border w3-border-indigo w3-round" type="submit" value="Apply">
    </form>
    <p>If a user de-activated, deleted, or was banned, their account will no longer show up down below for <a href="/privacy" class="reload">privacy</a> reasons.</p>
        <?php
            $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
            if ($conn->connect_error) {
                exit($conn->connect_error);
            }

            if (isset($_GET['sort']) && $_GET['sort']) {
                if($_GET['sort'] === 'desc') {
                    $sql = "SELECT * FROM users ORDER BY id DESC";
                }
                if($_GET['sort'] === 'username_a') {
                    $sql = "SELECT * FROM users ORDER BY username ASC";
                }
                if($_GET['sort'] === 'username_d') {
                    $sql = "SELECT * FROM users ORDER BY username DESC";
                }
                if($_GET['sort'] === 'age') {
                    $sql = "SELECT * FROM users ORDER BY age DESC";
                }
                if($_GET['sort'] === 'verified') {
                    $sql = "SELECT * FROM users WHERE verified = '1' ORDER BY age DESC";
                }
                if($_GET['sort'] === 'admin') {
                    $sql = "SELECT * FROM users WHERE admin = '1' ORDER BY age DESC";
                }
            } else {
                $sql = "SELECT * FROM users ORDER BY id DESC";
            }
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $users = $result->fetch_all(MYSQLI_ASSOC);
                $count_result = $conn->query("SELECT COUNT(*) as total_users FROM users");
                $count_row = $count_result->fetch_assoc();

                echo '<h3>' . $count_row['total_users'] . ' total registered users. <a href="/chart" class="reload">View chart</a>.</h3>';
            
                foreach ($users as $user) {
                    $userid = $user['id'];
                    $sql = "SELECT * FROM bans WHERE user = '$userid'";
                    $result2 = $conn->query($sql);
                    $row2 = $result2->fetch_assoc();

                    if(empty($user['username']) || $result2->num_rows != 0 && $row2['end_date'] >= time()) {
                        continue;
                    }

                    echo "<br /><li class='w3-padding-small w3-border'><a href='/user/" . $userid . "'>";
                    echo "<img id='pfp' style='width:100px;height:100px;border-radius:15px;border:1px solid skyblue;' src='/acc/users/pfps/" . $userid . ".jpg'>";
                    echo "&nbsp;<b class='w3-xlarge'>" . htmlspecialchars($user['username']) . "</a></b>";
                    if($user['verified'] != 0) {
                        echo '&nbsp;<i class="fa fa-check w3-blue w3-padding-tiny w3-xlarge" title="This profile is important." aria-hidden="true"></i>';
                    }
                    if($user['admin'] != 0) {
                        echo '&nbsp;<i class="fa fa-check w3-red w3-padding-tiny w3-xlarge" title="This user has volunteered to moderate Gr8brik." aria-hidden="true"></i>';
                    }
                    if($userid === '1' || $userid === '5') {
                        echo '&nbsp;<i class="w3-yellow w3-padding-small w3-large" title="This user helped us develop Gr8brik." aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px;" viewBox="0 0 576 512">
                            <path d="M309 106c11.4-7 19-19.7 19-34c0-22.1-17.9-40-40-40s-40 17.9-40 40c0 14.4 7.6 27 19 34L209.7 220.6c-9.1 18.2-32.7 23.4-48.6 10.7L72 160c5-6.7 8-15 8-24c0-22.1-17.9-40-40-40S0 113.9 0 136s17.9 40 40 40c.2 0 .5 0 .7 0L86.4 427.4c5.5 30.4 32 52.6 63 52.6l277.2 0c30.9 0 57.4-22.1 63-52.6L535.3 176c.2 0 .5 0 .7 0c22.1 0 40-17.9 40-40s-17.9-40-40-40s-40 17.9-40 40c0 9 3 17.3 8 24l-89.1 71.3c-15.9 12.7-39.5 7.5-48.6-10.7L309 106z"/>
                        </svg>
                        </i>';
                    }
                    if(trim($id) === trim($userid)) {
                        echo '&nbsp;<a href="/acc"><i class="fa fa-pencil w3-green w3-padding-tiny w3-xlarge" title="This is your profile." aria-hidden="true"></i></a>';
                    }
                    echo "<br /><b class='w3-text-grey'>" . $bbcode->toHTML(htmlspecialchars($user['description'])) . "</b></li>";
                }
            } else {
                echo "<h4 class='w3-padding-small w3-border'>Please check your internet connection</h4>";
            }

        ?>
    </ul>
<?php include('linkbar.php') ?>
</body>
</html>