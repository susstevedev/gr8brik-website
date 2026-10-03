<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/profile.php';

if(isset($_GET['name'])) {
    $use_username = true;
} else if(isset($_GET['id'])) {
    $use_username = false;
} else {
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';

$data = fetch_profile($_GET['id'] ?? urldecode($_GET['name']), $_SESSION['csrf'], $use_username);
$userid = (loggedin() && isset($current_user)) ? $current_user->id : null;
$user_admin = (loggedin() && isset($current_user) && $current_user->admin) ? true : false;

if(isset($data) && !$data['success'] && !empty($data['message'])) {
    $error = $data['message'];
    $error_title = $data['title'] ?? null;
    $error_picture = $data['picture'] ?? null;
    $error_code = $data['code'] ?? null;
}

if(isset($data) && isset($data['userid'])) {
	$_GET['id'] = $data['userid'];
}

$interactions = new UserInteractions();

if (isset($_POST['follow'])) {
    $profile_id = (int)$_GET['id'];
    $p_data = $interactions->followUser($profile_id);
    $error_title = $p_data['title'] ?? 'Follow';

    if($data['success']) {
        header("HTTP/1.0 200 OK");
        $message = $p_data['message'] ?? 'Successful';
        header('refresh:3');
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        $error = $p_data['message'] ?? 'An error has occured';
        header('refresh:3');
    }
}

if(isset($_POST['unfollow'])) {
    $profile_id = (int)$_GET['id'];
    $p_data = $interactions->unfollowUser($profile_id);
    $error_title = $p_data['title'] ?? 'Unfollow';

    if($p_data['success']) {
        header("HTTP/1.0 200 OK");
        $message = $p_data['message'] ?? 'Successful';
        header('refresh:3');
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        $error = $p_data['message'] ?? 'An error has occured';
        header('refresh:3');
    }
}

if (isset($_POST['block'])) {
    $profile_id = (int)$_GET['id'];
    $p_data = $interactions->blockUser($profile_id);
    $error_title = $p_data['title'] ?? 'Block and unfollow';

    if($p_data['success']) {
        header("HTTP/1.0 200 OK");
        $message = $p_data['message'] ?? 'Successful';
        header('refresh:3');
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        $error = $p_data['message'] ?? 'An error has occured';
        header('refresh:3');
    }
}

if(isset($_POST['unblock'])) {
    $profile_id = (int)$_GET['id'];
    $p_data = $interactions->unblockUser($profile_id);
    $error_title = $p_data['title'] ?? 'Unblock';

    if($p_data['success']) {
        header("HTTP/1.0 200 OK");
        $message = $p_data['message'] ?? 'Successful';
        header('refresh:3');
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        $error = $p_data['message'] ?? 'An error has occured';
        header('refresh:3');
    }
}

$admin = new UserAdmin();

if(isset($_POST['warn'])) {
    $reason = isset($_POST['reason']) ? $_POST['reason'] : null;
    $profile_id = (int)$_GET['id'];
    $p_data = $admin->warnUser($profile_id, $reason);
    $error_title = $p_data['title'] ?? 'Warn';

    if($p_data['success']) {
        header("HTTP/1.0 200 OK");
        $message = $p_data['message'] ?? 'Successful';
        header('refresh:3');
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        $error = $p_data['message'] ?? 'An error has occured';
        header('refresh:3');
    }
}

/*if(isset($_POST['delete'])) {
    $error_title = 'Removing user';

    if(!loggedin()) {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "Please login to remove this user";
    }

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        exit('Invalid user ID!');
    }

    if(User::isDeleted((int)$_GET['id'])) {
        exit('No user found');
    }

    $ignore = isset($_POST['ignore']) ? $_POST['ignore'] : 0;
    $use_email = isset($_POST['use_email']) ? $_POST['use_email'] : 0;

    if (!empty($_POST['until']) && !$ignore) {
        $date = new DateTime($_POST['until']);

        if ($date) {
            $until = $date->format('Y-m-d H:i:s');
        } else {
            header("HTTP/1.0 500 Internal Server Error");
            $error = "Invalid ban date";
            header('refresh:3');
        }
    } else {
        $until = null;
    }

    $profile_id = (int)$_GET['id'];
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : 'banned by admin request';
    $rand = bin2hex(random_bytes(32));

    if($use_email) {
        $identifier = hash('sha256', strtolower(trim($data['email'])));
        $type = 'email';
    } else {
        $identifier = $profile_id;
        $type = 'userid';
    }

    if($current_user->admin != false) {
        $sql_block = "INSERT IGNORE INTO blacklist (value, type, reason, ignore_at) VALUES (?, ?, ?, ?)";
        $sql_delete = "UPDATE php_sessions SET active = 0 WHERE userid = ?";
        $sql_delete_2 = "UPDATE sessions SET timestamp = 0 WHERE user = ?";

        $stmt_block = $conn->prepare($sql_block);
        $stmt_block->bind_param("ssss", $identifier, $type, $reason, $until);
        $result2 = $stmt_block->execute();
        $stmt_block->close();

        if(!$result2) {
            header("HTTP/1.0 500 Internal Server Error");
            $error = "Couldn't ban this user at this time";
            header('refresh:3');
        } else {
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("i", $profile_id);
            $result3 = $stmt_delete->execute();
            $stmt_delete->close();

            $stmt_delete = $conn->prepare($sql_delete_2);
            $stmt_delete->bind_param("i", $profile_id);
            $result4 = $stmt_delete->execute();
            $stmt_delete->close();

            if(!$result3 || !$result4) {
                header("HTTP/1.0 500 Internal Server Error");
                $error = "Couldn't log this user out when banning the account";
                header('refresh:3');
            } else {
                header('refresh:1');
                exit;
            }
        }
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "User is not an administrator!";
        header('refresh:3');
    }
}*/

if(isset($_POST['delete'])) {
    $profile_id = (int)$_GET['id'];

    $ignore = filter_has_var(INPUT_POST,'ignore') ? true : false;
    $use_email = filter_has_var(INPUT_POST,'use_email') ? true : false;
    $until = isset($_POST['until']) ? $_POST['until'] : null;
    $reason = isset($_POST['reason']) ? $_POST['reason'] : null;
    $email = isset($data['email']) ? $data['email'] : null;

    $p_data = $admin->removeUser($profile_id, $ignore, $use_email, $until, $reason, $email);
    $error_title = $p_data['title'] ?? 'Warn';

    if($p_data['success']) {
        header("HTTP/1.0 200 OK");
        $message = $p_data['message'] ?? 'Successful';
        header('refresh:3');
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        $error = $p_data['message'] ?? 'An error has occured';
        header('refresh:3');
    }
}

if(isset($_POST['unban-submit'])) {
    $profile_id = (int)$_GET['id'];
    $email = isset($data['email']) ? $data['email'] : null;

    $p_data = $admin->unblacklistUser($profile_id, $email);
    $error_title = $p_data['title'] ?? 'Unban';

    if($p_data['success']) {
        header("HTTP/1.0 200 OK");
        $message = $p_data['message'] ?? 'Successful';
        header('refresh:3');
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        $error = $p_data['message'] ?? 'An error has occured';
        header('refresh:3');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo isset($data['username']) ? $data['username'] . '\'s profile' : 'Unknown profile' ?></title>
    <?php include 'header.php' ?>

    <script type="text/javascript">
        $(document).ready(function() {
            window.userid = '<?php echo (int)$_GET['id'] ?>';
        });
    </script>
    <script type="text/javascript" src="/lib/profile.js"></script>
</head>
<body class="w3-container">

    <?php include 'navbar.php' ?>

    <?php if(isset($error) && isset($error_title)) { ?>
        <div class="message-wrapper gr8-theme w3-light-grey w3-card-2 w3-padding w3-round-small w3-center">
            <div class="message-title"><h4><?php echo $error_title ?></h4></div>
            <div class="message"><p><?php echo $error ?></p></div>

            <?php if(isset($error_code) && $error_code === 'profile_private' && loggedin()) {?>
                <form id="followUser" action="" method="post"></form>
                <input id="button-follow" name="follow" form="followUser" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo" type="submit" value="Follow user">
            <?php } ?>

            <?php if(isset($error_code) && $error_code === 'profile_private' && !loggedin()) {?>
                <a href="/acc/login" id="button-follow" name="follow" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Login to follow user</a>
            <?php } ?>

            <?php if(isset($error_code) && $error_code === 'account_banned' && loggedin() && $user_admin) {?>
                <form id="unbanUser" action="" method="post"></form>
                <input id="unban-submit" name="unban-submit" form="unbanUser" class="w3-btn w3-red w3-hover-opacity w3-round-small w3-border w3-border-pink" type="submit" value="Unban user">
            <?php } ?>
        </div>
        <?php exit; ?>
    <?php } ?>

    <?php if(isset($message)) { ?>
        <div class="message w3-padding w3-round w3-card-2 w3-light-grey w3-bottom"><?php echo $message ?></div><br /><br />
    <?php } ?>

    <div class="w3-navbar w3-top w3-row w3-margin-top w3-center" style="flex-direction:row;">
        <a class='w3-button w3-light-grey w3-col m2 w3-hover-blue w3-border w3-border-grey w3-padding-small w3-card-2' onclick="openTab('creationstab')">Creations</a>
        <a class='w3-button w3-light-grey w3-col m2 w3-hover-blue w3-border w3-border-grey w3-padding-small w3-card-2' onclick="openTab('poststab')">Posts</a>
        <a class='w3-button w3-light-grey w3-col m2 w3-hover-blue w3-border w3-border-grey w3-padding-small w3-card-2' onclick="openTab('commentstab')">Comments</a>
        <a class='w3-button w3-light-grey w3-col m2 w3-hover-blue w3-border w3-border-grey w3-padding-small w3-card-2' onclick="openTab('likestab')">Favorites</a>
    </div><br /><br />

    <article id="user-card" class="gr8-theme w3-light-grey w3-card-2 w3-padding w3-round">
        <!-- banners are on life support -->
        <?php if(file_exists("acc/users/banners/" . htmlspecialchars($_GET['id']) . "..jpg")) { ?>
            <div>
                <span data-testid="user-profile-card-banner_image" id="banner"><img src="/acc/users/banners/<?php echo htmlspecialchars($_GET['id']) ?>..jpg" /></span>
            </div>
        <?php } ?>

        <span class="w3-large">
            <img id="picture" width="75px" height="75px" class="w3-round" src="<?php echo $data['picture'] ?>" />

            <?php if($data['admin']) { ?>
                <span id="username" class="w3-padding-small w3-xlarge w3-text-red"><?php echo $data['username'] ?></span>
            <?php } else { ?>
                <span id="username" class="w3-padding-small w3-xlarge"><?php echo $data['username'] ?></span>
            <?php } ?>

            <?php if($data['is_private']) { ?>
                <i class='fa fa-lock w3-xlarge w3-text-yellow' title="This profile has been privated. You can only view public contibutions if you follow them." aria-hidden='true'></i>
            <?php } ?>

            <span id="stats" class="w3-padding-small">
                <b id="model-count"><?php echo number_format($data['stats']['creation_count']) ?></b> creations -
                <b id="follower-count"><?php echo number_format($data['stats']['followers']) ?></b> followers -
                <b id="following-count"><?php echo number_format($data['stats']['following']) ?></b> following
            </span><br />
        </span>

        <div><p id="description"><?php echo $data['description'] ?></p></div>

        <span id="stats-other">
            <b><?php echo number_format($data['stats']['forum_posts']) ?></b> forum posts -
            <b><?php echo number_format($data['stats']['views']) ?></b> views -
            <b><?php echo number_format($data['stats']['likes']) ?></b> likes
        </span><br />

        <span id="joined-wrapper">
            Became a member <?php echo time_ago($data['age']) ?>
        </span>
        
        <?php if(!empty($data['twitter'])) { ?>
            <b>-</b>
            <span id="twitter-wrapper">
                <a id="twitter-link" href="https://twitter.com/<?php echo $data['twitter'] ?>" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-twitter" viewBox="0 0 16 16">
                        <path d="M5.026 15c6.038 0 9.341-5.003 9.341-9.334q.002-.211-.006-.422A6.7 6.7 0 0 0 16 3.542a6.7 6.7 0 0 1-1.889.518 3.3 3.3 0 0 0 1.447-1.817 6.5 6.5 0 0 1-2.087.793A3.286 3.286 0 0 0 7.875 6.03a9.32 9.32 0 0 1-6.767-3.429 3.29 3.29 0 0 0 1.018 4.382A3.3 3.3 0 0 1 .64 6.575v.045a3.29 3.29 0 0 0 2.632 3.218 3.2 3.2 0 0 1-.865.115 3 3 0 0 1-.614-.057 3.28 3.28 0 0 0 3.067 2.277A6.6 6.6 0 0 1 .78 13.58a6 6 0 0 1-.78-.045A9.34 9.34 0 0 0 5.026 15"/>
                    </svg>
                    <?php echo $data['twitter'] ?>
                </a>
            </span>
        <?php } ?>
        
        <?php if(!empty($data['bsky'])) { ?>
            <b>-</b>
            <span id="bsky-wrapper">
                <a id="bsky-link" href="https://bsky.app/profile/<?php echo $data['bsky'] ?>" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bluesky" viewBox="0 0 16 16">
  						<path d="M3.468 1.948C5.303 3.325 7.276 6.118 8 7.616c.725-1.498 2.698-4.29 4.532-5.668C13.855.955 16 .186 16 2.632c0 .489-.28 4.105-.444 4.692-.572 2.04-2.653 2.561-4.504 2.246 3.236.551 4.06 2.375 2.281 4.2-3.376 3.464-4.852-.87-5.23-1.98-.07-.204-.103-.3-.103-.218 0-.081-.033.014-.102.218-.379 1.11-1.855 5.444-5.231 1.98-1.778-1.825-.955-3.65 2.28-4.2-1.85.315-3.932-.205-4.503-2.246C.28 6.737 0 3.12 0 2.632 0 .186 2.145.955 3.468 1.948"/>
					</svg>
                    <?php echo $data['bsky'] ?>
                </a>
            </span>
        <?php } ?>

        <b>-</b>
        <span id="followedby-wrapper"></span><br />

        <?php if(loggedin()) { ?>
            <?php if($userid != trim($_GET['id'])) { ?>
            <span id="action-buttons">
                <?php if($data['is_following'] === true) { ?>
                    <button onclick='document.getElementById("modal-unfollow").style.display="block"' name="unfollow" class="button-unfollow w3-btn w3-red w3-hover-opacity w3-round-small w3-border w3-border-pink" />
                        Unfollow
                    </button>&nbsp;
                <?php } elseif($data['is_following'] === false) { ?>
                    <input id="button-follow" form="followUser" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo" type="submit" value="Follow" name="follow">&nbsp;
                <?php } ?>

                <div class="w3-dropdown-click">
                    <button onclick="dropdown('user-interactions')" class="gr8-theme w3-btn w3-hover-opacity w3-round-small w3-border w3-border-gray">More...</button>
                    <div id="user-interactions" class="w3-dropdown-content gr8-theme w3-bar-block w3-border w3-border-gray w3-round-small">
                        <?php if($data['is_blocking'] === false) { ?>
                            <button onclick='document.getElementById("modal-block").style.display="block"' name="block" class="w3-bar-item w3-button" />
                                Block
                            </button>
                        <?php } elseif($data['is_blocking'] === true) { ?>
                            <input id="button-unblock" form="unblockUser" class="w3-bar-item w3-button" type="submit" value="Unblock" name="unblock">
                        <?php } ?>

                        <button id="button-report-user" onclick='document.getElementById("modal-report").style.display="block"' name="report" class="w3-bar-item w3-button" />
                            Report
                        </button>

                        <?php if($user_admin != false) { ?>
        					<button id="button-warn-user" onclick='document.getElementById("modal-warn").style.display="block"' name="warn" class="w3-bar-item w3-button" />
                                Warn
                            </button>
                            <button id="button-delete-user" onclick='document.getElementById("modal-delete").style.display="block"' name="delete" class="w3-bar-item w3-button" />
                                Ban
                            </button>
                        <?php } ?>
                    </div>
                </div>

                <form id="followUser" action="" method="post"></form>
                <form id="unblockUser" action="" method="post"></form>
            </span>
            <?php } else { ?>
                <a href="/acc/index">Edit Profile</a>&nbsp;
            <?php } ?>
        <?php } ?>

    </span></article>
        
    <div id="modal-unfollow" class="w3-modal">
		<div class="w3-modal-content w3-card-2 w3-light-grey w3-center">
			<div class="w3-container">
				<span onclick="document.getElementById('modal-unfollow').style.display='none'" class="w3-button w3-large w3-red w3-hover-white w3-display-topright">&times;</span>
				    <form method='post' action=''>
					<h2>Are you sure you want to unfollow this user?</h2>
					<span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('modal-unfollow').style.display='none'">No</span> 
					<input type="submit" value="Yes" name="unfollow" class="w3-btn w3-large w3-white w3-hover-red">
				</form>
			</div>
		</div>
	</div>

    <div id="modal-report" class="w3-modal" style="z-index: 999999">
        <div class="w3-modal-content gr8-theme w3-card-2 w3-light-grey w3-center">
            <div class="w3-container">
                <span onclick="$('#reportForm')[0].reset();$('#modal-report').hide();" class="w3-button w3-large w3-red w3-hover-white w3-display-topright">&times;</span>
                <form id="reportForm">
                    <h2>Why do you want to report this user?</h2>
                    <b>You can only report content when it violates our <a href="/rules?src=creation" target="_blank"><i class="fa fa-external-link" aria-hidden="true"></i>rules</a>.</b><br />
                    <input type="radio" name="reason" value="violent" class="w3-check"> <label>Violent or extreme content</label><br />
                    <input type="radio" name="reason" value="misinformation" class="w3-check"> <label>Misinformation/disinformation</label><br />
                    <input type="radio" name="reason" value="inappropriate" class="w3-check"> <label>Inappropriate content</label><br />
                    <input type="radio" name="reason" value="harrasing-me" class="w3-check"> <label>Harassing me or others</label><br />
                    <input type="radio" name="reason" value="spam" class="w3-check"> <label>Spam</label><br />
                    <input type="radio" name="reason" value="underage" class="w3-check"> <label>User is under 13</label><br />
                    <input type="radio" name="reason" value="copyright" class="w3-check"> <label>Copyrighted content</label><br />
                    <input type="radio" name="reason" value="other" class="w3-check" id="otherReasonToggle"> <label>Something else</label><br /><br />

                    <textarea class="w3-input w3-card-2 w3-hover-shadow w3-mobile w3-round" name="other" id="otherReason" placeholder="Explain more..." rows="4"></textarea><br />

                    <span class="w3-btn w3-large w3-white w3-hover-blue w3-round-small" onclick="$('#reportForm')[0].reset();$('#modal-report').hide();">Close</span>
                    <button type="submit" class="w3-btn w3-large w3-white w3-hover-red w3-round-small">Report</button>
                </form>
            </div>
        </div>
    </div>

    <div id="modal-block" class="w3-modal">
		<div class="w3-modal-content w3-card-2 w3-light-grey w3-center">
			<div class="w3-container">
				<span onclick="document.getElementById('modal-block').style.display='none'" class="w3-button w3-large w3-red w3-hover-white w3-display-topright">&times;</span>
				    <form method='post' action=''>
					<h2>Are you sure you want to block this user?</h2>
					<span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('modal-block').style.display='none'">No</span> 
					<input type="submit" value="Yes" name="block" class="w3-btn w3-large w3-white w3-hover-red">
				</form>
			</div>
		</div>
	</div>

    <?php if(loggedin() && $user_admin != false) { ?>
        <div id="modal-delete" class="w3-modal">
            <div class="gr8-theme w3-modal-content w3-card-2 w3-light-grey w3-center">
                <div class="w3-container">
                    <span onclick="document.getElementById('modal-delete').style.display='none'" class="w3-button w3-large w3-red w3-hover-white w3-display-topright">&times;</span>
                    <form method='post' action=''>
                        <h2>Are you sure you want to ban this user?</h2>

                        <label for="until">Ban until:</label>
                        <input type="date" class="w3-round w3-hover-opacity" id="until" name="until" min="<?php echo date('Y-m-d') ?>" max="2056-12-31"><br />

                        <input type="checkbox" id="ignore" name="ignore">
                        <label for="ignore">Permanent ban</label><br />

                        <input type="checkbox" id="use_email" name="use_email">
                        <label for="use_email">Ban using email for presistance if the user deletes the account</label><br />

                        <textarea name="reason" placeholder="Moderator note about this ban" class="w3-input w3-border w3-mobile" rows="4" cols="50" required></textarea><br />

                        <span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('modal-delete').style.display='none'">No</span>
                        <input type="submit" value="Yes" name="delete" class="w3-btn w3-large w3-white w3-hover-red">
                    </form>
                </div>
            </div>
        </div>

        <div id="modal-warn" class="w3-modal">
            <div class="gr8-theme w3-modal-content w3-card-2 w3-light-grey w3-round w3-padding w3-center">
                <div class="w3-container">
                    <span onclick='document.getElementById("modal-warn").style.display="none"' class="w3-closebtn w3-red w3-hover-white w3-padding w3-display-topright">&times;</span><form method="post" action="">
                        <h2>Are you sure you want to warn this user?</h2>
                        <textarea name="reason" placeholder="Moderator note about this warning (required)" class="w3-input w3-border w3-mobile" rows="4" cols="50" required></textarea><br />
                        <span name="close" class="w3-btn w3-large w3-white w3-hover-blue w3-round" onclick='document.getElementById("modal-warn").style.display="none"'>No</span>
                        <input type="submit" value="Yes" name="warn" class="w3-btn w3-large w3-white w3-hover-red w3-round">
                    </form>
                </div>
            </div>
        </div><br />
    <?php } ?>

    <span id="data-user-actions" class="w3-panel">
        <div class="tab" id="creationstab">
            <a href="#creations" id="creations"></a>
            <div class="w3-row">
                <template id="gr8-creation-template">
                    <div class="w3-col l4 m6 s12 w3-padding-small">
                        <div class="gr8-theme creation w3-card-2 w3-light-grey w3-padding creation-card">
                            <a href="/build/" class="creation-link">
                                <img src="" loading="lazy" class="cre-image w3-hover-opacity w3-card-2 w3-grey creation-thumbnail">
                                <h4 class="creation-title"></h4>
                            </a>
                            <div class="creation-meta">
                                <span class="meta-author">
                                    By <b><a href=""></a></b> <span></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <br /><button class="back-button w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Back</button>
            <button class="foward-button w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Foward</button>
        </div>

        <div class="tab" id="poststab">
            <a href="#posts" id="posts"></a>
            <div class="w3-row">
                <template id="gr8-posts-template">
                    <div class='posts w3-display-container w3-left w3-padding' width="50%">
                        <div class='w3-card-2 gr8-theme w3-light-grey w3-padding-small'>
                            <a class='link-name' href=''>
                                <h4 class='text'></h4>
                            </a>
                            <span><a class='user' href='/user/'></a> replied to <a class='title' href=''></a> <span class='time'></span></span>
                        </div>
                    </div>
                </template>
            </div>
            <button class="back-button w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Back</button>
            <button class="foward-button w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Foward</button>
        </div>

        <div class="tab" id="commentstab">
            <a href="#comments" id="comments"></a>
            <div class="w3-row">
                <template id="gr8-comment-template">
                    <div class='comment w3-display-container w3-left w3-padding' width="50%">
                        <div class='w3-card-2 gr8-theme w3-light-grey w3-padding-small'>
                            <a class='link-name' href=''>
                                <h4 class='text'></h4>
                            </a>
                            <span><a class='user' href='/user/'></a> replied to <a class='title' href=''></a> <span class='time'></span></span>
                        </div>
                    </div>
                </template>
            </div>
            <button class="back-button w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Back</button>
            <button class="foward-button w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Foward</button>
        </div>
        
        <div class="tab" id="likestab">
		    <a href="#likes" id="likes"></a>
			<div class="w3-row">
                <template id="gr8-likes-template">
                    <div class="w3-col l4 m6 s12 w3-padding-small">
                        <div class="gr8-theme liked w3-card-2 w3-light-grey w3-padding creation-card">
                            <a href="/build/" class="creation-link">
                                <img src="" loading="lazy" class="cre-image w3-hover-opacity w3-card-2 w3-grey creation-thumbnail">
                                <h4 class="creation-title"></h4>
                            </a>
                            <div class="creation-meta">
                                <span class="meta-author">
                                    By <b><a href=""></a></b> <span></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
		    </div><br />
            <button class="back-button w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Back</button>
            <button class="foward-button w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Foward</button>
        </div>
    </span>

    <?php include('linkbar.php') ?>

</div>

</body>
</html>