<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/const/utils/time.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/const/utils/numbers.php';

$conn = Database::get(DB_NAME);
$conn2 = Database::get(DB_NAME2);

if (isset($_GET['feature_v3'])) {
    $sql = "SELECT m.*, u.username
    FROM model m
    INNER JOIN users u
    ON u.id = m.user
    WHERE m.feature = 1 AND u.deactive IS NULL
    ORDER BY date
    DESC LIMIT 6";

    $stmt = $conn2->prepare($sql);
    $stmt->execute();
    $result2 = $stmt->get_result();
    $creation_count = 0;
    $creations = [];

    while ($row2 = $result2->fetch_assoc()) {
        $truncated_name = substr($row2['name'], 0, 30);
        if (strlen($row2['name']) >= 30) {
            $truncated_name .= "...";
        }

        $username = $row2['username'] ?? 'User';
        $truncated_username = substr($username, 0, 15);
        if (strlen($username) >= 15) {
            $truncated_username .= "...";
        }

        $creation_count++;
        $creations[] = [
            'model_id' => $row2['id'],
            'user' => $row2['user'],
            'username' => $truncated_username,
            'pfp' => $usero->profile->picture ?? null,
            'title' => $truncated_name,
            'views' => $row2['views'],
            'likes' => $row2['likes'],
            'thumb' => $row2['screenshot'],
            'comments' => $row2['replies'],
            'date' => time_ago($row2['date']),
        ];
    }

    echo json_encode([
        'build_count' => $creation_count,
        'count' => $creation_count,
        'creations' => $creations
    ]);
    exit;
}
?>
<?php
        $pageTitle = 'Creations';
        $pageDesc = 'Creations Page';
        require_once $_SERVER['DOCUMENT_ROOT'] . '/const/includes/header.php';
        include 'navbar.php';
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
        $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        $offset = ($page - 1) * 12;
        $sorting = isset($_GET['sort']) ? $_GET['sort'] : "following";
        $searching = isset($_GET['q']) ? $_GET['q'] : "";

        $sort_options = [
            'follow' => ['sql' => null, 'label' => 'Following'],
            'feature' => ['sql' => 'AND feature = 1 ORDER BY date DESC', 'label' => 'Featured'],
            'all' => ['sql' => 'ORDER BY id DESC', 'label' => 'All'],
            'views' => ['sql' => 'ORDER BY views DESC', 'label' => 'Most viewed'],
            'size' => ['sql' => 'ORDER BY size DESC', 'label' => 'Biggest in filesize'],
            'likes' => ['sql' => 'ORDER BY likes DESC', 'label' => 'Most favorited'],
            'az' => ['sql' => 'ORDER BY name ASC', 'label' => 'Alphabetical A-Z'],
            'za' => ['sql' => 'ORDER BY name DESC', 'label' => 'Alphabetical Z-A'],
            'oldest' => ['sql' => 'ORDER BY date ASC', 'label' => 'Oldest'],
            'newest' => ['sql' => 'ORDER BY date DESC', 'label' => 'Newest'],
        ];
    ?>

    <a href="?p=<?= $page - 1 ?>&sort=<?= $sorting ?>&q=<?= $searching ?>"><button class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Back</button></a>&nbsp;
    <a href="?p=<?= $page + 1 ?>&sort=<?= $sorting ?>&q=<?= $searching ?>"><button class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Forward</button></a>

    <div class="w3-right" id="sorting-options" style="display: flex; align-items: center; gap: 15px;">
        <form id="list-search-cre" name="list-search-cre" class="w3-white" method="get" action="" style="display: inline-flex; align-items: center; border: 1px solid #ccc;">
            <button form="list-search-cre" class="w3-btn w3-hover-none w3-mobile" id="search-button-2">
                <i class="fa fa-search" aria-hidden="true"></i>
            </button>
            <input name="q" class="w3-padding" type="text" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : null ?>" id="search-input-2" placeholder="Search for..." style="border:none; outline:none;">
        </form>

        <form id="list-tags-cre" name="list-tags-cre" class="w3-white" method="get" action="" style="display: inline-flex; align-items: center; border: 1px solid #ccc;">
            <button form="list-tags-cre" class="w3-btn w3-hover-none w3-mobile">
                <i class="fa fa-search" aria-hidden="true"></i>
            </button>
            <input name="t" class="w3-padding" type="text" value="<?php echo isset($_GET['t']) ? htmlspecialchars($_GET['t']) : null ?>" placeholder="Tags (seperated by comma)" style="border:none; outline:none;">
        </form>

        <form id="sorting" name="sorting" method="get" style="display: inline-flex; align-items: center; gap: 8px;">
            <label for="sort"><span>Sort by&nbsp;</span></label>
            <select class="w3-select w3-light-grey w3-border w3-border-grey" name="sort" id="sort" style="width: auto; padding: 4px 8px;">
                <option disabled>Select...</option>

                <?php
                $selected_sort = isset($_GET['sort']) ? $_GET['sort'] : 'follow';

                foreach ($sort_options as $k => $v) {
                    $selected = ($selected_sort === $k) ? 'selected' : null;
                    echo "<option value='{$k}' {$selected}>{$v['label']}</option>";
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
        $is_search = false;
        $sql = $sort_options['all']['sql'];
        $sql_begin = "SELECT
                    m.id, m.name, m.screenshot, m.date, m.views, m.replies, m.likes, m.size, u.username, u.email, u.id AS userid, u.admin AS useradmin, p.picture_small AS profile_picture
                    FROM " . DB_NAME2 . ".model m
                    LEFT JOIN " . DB_NAME . ".users u
                    ON u.id = m.user
                    LEFT JOIN " . DB_NAME . ".user_profiles p
                    ON p.userid = m.user";

        if (loggedin() && isset($current_user)) {
            $id = $current_user->id ?? 0;

            $stmt = $conn->prepare('SELECT profileid FROM follow WHERE userid = ? LIMIT 300');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();

            $followed_users = [];

            if ($result->num_rows != 0) {
                while ($row = $result->fetch_assoc()) {
                    $followed_users[] = $row['profileid'];
                }


                $imploded_users = implode(',', $followed_users);
                $sql = "{$sql_begin} WHERE user IN ({$imploded_users}) ORDER BY date DESC LIMIT 12 OFFSET {$offset}";

                if ((isset($_GET['sort']) && $_GET['sort'] === "follow") || (empty($_GET['sort']) && empty($_GET['q']))) {
                    echo '<div class="w3-col s12 w3-margin-bottom"><p class="w3-panel w3-light-grey w3-padding w3-round">You can get a better feed by following more people. <a href="/users">Find people to follow</a>.</p></div>';
                }
            } else {
                echo '<div class="w3-col s12 w3-margin-bottom w3-panel w3-light-grey w3-round">';
                echo '<h4>You\'re not following anyone</h4>';
                echo '<p>Start following people to engage with more people and get a custom feed. <a href="/users">Find people to follow</a>.</p>';
                echo '<a href="/rules">Rules</a> • <a href="/terms">Terms and Conditions</a> • <a href="/privacy">Privacy Policy</a>';
                echo '</div>';
            }
        }

        if (isset($_GET['q']) && $_GET['q']) {
            $is_search = true;
            $query = trim($_GET['q']);
            $search = "%" . $conn2->real_escape_string(htmlspecialchars($query)) . "%";

            $stmt = $conn2->prepare("{$sql_begin} WHERE (name LIKE ? OR description LIKE ?) LIMIT 12 OFFSET {$offset}");
            $stmt->bind_param('ss', $search, $search);
            $stmt->execute();
            $result2 = $stmt->get_result();

            echo '<div class="w3-col s12"><p>Search results for <b>' . htmlspecialchars($query) . '</b></p></div>';
        }

        if (isset($_GET['t']) && $_GET['t']) {
            $is_search = true;
            $query = trim($_GET['t']);
            $tags = array_filter(array_map('trim', explode(',', $query)));

            if (!empty($tags)) {
                $placeholders = implode(', ', array_fill(0, count($tags), '?'));

                $stmt = $conn2->prepare
                ("SELECT DISTINCT m.* FROM model m
                JOIN model_tags mt ON m.id = mt.model_id
                JOIN tags t ON mt.tag_id = t.id
                WHERE t.tag_name IN ($placeholders)
                LIMIT 12 OFFSET {$offset}");

                $types = str_repeat('s', count($tags));
                $stmt->bind_param($types, ...$tags);
                $stmt->execute();
                $result2 = $stmt->get_result();

                $escaped_tags = array_map('htmlspecialchars', $tags);
                echo '<div class="w3-col s12"><p>Creations tagged <b>' . implode(', ', $escaped_tags) . '</b></p></div>';
            }
        }

        if (isset($_GET['sort'])) {
            if (array_key_exists($_GET['sort'], $sort_options)) {
                if(isset($sort_options[$_GET['sort']]['sql'])) {
                    $sort = $sort_options[$_GET['sort']]['label'];
                    $sort_sql = $sort_options[$_GET['sort']]['sql'];

                    $sql = "{$sql_begin} WHERE m.removed = 0 AND m.visibility = 'public' {$sort_sql} LIMIT 12 OFFSET {$offset}";

                    echo '<div class="w3-col s12"><p>Sorting by <b>' . $sort . '</b></p></div>';
                }
            }
        }

        if (!$is_search && $page > 0) {
            $stmt = $conn2->prepare($sql);
            $stmt->execute();
            $result2 = $stmt->get_result();
        }
        ?>

        <?php if (isset($result2) && $result2 !== null && $result2->num_rows > 0): ?>
            <?php if($is_search): ?>
                <div class="w3-col s12">
                    <h4><i class="fa fa-info-circle w3-padding-small" aria-hidden="true"></i><?= $result2->num_rows; ?> creations found</h4>
                </div>
            <?php endif; ?><br />

            <?php
                $rows = [];
                while ($row = $result2->fetch_assoc()) {
                    $rows[] = $row;
                }

                foreach($rows as $row):
                    if (empty($row['name'])) {
                        $row['name'] = ($row['username'] ?? 'User') . "'s creation";
                    }

                    $truncatedName = (mb_strlen($row['name']) > 30) ? mb_substr($row['name'], 0, 30) . '...' : $row['name'];
            ?>

                <!-- new ui because old one got boring -->
                <div class="w3-col l4 m6 s12 w3-margin-bottom">
                    <div class="gr8-theme w3-card-2 w3-light-grey w3-padding creation-card w3-padding w3-round">
                        <a href="/build/<?= $row['id']; ?>" class="creation-link">
                            <img src="<?= !empty($row['screenshot']) ? $row['screenshot'] : '/img/no_image.png'; ?>" loading="lazy" class="cre-image w3-hover-opacity w3-card-2 w3-grey w3-round w3-border w3-border-light-grey creation-thumbnail" alt="<?= htmlspecialchars($row['name']); ?>">
                            <h4 class="creation-title"><?= htmlspecialchars($truncatedName); ?></h4>
                        </a>
                        <div class="creation-meta">
                            <span class="meta-author" data-userid="<?= $row['userid'] ?>">
                                <?php if (!$row['userid']) { ?>
                                    By <i>Deleted User</i>
                                <?php } else { ?>
                                    By <a class="<?= $row['useradmin'] ? 'w3-text-red' : null ?>" href="/@<?= rawurlencode($row['username']); ?>"><img src="<?= $row['profile_picture'] ?? User::gravatar($row['email'], 25) ?>" class="w3-circle" width="20px" height="20px" alt="Profile Picture" loading="lazy" /><i class="fa fa-at" aria-hidden="true"></i><?= $row['username'] ?></a>
                                <?php } ?>
                                on <?= date("d M Y", strtotime($row['date'])) ?>
                            </span>

                            <div class="meta-stats">
                                <span class="views"><i class="fa fa-eye"></i> <?= Numbers::format($row['views']) ?> views</span> •
                                <span class="favs"><i class="fa fa-star"></i> <?= Numbers::format($row['likes']) ?> favorites</span> •
                                <span class="comments"><i class="fa fa-comments"></i> <?= Numbers::format($row['replies']) ?> comments</span>
                                <span class="w3-right"><?= Numbers::filesize($row['size']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

            <?php
                endforeach;
            else :
                echo '<div class="w3-col s12 w3-center w3-padding-large"><b>¯\_(ツ)_/¯ No creations found. Sorry!</b></div>';
            endif;
        ?>

    </div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/const/includes/linkbar.php'; ?>