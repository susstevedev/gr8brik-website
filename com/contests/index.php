<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/profile.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/build.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/contest.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Contest</title>
    <?php include '../../header.php' ?>
</head>

<body class="w3-light-blue w3-container">

    <?php 
        include '../../navbar.php';

        $data = fetch_contests($_GET['id']);
    ?>

    <?php if(!empty($data)) { ?>
        <?php
            $end_time = $data['end_date'];
            $current_time = time(); 
        ?>

        <h2 data-id="<?php echo $data['id'] ?>"><?php echo $data['title'] ?></h2><hr />
        <pre><?php echo $data['about'] ?></pre>

        <?php
            if ($end_time < $current_time && $end_time != 0) {
                echo "<b>Ended " . time_ago($end_time) . "</b>";
            } elseif($end_time >= $current_time && $end_time != 0) {
                echo "<b>Ends " . time_ago($end_time) . "</b>";
            } else {
                echo "<b>Ended a white ago. Infact, so long ago we can't display how long ago it ended!</b>";
            }
        ?>

        <hr />
        <h4>Submitted creations</h4>

        <table class='w3-table-all' style='color:black;'>
        <?php
            $users = json_decode($data['submitted_ids'], true);
            echo '<ul>';
            reset($users);
            while (($creation_id = current($users)) !== false) {
                $user_id = key($users);
                $user_data = json_decode(fetch_profile($user_id, $users_row['id'], $users_row, $_SESSION['csrf']), true);
                $creation_data = json_decode(fetch_build($creation_id, $_SESSION['csrf']), true);

                if(isset($user_data['userid']) && isset($creation_data['userid'])) {
                    //echo "<li>User: <a href='/user/" . $user_id . "'>" . $user_data['username'] . "</a><br />Creation: <a href='/build/" . $creation_id . "'>" . $creation_data['name'] . "</a></li>";
                    echo "<div class='w3-display-container w3-left w3-padding'>";
                    echo "<a href='/build/" . $creation_id . "'><img src='/cre/" . $creation_data['screenshot'] . "' width='320' height='240' loading='lazy' class='gr8-theme w3-card-2 w3-hover-shadow w3-border w3-border-white'></a>";
                    echo "<span class='gr8-theme w3-large w3-display-middle w3-card-2 w3-light-grey w3-padding-small'>" . $creation_data['name'] . '</span>';
                    echo "<span class='gr8-theme w3-display-bottommiddle w3-card-2 w3-light-grey w3-padding-small'>" . $creation_data['views'] . ' views - By ';
                    echo "<a href='/user/" . $user_id . "'>" . $user_data['username'] . "</a></span></div>";
                }
                next($users);
            }
            echo '</ul>';
        ?>
        </table><br /><br />
    <?php } else { ?>
        <h4>No contest lives here.</h4>
    <?php } ?>

    <?php include '../../linkbar.php' ?>
</body>
</html>