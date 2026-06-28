<!DOCTYPE html>
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
<div class="w3-container w3-center">
    <h1>All Users</h1>

    <div class="w3-card-2 w3-light-grey gr8-theme w3-padding-small w3-center">
        <h5>If you wish us to remove any personal details we hold about you, please email us at <i class="fa fa-envelope"><a href="mailto:<?php echo DB_MAIL ?>"><?php echo DB_MAIL ?></a></i></h5>
    </div><br />
</div>

<div class="w3-container">
    <ul class="w3-ul w3-card-2 gr8-theme w3-light-grey w3-padding-small">
    <form method="get">
        <label for="sort"><b>Sort options:</b></label>
        <select name="sort" id="sort">
            <option value="desc" selected>Newest registered</option>
            <option value="username_a">A-Z</option>
            <option value="username_d">Z-A</option>
            <option value="age">Creation date</option>
            <option value="admin">Moderator Accounts</option>
        </select>
        <input class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" type="submit" value="Apply">
    </form>
    <p>If a user deactivated their account, or was banned, their account will no longer show up down below for privacy reasons.</p>
        <?php
            $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
            if ($conn->connect_error) {
                exit($conn->connect_error);
            }
                                                                                                                             
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
			if($page < 1){$page = 1;}

           	$limit = 8;
            $offset = ($page - 1) * $limit;
            $pDown = $page - 1;
            $pUp = $page + 1;

			$count_result = $conn->query("SELECT COUNT(*) as total_users FROM users");
            $count_row = $count_result->fetch_assoc();
          	$total_pages = ceil($count_row['total_users'] / $limit);

            if (isset($_GET['sort']) && $_GET['sort']) {
                if($_GET['sort'] === 'desc') {
                    $sql = "SELECT * FROM users ORDER BY id DESC LIMIT $limit OFFSET $offset";
                } elseif($_GET['sort'] === 'username_a') {
                    $sql = "SELECT * FROM users ORDER BY username ASC LIMIT $limit OFFSET $offset";
                } elseif($_GET['sort'] === 'username_d') {
                    $sql = "SELECT * FROM users ORDER BY username DESC LIMIT $limit OFFSET $offset";
                } elseif($_GET['sort'] === 'age') {
                    $sql = "SELECT * FROM users ORDER BY age DESC LIMIT $limit OFFSET $offset";
                } elseif($_GET['sort'] === 'admin') {
                    $sql = "SELECT * FROM users WHERE admin = '1' ORDER BY age DESC LIMIT $limit OFFSET $offset";
                } else {
                    $sql = "SELECT * FROM users ORDER BY id DESC LIMIT $limit OFFSET $offset";
                }
            } else {
                $sql = "SELECT * FROM users ORDER BY id DESC LIMIT $limit OFFSET $offset";
            }
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $users = $result->fetch_all(MYSQLI_ASSOC);

                echo '<h3>' . $count_row['total_users'] . ' total registered users, ' . $total_pages . ' pages, on page ' . $page . '.</h3>';
                echo '<a class="w3-btn w3-blue w3-hover-opacity w3-round w3-border w3-border-indigo" href="?page=' . $pDown . '">Back</a>&nbsp;&nbsp;';
                echo '<a class="w3-btn w3-blue w3-hover-opacity w3-round w3-border w3-border-indigo" href="?page=' . $pUp . '">Next</a><hr />';

                //echo '<h3>' . $count_row['total_users'] . ' total registered users. <a href="/chart" class="reload">View chart</a>.</h3>';
            
                foreach ($users as $user) {
                    $userid = $user['id'];

                    echo "<br /><li class='w3-padding-small w3-border w3-border-grey w3-hover-shadow'><a href='/user/" . htmlspecialchars($userid) . "'>";
                    echo "<img id='pfp' style='width: 100px; height: 100px; border-radius: 50%; border: none;' src='" . htmlspecialchars($user['picture']) . "'>";
                    if($user['admin'] === '1') {
                        echo "&nbsp;<b class='w3-xlarge w3-text-red'>" . htmlspecialchars($user['username']) . "</a></b>";
                    } else {
                        echo "&nbsp;<b class='w3-xlarge'>" . htmlspecialchars($user['username']) . "</a></b>";
                    }
                    if(trim($id) === trim($userid)) {
                        echo '&nbsp;<a href="/acc"><i class="fa fa-pencil w3-xlarge" aria-hidden="true"></i></a>';
                    }
                    echo "<br /><b class='w3-text-grey'>" . $bbcode->toHTML(htmlspecialchars($user['description'])) . "</b></li>";
                }
            }

        ?>
    </ul>
<?php include('linkbar.php') ?>
</body>
</html>