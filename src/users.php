<!DOCTYPE html>
<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/bbcode.php';
    $bbcode = new BBCode;
?>
<head>
    <title>Users</title>
    <?php include 'header.php' ?>
</head>
<body class="w3-light-blue w3-container">
<?php include('navbar.php'); ?>
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
        <input name="q" class="w3-padding" type="text" value="<?php echo isset($_GET['q']) ? $_GET['q'] : null ?>" id="search-input-2" placeholder="Search using user email...">
        <input class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" type="submit" value="Apply">
    </form>
    <p>If a user deactivated their account, or was banned, their account will no longer show up down below for privacy reasons.</p>
        <?php
        //this is ass code 0/10 why why why wnhy why

            $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
            if ($conn->connect_error) {
                exit($conn->connect_error);
            }
                                                                                                                             
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
			if($page < 1) {
                $page = 1;
            }

           	$limit = 8;
            $offset = ($page - 1) * $limit;
            $pDown = $page - 1;
            $pUp = $page + 1;

			$count_result = $conn->query("SELECT COUNT(*) as total_users FROM users");
            $count_row = $count_result->fetch_assoc();
          	$total_pages = ceil($count_row['total_users'] / $limit);

            $search = false;
            if(isset($_GET['q']) && $_GET['q']) {
                $search = "%" . $conn->real_escape_string($_GET['q']) . "%";
            }

            if (isset($_GET['sort']) && $_GET['sort'] && !$search) {
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
            } else if(isset($_GET['sort']) && $_GET['sort'] && $search) {
                $sql = "SELECT * FROM users WHERE username LIKE '$search' ORDER BY id DESC";
                if($_GET['sort'] === 'desc') {
                    $sql = "SELECT * FROM users WHERE username LIKE '$search' ORDER BY id DESC LIMIT $limit OFFSET $offset";
                } elseif($_GET['sort'] === 'username_a') {
                    $sql = "SELECT * FROM users WHERE username LIKE '$search' ORDER BY username ASC LIMIT $limit OFFSET $offset";
                } elseif($_GET['sort'] === 'username_d') {
                    $sql = "SELECT * FROM users WHERE username LIKE '$search' ORDER BY username DESC LIMIT $limit OFFSET $offset";
                } elseif($_GET['sort'] === 'age') {
                    $sql = "SELECT * FROM users WHERE username LIKE '$search' ORDER BY age DESC LIMIT $limit OFFSET $offset";
                } elseif($_GET['sort'] === 'admin') {
                    $sql = "SELECT * FROM users WHERE admin = '1' AND username LIKE '$search' ORDER BY age DESC LIMIT $limit OFFSET $offset";
                } else {
                    $sql = "SELECT * FROM users WHERE username LIKE '$search' ORDER BY id DESC LIMIT $limit OFFSET $offset";
                }
            } else {
                $sql = "SELECT * FROM users ORDER BY id DESC LIMIT $limit OFFSET $offset";
            }

            $result = $conn->query($sql) or die('no');

            if ($result->num_rows > 0) {
                $users = $result->fetch_all(MYSQLI_ASSOC);
            ?>

                <h3><?php echo $count_row['total_users'] ?> total registered users, <?php echo $total_pages ?> pages, on page <?php echo $page ?></h3>
                <a class="w3-btn w3-blue w3-hover-opacity w3-round w3-border w3-border-indigo" href="?page=<?php echo $pDown ?>">Back</a>
                <a class="w3-btn w3-blue w3-hover-opacity w3-round w3-border w3-border-indigo" href="?page=<?php echo $pUp ?>">Next</a>
                <div class='w3-row'>

            <?php
                foreach ($users as $user) {
                    $userid = $user['id'];

                    echo "<div class='w3-col w3-section w3-padding w3-card-2 w3-round w3-border w3-border-grey w3-row'>";
                    echo "<a href='/@" . urlencode($user['username']) . "'>";

                    if(isset($user['picture']) && $user['picture'] !== '/img/no_image.png') {
                        echo "<div class='w3-col m1'><img id='pfp' width='100px' height='100px' class='w3-circle' src='" . $user['picture'] . "'></div>";
                    }

                    echo "<div class='w3-rest'>";
                    if($user['admin'] === '1') {
                        echo "<h4 class='w3-text-red'><i class='fa fa-at' aria-hidden='true'></i>";
                    } else {
                        echo "<h4><i class='fa fa-at' aria-hidden='true'></i>";
                    }
                    echo htmlspecialchars($user['username']) . "</a></h4>";

                    if(trim($id) === trim($userid)) {
                        echo '<a href="/acc"><i class="fa fa-pencil" aria-hidden="true"></i>Edit my details</a>';
                    }

                    echo "<p>" . $bbcode->toHTML($user['description'], true, true) . "</p>";
                    echo "<p>Registered " . time_ago($user['age']) . "</p></div></div><br />";
                }

                echo "</div>";
            }
        ?>
    </ul>
<?php include('linkbar.php') ?>
</body>
</html>