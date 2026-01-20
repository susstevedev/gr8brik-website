<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';
	require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/filesize.php';

    if (isset($_GET['featured'])) {
        header('Content-type: application/json');
        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
        $i = 0;

        if ($conn->connect_error || $conn2->connect_error) {
            header('HTTP/1.0 500 Internal Server Error');
            exit("fail  " . $conn->connect_error . " / " . $conn2->connect_error);
        }

        /*$unique = filter_input(INPUT_GET, 'unique', FILTER_SANITIZE_STRING);
        if (!$unique) {
            header('HTTP/1.0 403 Forbidden');
            exit(json_encode(["error" => "unique id missing"]));
        }
        */

        $stmt = $conn2->prepare("SELECT model_id FROM featured ORDER BY id DESC");
        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result) {
            header('HTTP/1.0 500 Internal Server Error');
            exit("query error " . $conn2->error);
        }

        $builds = [];

        while ($row = $result->fetch_assoc()) {
            $build_count = $result->num_rows;
            $model_id = $row['model_id'];

            $stmt = $conn2->prepare("SELECT * FROM model WHERE id = ?");
            $stmt->bind_param("s", $model_id);
            $stmt->execute();
            $result2 = $stmt->get_result();

            if ($result2 && $row2 = $result2->fetch_assoc()) {
                $truncated_name = substr($row2['name'], 0, 30);
                if (strlen($row2['name']) >= 30) {
                    $truncated_name .= "...";
                }

                $userid = $row2['user'];

                $stmt2 = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt2->bind_param("s", $userid);
                $stmt2->execute();
                $result3 = $stmt2->get_result();

                if ($result3 && $user = $result3->fetch_assoc()) {
                    $truncated_username = substr($user['username'], 0, 15);
                    if (strlen($user['username']) >= 15) {
                        $truncated_username .= "...";
                    }

                    $builds[] = [
                        'fetched_at' => $_SERVER['REQUEST_TIME'],
                        'model_id' => $row2['id'],
                        'user' => $user['id'],
                        'username' => $truncated_username,
                        'title' => $truncated_name,
                        'views' => $row2['views'],
                        'likes' => $row2['likes']
                    ];
                }
            }
            if (++$i == 5) break;
        }

        echo json_encode(['build_count' => $build_count, 'builds' => $builds]);
        exit;
    }

    if (isset($_GET['feature_v2'])) {
        header('Content-type: application/json');
        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

        if ($conn->connect_error || $conn2->connect_error) {
            header('HTTP/1.0 500 Internal Server Error');
            exit("Could not connect to database");
        }


            $stmt = $conn2->prepare("SELECT * FROM model WHERE feature = 1 ORDER BY date DESC LIMIT 5");
            $stmt->execute();
            $result2 = $stmt->get_result();
            $build_count = $result2->num_rows;
            $builds = [];
      
            while ($row2 = $result2->fetch_assoc()) {
              
                $truncated_name = substr($row2['name'], 0, 30);
                if (strlen($row2['name']) >= 30) {
                    $truncated_name .= "...";
                }
                
                $userid = $row2['user'];

                $stmt2 = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt2->bind_param("s", $userid);
                $stmt2->execute();
                $result3 = $stmt2->get_result();

                if ($result3 && $user = $result3->fetch_assoc()) {
                    $truncated_username = substr($user['username'], 0, 15);
                    if (strlen($user['username']) >= 15) {
                        $truncated_username .= "...";
                    }

                    $builds[] = [
                        'model_id' => $row2['id'],
                        'user' => $user['id'],
                        'username' => $truncated_username,
                        'pfp' => $user['picture'],
                        'title' => $truncated_name,
                        'views' => $row2['views'],
                        'likes' => $row2['likes']
                    ];
                }
        }

        echo json_encode([
          'fetched_at_int' => $_SERVER['REQUEST_TIME'], 
          'fetched_at_str' => date("Y-m-d H:i:s", $_SERVER['REQUEST_TIME']), 
          'build_count' => $build_count, 
          'builds' => $builds
        ]);
        exit;
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php if(isset($_COOKIE['token'])) { ?>
        <title>All-in-browser Lego &reg; modeler</title>
    <?php } else { ?>
        <title>Creations</title>
    <?php } ?>

    <?php include 'header.php' ?>
</head>
<body class="w3-light-blue w3-container radial-gradient">

        <?php
            include 'com/bbcode.php';
            include 'navbar.php';
            $bbcode = new BBCode;
        ?>

        <?php if(isset($_GET['invalid_login'])) { ?>
            <span id='error' class='w3-padding w3-round w3-red w3-top'>You've been logged out because
                <?php echo isset($_GET['reason']) ? htmlspecialchars(rawurldecode($_GET['reason']), ENT_QUOTES, 'UTF-8') : "of an unknown reason"; ?>!
            </span>
        <?php } ?>


        <div class="w3-container">
            <span class="w3-left">
                <h2>Creations</h2>
            </span>
            <span class="w3-right">
                <a href="/modeler" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo">Start Building</a>
            </span>
        </div>

        <input class="w3-input w3-border w3-threequarter" type="text" value="<?php if (isset($_GET['q'])) { echo $_GET['q']; } ?>" id="search-input-2" placeholder="Search for...">
        <button style="height: 40px" class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border w3-quarter" id="search-button-2"><i class="fa fa-search" aria-hidden="true"></i></button><br /><br />

        <script>
            function checkCookieExists(n) {
                if (document.cookie.split(';').some((item) => item.trim().startsWith(n + '='))) {
                    return true;
                }
                return false;
            }

            $(document).ready(function() {
                function loadSearch() {
                    var search = $('#search-input-2').val();
                        $.ajax({
                            url: '/list.php',
                            type: 'GET',
                            data: { q: search },
                            success: function(response) {
                                window.location.href = "/list?q=" + encodeURIComponent(search);
                            }
                        });
                }

                $('#search-input-2').on('keyup', function(e) {
                    if (e.keyCode === 13) {
                        loadSearch();
                    }
                })
                $('#search-button-2').on('click', function() {
                    loadSearch();
                });
            });

        </script>

        <form method="get">
            <label for="sort"><span>Sort by&nbsp;</span></label>
            <select name="sort" id="sort">
                <option disabled>Select...</option>
                <?php                	
            		if ($_GET['sort'] === "follow" || empty($_GET['sort'])) {
                        echo "<option value='follow' selected>Following</option>";
                    } else {
                        echo "<option value='follow'>Following</option>";
                    }
                    
                    if ($_GET['sort'] === "feature") {
                        echo "<option value='feature' selected>Featured</option>";
                    } else {
                        echo "<option value='feature'>Featured</option>";
                    }
                    
                    if ($_GET['sort'] === "all") {
                        echo "<option value='all' selected>All</option>";
                    } else {
                        echo "<option value='all'>All</option>";
                    }
                    
                    if ($_GET['sort'] === "views") {
                        echo "<option value='views' selected>Most viewed</option>";
                    } else {
                        echo "<option value='views'>Most viewed</option>";
                    }
                    
                    if ($_GET['sort'] === "likes") {
                        echo "<option value='likes' selected>Most liked</option>";
                    } else {
                        echo "<option value='likes'>Most liked</option>";
                    }
                    
                    if ($_GET['sort'] === "az") {
                        echo "<option value='az' selected>Alphabetical A-Z</option>";
                    } else {
                        echo "<option value='az'>Alphabetical A-Z</option>";
                    }
                    
                    if ($_GET['sort'] === "za") {
                        echo "<option value='za' selected>Alphabetical Z-A</option>";
                    } else {
                        echo "<option value='za'>Alphabetical Z-A</option>";
                    }
                    
                    if ($_GET['sort'] === "oldest") {
                        echo "<option value='oldest' selected>Oldest</option>";
                    } else {
                        echo "<option value='oldest'>Oldest</option>";
                    }
                    
                    if ($_GET['sort'] === "newest") {
                        echo "<option value='newest' selected>Newest</option>";
                    } else {
                        echo "<option value='newest'>Newest</option>";
                    }
               	?>
                <!-- <option value="follow">Following</option>
                <option value="feature">Featured</option>
                <option value="all">All</option>
                <option value="views">Most viewed</option>
                <option value="likes">Most liked</option>
                <option value="az">Alphabetical A-Z</option>
                <option value="za">Alphabetical Z-A</option>
                <option value="oldest">Oldest</option>
                <option value="newest">Newest</option> -->
            </select>
            <input class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" type="submit" value="Apply!">
        </form><br />
        
        <?php

        $conn  = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
        $is_search = false;

        $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        $offset = ($page - 1) * 12;

        if (isset($_COOKIE['token']) && $tokendata->num_rows != 0) {
            $stmt = $conn->prepare('SELECT * FROM follow WHERE userid = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();

            $followed_users = [];

            if ($result->num_rows != 0) {
                while ($row = $result->fetch_assoc()) {
                    $followed_users[] = $row['profileid'];
                }

                $sql = 'SELECT * FROM model WHERE user IN (' . implode(',', $followed_users) . ') ORDER BY date DESC LIMIT 12 OFFSET ' .  $offset;
              
              if (
                isset($_GET['sort']) 
                && $_GET['sort'] === "follow" 
                || empty($_GET['sort']) 
                && empty($_GET['q'])
              ) {
                echo '<p>You can get a better feed by following more people. <a href="/users">Users page</a>.</p>';
              }
              
            } else {
                $sql = 'SELECT * FROM model WHERE removed = 0 ORDER BY id DESC LIMIT 12 OFFSET ' .  $offset;

                echo '<h4>You\'re not following anyone</h4>';
                echo '<p>Start following people to engage with more people and get a custom feed.</p>';
                echo '<a href="/users" class="w3-btn w3-blue w3-hover-opacity w3-round w3-padding w3-border w3-border-indigo">Users Page</a><br /><b>Or...</b><br />';
                echo '<a href="/rules">Rules Page</a>&nbsp;<a href="/terms">Terms and Conditions</a>&nbsp;<a href="/privacy">Privacy Policy</a>';
            }
        } else {
            $sql = 'SELECT * FROM model WHERE removed = 0 ORDER BY id DESC LIMIT 12 OFFSET ' .  $offset;
        }

        if (isset($_GET['q']) && $_GET['q']) {
            $is_search = true;

            $query = isset($_GET['q']) ? trim($_GET['q']) : '';
            $search = "%" . $conn2->real_escape_string(htmlspecialchars($query)) . "%";

            $stmt = $conn2->prepare('SELECT * FROM model WHERE (name LIKE ? OR description LIKE ?) LIMIT 12 OFFSET ' .  $offset);
            $stmt->bind_param('ss', $search, $search);
            $stmt->execute();
            $result2 = $stmt->get_result();

            echo '<p>Search results for <b>' . htmlspecialchars($query) . '</b></p><br />';
        }

        if (isset($_GET['sort']) && $_GET['sort']) {
            if ($_GET['sort'] === 'feature') {
                $sql  = 'SELECT * FROM model WHERE feature = 1 ORDER BY date DESC LIMIT 12 OFFSET ' .  $offset;
                $sort = 'Featured creations';
            }
            if ($_GET['sort'] === 'views') {
                $sql  = 'SELECT * FROM model ORDER BY views DESC LIMIT 12 OFFSET ' .  $offset;
                $sort = 'Most viewed';
            }
            if ($_GET['sort'] === 'likes') {
                //$sql = 'SELECT model.*, COUNT(*) AS vote_count FROM model LEFT JOIN votes ON model.id = votes.creation WHERE model.removed = 0 GROUP BY model.id ORDER BY vote_count DESC LIMIT 12 OFFSET ' .  $offset;
                $sql  = 'SELECT * FROM model ORDER BY likes DESC LIMIT 12 OFFSET ' .  $offset;
                $sort = 'Most liked';
            }
            if ($_GET['sort'] === 'az') {
                $sql  = 'SELECT * FROM model ORDER BY name ASC LIMIT 12 OFFSET ' .  $offset;
                $sort = 'Alphabetical A-Z';
            }
            if ($_GET['sort'] === 'za') {
                $sql  = 'SELECT * FROM model ORDER BY name DESC LIMIT 12 OFFSET ' .  $offset;
                $sort = 'Alphabetical Z-A';
            }
            if ($_GET['sort'] === 'oldest') {
                $sql  = 'SELECT * FROM model ORDER BY date ASC LIMIT 12 OFFSET ' .  $offset;
                $sort = 'Oldest creations';
            }
            if ($_GET['sort'] === 'newest') {
                $sql  = 'SELECT * FROM model ORDER BY date DESC LIMIT 12 OFFSET ' .  $offset;
                $sort = 'Newest creations';
            }
            if ($_GET['sort'] === 'all') {
                $sql  = 'SELECT * FROM model WHERE removed = 0 ORDER BY id DESC LIMIT 12 OFFSET ' .  $offset;
                $sort = 'All creations';
            }
            if (!empty($sort)) {
                echo '<p>Sorting by <b>' . $sort . '</b></p>';
            }
        }

        if(!$is_search && $page > 0) {
            $stmt = $conn2->prepare($sql);
            $stmt->execute();
            $result2 = $stmt->get_result();
        }
        
        // if there is some creations found from the query
        if ($result2->num_rows > 0) {
          
            echo '<h4><i class="fa fa-info-circle w3-padding-small" aria-hidden="true"></i>' . $result2->num_rows . ' total creations</h4>';
          
            echo "<table class='w3-table-all' style='color:black;'>";
            while ($row = $result2->fetch_assoc()) {
              
                $model_id = $row['id'];
                $userid = $row['user'];

                /*
                    Fetch users, like count, and bans
                    We also check if those are invalid
                */
              
                $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
                $stmt->bind_param('i', $userid);
                $stmt->execute();
                $result3 = $stmt->get_result();
                $user = $result3->fetch_assoc();
                $username = isset($user['username']) ? $user['username'] : '';

                /*
                $stmt = $conn2->prepare('SELECT COUNT(*) as count FROM votes WHERE creation = ?');
                $stmt->bind_param('i', $model_id);
                $stmt->execute();
                $result4 = $stmt->get_result();
                $likes = $result4->fetch_assoc();
                */

                $stmt = $conn->prepare('SELECT * FROM bans WHERE user = ?');
                $stmt->bind_param('i', $userid);
                $stmt->execute();
                $banResult = $stmt->get_result();
                $banRow = $banResult->fetch_assoc();

                if (empty($row['name'])) {
                    $row['name'] = $username . "'s creation";
                }

                /*if ($result3->num_rows <= 0 || $banResult->num_rows > 0 && $banRow['end_date'] >= time()) {
                    continue;
                }*/

                $truncatedName = substr($row['name'], 0, 30);
                if (strlen($row['name']) >= 30) {
                    $truncatedName .= '...';
                }

                /*echo "<div class='w3-display-container w3-left w3-padding'>";
                echo "<a href='/build/" . $row['id'] . "'><img src='/cre/" . $row['screenshot'] . "' width='320' height='240' loading='lazy' class='gr8-theme w3-card-2 w3-hover-shadow w3-border w3-border-white'></a>";
                echo "<span class='gr8-theme w3-large w3-display-middle w3-card-2 w3-light-grey w3-padding-small'>" . $truncatedName . '</span>';
                echo "<span class='gr8-theme w3-display-bottommiddle w3-card-2 w3-light-grey w3-padding-small'>" . $row['views'] . ' views - ' . $likes['count'] . " likes - By ";
                echo "<a href='/user/" . $row['user'] . "'>" . $username . "</a></span></div>"; */

                ?>

                <div class='w3-display-container w3-left w3-padding'>
                <a href='/build/<?php echo $row['id'] ?>'><img src='<?php echo $row['screenshot'] ?>' width='320px' height='240px' loading='lazy' class='w3-card-2 w3-hover-shadow w3-grey'></a>
                <div class='gr8-theme w3-card-2 w3-light-grey w3-padding-small'><h4><?php echo $truncatedName ?></h4>
                <span>By <a href='/user/<?php echo $row['user'] ?>'><?php echo $username ?></a> on <?php echo date("D, M d, Y", strtotime($row['date'])) ?></span>
                <br />
                <span>Viewed <?php echo $row['views'] ?> times, <?php echo $row['likes'] ?> likes, <?php echo size_unit($row['size']) ?></span>
                </div></div>

                <?php
            }
            echo "</table><br /><br />";
        } else {
            echo "<b>No creations found.</b><br />";
        }
        // Foward back buttons
        $sorting = isset($_GET['sort']) ? $_GET['sort'] : "following";
        $searching = isset($_GET['q']) ? $_GET['q'] : "";
        echo '<a class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" href="?p=' . ($page - 1) . '&sort=' . $sorting . '&q=' . $searching . '">Back</a>&nbsp;';
        echo '<a class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" href="?p=' . ($page + 1) . '&sort=' . $sorting . '&q=' . $searching . '">Forward</a>';
    ?>
			
    <?php include('linkbar.php'); ?>
</body>
</html>