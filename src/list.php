<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/numbers.php';

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
                'likes' => $row2['likes'],
                'thumb' => $row2['screenshot'] //new addition!
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

if (isset($_GET['feature_v3'])) {
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

    if ($conn->connect_error || $conn2->connect_error) {
        header('HTTP/1.0 500 Internal Server Error');
        exit("Could not connect to database");
    }


    $stmt = $conn2->prepare("SELECT * FROM model WHERE feature = 1 ORDER BY date DESC LIMIT 6");
    $stmt->execute();
    $result2 = $stmt->get_result();
    $build_count = $result2->num_rows;
    $creations = [];

    while ($row2 = $result2->fetch_assoc()) {
        $truncated_name = substr($row2['name'], 0, 30);
        if (strlen($row2['name']) >= 30) {
            $truncated_name .= "...";
        }

        $userid = $row2['user'];

        $usero = User::getUser($userid);
        $userid = $usero->id;

        if (!User::isDeleted($userid)) {
            $truncated_username = substr($usero->username ?: 'Untited User', 0, 15);
            if (strlen($usero->username) >= 15) {
                $truncated_username .= "...";
            }

            $creations[] = [
                'model_id' => $row2['id'],
                'user' => $userid,
                'username' => $truncated_username,
                'pfp' => $usero->picture,
                'title' => $truncated_name,
                'views' => $row2['views'],
                'likes' => $row2['likes'],
                'thumb' => $row2['screenshot'],
                'comments' => $row2['replies'],
                'date' => time_ago($row2['date']),
            ];
        }
    }

    echo json_encode([
        'build_count' => $build_count,
        'creations' => $creations
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Creations</title>
    <?php include 'header.php' ?>
</head>

<body class="w3-container">
    <?php
    include 'com/bbcode.php';
    include 'navbar.php';
    $bbcode = new BBCode;
    ?>

    <div class="w3-container">
        <span class="w3-left">
            <h2>Creations</h2>
        </span>
        <span class="w3-right">
            <a href="/modeler" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo">
                <i class="fa fa-cubes" aria-hidden="true"></i>
                <span>Start Building</span>
            </a>
        </span>
    </div>

    <?php
    // Foward back buttons
    $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
    $offset = ($page - 1) * 12;
    $sorting = isset($_GET['sort']) ? $_GET['sort'] : "following";
    $searching = isset($_GET['q']) ? $_GET['q'] : "";

    echo '<a href="?p=' . ($page - 1) . '&sort=' . $sorting . '&q=' . $searching . '"><button class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Back</button></a>&nbsp;';
    echo '<a href="?p=' . ($page + 1) . '&sort=' . $sorting . '&q=' . $searching . '"><button class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Forward</button></a>';
    ?>

    <div class="w3-right" id="sorting-options" style="display: flex; align-items: center; gap: 15px;">
        <form id="list-search-cre" name="list-search-cre" class="w3-white" method="get" action="" style="display: inline-flex; align-items: center; border: 1px solid #ccc;">
            <button form="list-search-cre" class="w3-btn w3-hover-none w3-mobile" id="search-button-2">
                <i class="fa fa-search" aria-hidden="true"></i>
            </button>
            <input name="q" class="w3-padding" type="text" value="<?php if (isset($_GET['q'])) {
                                                                        echo htmlspecialchars($_GET['q']);
                                                                    } ?>" id="search-input-2" placeholder="Search for..." style="border:none; outline:none;">
        </form>

        <form id="list-tags-cre" name="list-tags-cre" class="w3-white" method="get" action="" style="display: inline-flex; align-items: center; border: 1px solid #ccc;">
            <button form="list-tags-cre" class="w3-btn w3-hover-none w3-mobile">
                <i class="fa fa-search" aria-hidden="true"></i>
            </button>
            <input name="t" class="w3-padding" type="text" value="<?php if (isset($_GET['t'])) {
                                                                        echo htmlspecialchars($_GET['t']);
                                                                    } ?>" placeholder="Tags (seperated by comma)" style="border:none; outline:none;">
        </form>

        <form id="sorting" name="sorting" method="get" style="display: inline-flex; align-items: center; gap: 8px;">
            <label for="sort"><span>Sort by&nbsp;</span></label>
            <select class="w3-select w3-light-grey w3-border w3-border-grey" name="sort" id="sort" style="width: auto; padding: 4px 8px;">
                <option disabled>Select...</option>
                <?php
                $selected_sort = isset($_GET['sort']) ? $_GET['sort'] : 'follow';

                $options = [
                    'follow'  => 'Following',
                    'feature' => 'Featured',
                    'all'     => 'All',
                    'views'   => 'Most viewed',
                    'size'    => 'File size',
                    'likes'   => 'Most liked',
                    'az'      => 'Alphabetical A-Z',
                    'za'      => 'Alphabetical Z-A',
                    'oldest'  => 'Oldest',
                    'newest'  => 'Newest'
                ];

                foreach ($options as $value => $label) {
                    $selected = ($selected_sort === $value) ? 'selected' : '';
                    echo "<option value='{$value}' {$selected}>{$label}</option>";
                }
                ?>
            </select>
            <button class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" type="submit">
                <i class="fa fa-filter" aria-hidden="true"></i>
                <span>Apply</span>
            </button>
        </form>
    </div>

    <div class="w3-row-padding" id="main-creations-grid">
        <?php
        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
        $is_search = false;

        if (loggedin()) {
            $stmt = $conn->prepare('SELECT * FROM follow WHERE userid = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();

            $followed_users = [];

            if ($result->num_rows != 0) {
                while ($row = $result->fetch_assoc()) {
                    $followed_users[] = $row['profileid'];
                }

                $sql = 'SELECT * FROM model WHERE user IN (' . implode(',', $followed_users) . ') ORDER BY date DESC LIMIT 12 OFFSET ' . (int)$offset;

                if ((isset($_GET['sort']) && $_GET['sort'] === "follow") || (empty($_GET['sort']) && empty($_GET['q']))) {
                    echo '<div class="w3-col s12 w3-margin-bottom"><p class="w3-panel w3-pale-blue w3-padding w3-round">You can get a better feed by following more people. <a href="/users">Find people to follow</a>.</p></div>';
                }
            } else {
                $sql = 'SELECT * FROM model WHERE removed = 0 ORDER BY id DESC LIMIT 12 OFFSET ' . (int)$offset;

                echo '<div class="w3-col s12 w3-margin-bottom w3-panel w3-pale-blue w3-round">';
                echo '<h4>You\'re not following anyone</h4>';
                echo '<p>Start following people to engage with more people and get a custom feed. <a href="/users">Find people to follow</a>.</p>';
                echo '<a href="/rules">Rules</a> • <a href="/terms">Terms and Conditions</a> • <a href="/privacy">Privacy Policy</a>';
                echo '</div>';
            }
        } else {
            $sql = 'SELECT * FROM model WHERE removed = 0 ORDER BY id DESC LIMIT 12 OFFSET ' . (int)$offset;
        }

        if (isset($_GET['q']) && $_GET['q']) {
            $is_search = true;
            $query = trim($_GET['q']);
            $search = "%" . $conn2->real_escape_string(htmlspecialchars($query)) . "%";

            $stmt = $conn2->prepare('SELECT * FROM model WHERE (name LIKE ? OR description LIKE ?) LIMIT 12 OFFSET ' . (int)$offset);
            $stmt->bind_param('ss', $search, $search);
            $stmt->execute();
            $result2 = $stmt->get_result();

            echo '<div class="w3-col s12"><p>Search results for <b>' . htmlspecialchars($query) . '</b></p></div>';
        }

        if (isset($_GET['t']) && $_GET['t']) {
            $is_search = true;
            $query = trim($_GET['t']);
            $search = "%$query%";

            $sql = "SELECT m.* FROM model m JOIN tags t ON m.id = t.model_id WHERE t.tag_name LIKE ? LIMIT 12 OFFSET " . (int)$offset;

            $stmt = $conn2->prepare($sql);
            $stmt->bind_param('s', $search);
            $stmt->execute();
            $result2 = $stmt->get_result();

            echo '<div class="w3-col s12"><p>Models tagged <b>' . htmlspecialchars($query) . '</b></p></div>';
        }

        if (isset($_GET['sort']) && $_GET['sort']) {
            $sort_options = [
                'feature' => ['sql' => 'SELECT * FROM model WHERE removed = 0 AND visibility = "public" AND feature = 1 ORDER BY date DESC', 'label' => 'Featured creations'],
                'views'   => ['sql' => 'SELECT * FROM model WHERE removed = 0 AND visibility = "public" ORDER BY views DESC', 'label' => 'Most viewed'],
                'size'    => ['sql' => 'SELECT * FROM model WHERE removed = 0 AND visibility = "public" ORDER BY size DESC', 'label' => 'Biggest in size'],
                'likes'   => ['sql' => 'SELECT * FROM model WHERE removed = 0 AND visibility = "public" ORDER BY likes DESC', 'label' => 'Most liked'],
                'az'      => ['sql' => 'SELECT * FROM model WHERE removed = 0 AND visibility = "public" ORDER BY name ASC', 'label' => 'Alphabetical A-Z'],
                'za'      => ['sql' => 'SELECT * FROM model WHERE removed = 0 AND visibility = "public" ORDER BY name DESC', 'label' => 'Alphabetical Z-A'],
                'oldest'  => ['sql' => 'SELECT * FROM model WHERE removed = 0 AND visibility = "public" ORDER BY date ASC', 'label' => 'Oldest creations'],
                'newest'  => ['sql' => 'SELECT * FROM model WHERE removed = 0 AND visibility = "public" ORDER BY date DESC', 'label' => 'Newest creations'],
                'all'     => ['sql' => 'SELECT * FROM model WHERE removed = 0 AND visibility = "public" ORDER BY id DESC', 'label' => 'All creations']
            ];

            if (array_key_exists($_GET['sort'], $sort_options)) {
                $sql  = $sort_options[$_GET['sort']]['sql'] . ' LIMIT 12 OFFSET ' . (int)$offset;
                $sort = $sort_options[$_GET['sort']]['label'];
                echo '<div class="w3-col s12"><p>Sorting by <b>' . $sort . '</b></p></div>';
            }
        }

        if (!$is_search && $page > 0) {
            $stmt = $conn2->prepare($sql);
            $stmt->execute();
            $result2 = $stmt->get_result();
        }

        if (isset($result2) && $result2 !== null && $result2->num_rows > 0) {
        ?>
            <div class="w3-col s12">
                <h4><i class="fa fa-info-circle w3-padding-small" aria-hidden="true"></i><?php echo $result2->num_rows; ?> creations found</h4>
            </div>

            <?php
            while ($row = $result2->fetch_assoc()) {
                $model_id = $row['id'];
                $userid = $row['user'];
                $user = User::getUser($userid);

                if (empty($row['name'])) {
                    $row['name'] = $user->username . "'s creation";
                }

                $truncatedName = (mb_strlen($row['name']) > 30) ? mb_substr($row['name'], 0, 30) . '...' : $row['name'];
            ?>

                <!-- new ui because old one got boring -->
                <div class="w3-col l4 m6 s12 w3-margin-bottom">
                    <div class="gr8-theme w3-card-2 w3-light-grey w3-padding creation-card">
                        <a href="/build/<?php echo $row['id']; ?>" class="creation-link">
                            <img src="<?php echo !empty($row['screenshot']) ? $row['screenshot'] : '/img/no_image.png'; ?>" loading="lazy" class="cre-image w3-hover-opacity w3-card-2 w3-grey creation-thumbnail" alt="<?php echo htmlspecialchars($row['name']); ?>">
                            <h4 class="creation-title"><?php echo htmlspecialchars($truncatedName); ?></h4>
                        </a>
                        <div class="creation-meta">
                            <span class="meta-author">
                                <?php if (!User::isDeleted($userid)) { ?>
                                    By <a href="/@<?php echo rawurlencode($user->username); ?>"><b><?php echo $user->username; ?></b></a>
                                <?php } else { ?>
                                    By <i><?php echo $user->username; ?></i>
                                <?php } ?>
                                on <?php echo date("d M Y", strtotime($row['date'])); ?>
                            </span>

                            <div class="meta-stats">
                                <span><i class="fa fa-eye"></i> <?php echo Numbers::format($row['views']); ?> views</span> •
                                <span><i class="fa fa-star"></i> <?php echo Numbers::format($row['likes']); ?> favorites</span>
                                <span class="w3-right"><?php echo Numbers::filesize($row['size']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
        <?php
            }
        } else {
            echo '<div class="w3-col s12 w3-center w3-padding-large"><b>¯\_(ツ)_/¯ No creations found. Sorry!</b></div>';
        }
        ?>
    </div>
    <?php include 'linkbar.php' ?>
</body>

</html>