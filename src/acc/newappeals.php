<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/const/utils/time.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/bbcode.php';
$bbcode = new BBCode;

$frame = '<center><p>sure easter egg why not</p><iframe width="640px" height="480px" src="https://www.youtube-nocookie.com/embed/2dZy3cd9KFY" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></center>';

if(loggedin() && isset($current_user)) {
    if(!$current_user->admin){
        echo $frame;
        exit;
    }
} else {
    echo $frame;
    exit;
}

$conn = Database::get(DB_NAME);

if(isset($_POST['deny']) && $current_user->admin) {
    $appealId = isset($_POST['appealId']) ? $_POST['appealId'] : null;

    $query_appeals = $conn->prepare("SELECT 1 FROM appeals WHERE id = ?");
    $query_appeals->execute([$appealId]);
    $appeal_result = $query_appeals->get_result();

    if($appeal_result->num_rows <= 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Appeal not found in database']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM appeals WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $appealId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Appeal Deleted']);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Appeal could not be deleted Try again Later']);
        exit;
    }
}

if(isset($_POST['accept']) && $current_user->admin) {
    $blacklistId = isset($_POST['blacklistId']) ? $_POST['blacklistId'] : null;
    $appealId = isset($_POST['appealId']) ? $_POST['appealId'] : null;

    $query_appeals = $conn->prepare("SELECT user FROM appeals WHERE id = ?");
    $query_appeals->execute([$appealId]);
    $appeal_result = $query_appeals->get_result();

    if($appeal_result->num_rows <= 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Appeal not found in database']);
        exit;
    }

    $appealUser = $appeal_result->fetch_assoc()['user'];

    $stmt_unblacklist = $conn->prepare("DELETE FROM user_bans WHERE id = ? LIMIT 1");
    $res_unblacklist = $stmt_unblacklist->execute([$blacklistId]);

    $stmt_undel = $conn->prepare("UPDATE users SET deactive = NULL, suspended = 0 WHERE id = ?");
    $res_undel = $stmt_undel->execute([$appealUser]);

    if($res_unblacklist && $res_undel) {
	    $stmt = $conn->prepare("DELETE FROM appeals WHERE id = ? LIMIT 1");
        $stmt->execute([$appealId]);

        echo json_encode(['success' => true, 'message' => 'Unsuspended user']);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'We couldn\'t unsuspend this user']);
        exit;
    }
}

/**
 * Reason enum to text
 */
function reasontotext(string $reason) {
    switch ($reason) {
        case 'violent' :
            $reason = 'Violent or extreme content';
        case 'misinformation':
            $reason = 'Misinformation/disinformation';
            break;
        
        case 'inappropriate':
            $reason = 'Inappropriate content';
            break;

        case 'harrasing-me':
            $reason = 'Harassment';
            break;

        case 'spam':
            $reason = 'Spam';
            break;

        case 'underage':
            $reason = 'User is under the age of 13';
            break;

        case 'copyright':
            $reason = 'Copyrighted content';
            break;

        case 'other':
            $reason = 'Other';
            break;

        default:
            break;
    }

    return $reason;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Suspension Appeals</title>
    <?php include '../header.php' ?>
</head>
<body class="w3-light-blue w3-container">
    <?php
        include('../navbar.php');
        include('panel.php');

        $user = null;
        $appealId = null;

        $query_appeals = $conn->prepare("SELECT id, user, reason FROM appeals");
        $query_appeals->execute();
        $query_appeals->store_result();
        $query_appeals->bind_result($appealId, $user, $reason);

        if($query_appeals->num_rows != 0 && $current_user->admin) {
            echo "<h4>here's some appeals, or something, good boy/girl/thing/goy</h4>";

            while ($query_appeals->fetch()) {
                $query_user = $conn->prepare("SELECT u.username, p.picture FROM users u LEFT JOIN user_profiles p ON p.userid = ? WHERE id = ?");
                $query_user->bind_param("ii", $user, $user);
                $query_user->execute();

                $user_result = $query_user->get_result();
                $user_row = $user_result->fetch_assoc();
                $username = urlencode($user_row['username']);

                $query_blacklist = $conn->prepare("SELECT * FROM user_bans WHERE userid = ? AND (expires IS NULL OR expires >= CURRENT_TIMESTAMP()) AND revoked IS NULL LIMIT 1");
                $query_blacklist->bind_param("i", $user);
                $query_blacklist->execute();

                $blacklist_result = $query_blacklist->get_result();
                $blacklist_row = $blacklist_result->fetch_assoc();

                if($blacklist_result->num_rows <= 0) {
                    continue;
                }

                $query_user->free_result();
            ?>

                <article class='w3-card-2 gr8-theme w3-light-grey w3-padding-small w3-round-small w3-margin-bottom'>
                    <header>
                        <h4>
                            <img src='<?php echo $user_row['picture'] ?? '/img/no_image.png' ?>' width="35px" height="35px" style='border-radius: 50%;'>
                            <a href='/@<?php echo $username ?>'><?php echo htmlspecialchars($username ?? '[deleted]') ?></a>
                        </h4>
                    </header>
                    <h4>Reason user wants to be unsuspended:<br /><?php echo $bbcode->toHTML($reason, true, true) ?></h4>
                    <h4>Suspended for <?php echo reasontotext($blacklist_row['reason']) ?></h4>
                    <h4>Admin note:<br /><?php echo $blacklist_row['note'] ?? '[none]' ?></h4>
                    <form method='post' action='newappeals.php'>
                        <input type='hidden' value='<?php echo $blacklist_row['id'] ?? null ?>' name="blacklistId" />
                        <input type='hidden' value='<?php echo $appealId ?? null ?>' name="appealId" />
                        <input type='submit' value='Delete appeal' name='deny' class='w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink'>
                        <input type='submit' value='Unsuspend user' name='accept' class='w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo'>
                    </form>
                </article>

            <?php
                $query_user->close();
            }
            $query_appeals->free_result();
            $query_appeals->close();
        } else {
            echo "<b>No ban appeals. You're all caught up!</b><br />";
        }
        $conn->close();

        include '../linkbar.php' 
    ?>
</body>
</html>