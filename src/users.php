<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/const/utils/time.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/const/toMarkdown.php';

    $pageTitle = 'Users';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/const/includes/header.php';
    include 'navbar.php';

    $sort_options = [
        'desc' => ['sql' => 'ORDER BY u.id DESC', 'label' => 'Newest Registered'],
        'username_a' => ['sql' => 'u ORDER BY u.username ASC', 'label' => 'A-Z'],
        'username_d' => ['sql' => 'ORDER BY u.username DESC', 'label' => 'Z-A'],
        'age' => ['sql' => 'ORDER BY u.age DESC', 'label' => 'Creation Date'],
        'admin' => ['sql' => 'WHERE u.admin = 1 ORDER BY u.age DESC', 'label' => 'Moderator Accounts']
    ];

    $conn = Database::get(DB_NAME);
                                                                                                                            
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $page = ($page < 1) ? $page : 1;

    $limit = 8;
    $offset = ($page - 1) * $limit;
    $pDown = $page - 1;
    $pUp = $page + 1;

    $count_result = $conn->query("SELECT COUNT(*) as total_users FROM users");
    $count_row = $count_result->fetch_assoc()['total_users'] ?? 0;
    $total_pages = ceil($count_row / $limit);

    $search = false;
    if(isset($_GET['q']) && $_GET['q']) {
        $search = "%" . $conn->real_escape_string($_GET['q']) . "%";
    }
?>

<div class="w3-container w3-center">
    <h1>All Users</h1>

    <div class="w3-card-2 w3-light-grey gr8-theme w3-padding w3-round w3-center">
        <h5>If you wish us to remove any personal details we hold about you, please email us at <i class="fa fa-envelope"></i><a href="mailto:<?php echo DB_MAIL ?>"><?php echo DB_MAIL ?></a></h5>
    </div><br />
</div>

<div class="w3-ul w3-container w3-card-2 gr8-theme w3-light-grey w3-padding w3-round">
    <form method="get">
        <label for="sort"><b>Sort options:</b></label>
        <select class="w3-padding w3-round w3-border" name="sort" id="sort">
            <option value="desc" selected><?= $sort_options['desc']['label'] ?></option>
            <option value="username_a"><?= $sort_options['username_a']['label'] ?></option>
            <option value="username_d"><?= $sort_options['username_d']['label'] ?></option>
            <option value="age"><?= $sort_options['age']['label'] ?></option>
            <option value="admin"><?= $sort_options['admin']['label'] ?></option>
        </select>
        <input name="q" class="w3-padding w3-round w3-border" type="text" value="<?php echo isset($_GET['q']) ? $_GET['q'] : null ?>" id="search-input-2" placeholder="Search...">
        <input class="w3-btn w3-blue w3-hover-opacity w3-round w3-border w3-border-indigo" type="submit" value="Apply">
    </form>

    <?php
        if($search) {
            $sql = "WHERE u.username LIKE '$search' ORDER BY u.id DESC";
        } else if (isset($_GET['sort'])) {
            if (array_key_exists($_GET['sort'], $sort_options)) {
                $sql = $sort_options[$_GET['sort']]['sql'];
                echo '<div class="w3-col s12"><p>Sorting by <b>' . $sort_options[$_GET['sort']]['label'] . '</b></p></div>';
            }
        }

        if(!isset($sql)) {
            $sql = "ORDER BY id DESC";
        }

        if ($page > 0) {
            $stmt = $conn->prepare("SELECT u.id, u.username, u.admin, u.description, u.age, p.picture FROM users u LEFT JOIN user_profiles p ON u.id = p.userid {$sql} LIMIT {$limit} OFFSET {$offset}");
            $stmt->execute();
            $result = $stmt->get_result();
        }

        if ($result->num_rows > 0) {
            $users = $result->fetch_all(MYSQLI_ASSOC);
        ?>

        <h3><?php echo $count_row ?> total registered users, <?php echo $total_pages ?> pages, on page <?php echo $page ?></h3>
        <a class="w3-btn w3-blue w3-hover-opacity w3-round w3-border w3-border-indigo" href="?page=<?php echo $pDown ?>">Back</a>
        <a class="w3-btn w3-blue w3-hover-opacity w3-round w3-border w3-border-indigo" href="?page=<?php echo $pUp ?>">Next</a>

        <div class='w3-row'>

        <?php
            foreach ($users as $user) :
                $userid = $user['id'];
        ?>

                <div class='w3-col w3-section w3-padding w3-card-2 w3-round w3-border w3-border-grey w3-row'>
                    <?php if(isset($user['picture']) && $user['picture'] !== '/img/no_image.png') : ?>
                        <a href='/@<?= urlencode($user['username']) ?>'>
                            <div class='w3-col m1'><img width='100' height='100' alt="Profile Image for <?= urlencode($user['username']) ?>" class='w3-circle' src='<?= $user['picture'] ?>'></div>
                        </a>
                    <?php endif; ?>

                    <div class='w3-rest'>
                        <a href='/@<?= urlencode($user['username']) ?>'>
                            <?php if($user['admin'] === 1) : ?>
                                <h4 class='w3-text-red'>
                            <?php else : ?>
                                <h4>
                            <?php endif; ?>

                            <i class='fa fa-at' aria-hidden='true'></i>
                            <?= htmlspecialchars($user['username']) ?>
                            </h4>
                        </a>

                    <?php if(trim($id) === trim($userid)) : ?>
                        <a href="/acc/index.php"><i class="fa fa-pencil" aria-hidden="true"></i>Edit my profile</a>
                    <?php endif; ?>

                    <?= Formatter::render($user['description']) ?>
                    <p>Registered <?= time_ago($user['age']) ?></p>
                </div>
            </div><br />

        <?php endforeach; } ?>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/const/includes/linkbar.php' ?>