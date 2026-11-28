<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/account_settings.php';

if (isset($_GET['reactive']) && isset($_GET['token'])) {
    $sessionid_raw = $_GET['token'];
    $sessionid = hash('sha256', $sessionid_raw);
    $conn = Database::get(DB_NAME);

    if(isset($sessionid) && $sessionid) {
        $stmt = $conn->prepare("SELECT user FROM sessions WHERE id = ? LIMIT 1");
        $stmt->bind_param("s", $sessionid);
        $stmt->execute();

        $res = $stmt->get_result();
        $id = (int)$res->fetch_assoc()['user'];

        if(isset($id) && $id) {
            $stmt_select = $conn->prepare("SELECT id FROM users WHERE id = ? AND (deactive IS NOT NULL OR suspended != 0) LIMIT 1");
            $stmt_select->bind_param("i", $id);
            $stmt_select->execute();

            $res = $stmt_select->get_result();
            $id = (int)$res->fetch_assoc()['id'];

            if(isset($id) && $id) {
                $banned = User::isBannedByID($id);

                if(!$banned) {
                    $stmt_set = $conn->prepare("UPDATE users SET deactive = NULL, suspended = 0 WHERE id = ? LIMIT 1");
                    $stmt_set->bind_param("i", $id);

                    if ($stmt_set->execute()) {
                        session_regenerate_id(true);
                        $_SESSION['userid'] = $id;
                        $_SESSION['tokenid'] = $sessionid_raw;

                        echo json_encode(['success' => true]);
                        header('Location:login.php');
                        exit;
                    }
                }
            }
        }
    }

    echo json_encode(['success' => false, 'error' => 'Couldn\'t restore your account. Please try again later.']);
    exit;
}

if (!loggedin() || !isset($current_user)) {
    header('Location:login.php');
    exit;
}
?>

<?php
    $pageTitle = "Account Settings";
    $pageCanonical = '/acc/';
    $pageScripts = ['/lib/account_settings.js'];
    require_once $_SERVER['DOCUMENT_ROOT'] . '/const/includes/header.php';

    include '../navbar.php';
    include 'panel.php';

    $utils = new ScreenNameUtils();
    $combinedString = $utils->generateRandomScreenName();

    if($current_user->banner && strpos($current_user->banner, '/acc/users/banners/') !== false && file_exists($_SERVER['DOCUMENT_ROOT'] . $current_user->banner)) {
        $hasbanner = true;
        $userBanner = $current_user->banner;
    } else {
        $hasbanner = false;
        $userBanner = "/img/no_image.png";
    }

    if($current_user->picture && strpos($current_user->picture, '/acc/users/pfps/') !== false && file_exists($_SERVER['DOCUMENT_ROOT'] . $current_user->picture)) {
        $haspfp = true;
    } else {
        $haspfp = false;
    }
?>

    <div id="deactive" class="w3-modal">
        <div class="w3-modal-content w3-card-2 w3-light-grey w3-center w3-animate-bottom">
            <div class="w3-container">
                <span onclick="document.getElementById('deactive').style.display='none'" class="w3-button w3-red w3-hover-white w3-padding w3-display-topright">&times;</span>
                <form method='post' action='/ajax/account_settings.php'>
                    <h2>Are you sure you want to delete your account?</h2>
                    <p><input type="password" name="password" placeholder="Password" class="w3-input w3-border w3-mobile" required /></p>
                    <p>You can restore your account up until 14 days after you start the process. After those 14 days, all of your account data will be anonymized or deleted.</p>
                    <span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('deactive').style.display='none'">No</span>&nbsp;
                    <input type="submit" value="Yes" name="deactive_account" class="w3-btn w3-large w3-white w3-hover-red">
                </form>
            </div>
        </div>
    </div>

    <style>
        .file-pfp {
            background-image: url('<?php echo $current_user->picture ?: "../acc/users/pfps/" . $current_user->id . ".jpg?" ?>');
        }

        .file-banner {
            background-image: url('<?= $userBanner ?>');
        }
    </style>

    <div id="ajax-success" class="success w3-light-grey w3-padding w3-margin-bottom w3-round w3-border w3-border-grey"></div>
    <div id="ajax-error" class="error w3-red w3-padding w3-margin-bottom w3-round w3-border w3-border-pink"></div>
    <h1>Account settings</h1><hr />

    <h2>Profile</h2>
    <div class="w3-row">
        <form class="pictureForm" method="post" action="" enctype="multipart/form-data">
            <b><legend>Profile banner</legend></b>
            <p>We recommend your banner be 1200x400</p>
            <p><input type="file" name="fileToupload" id="fileToupload" style="color:transparent;" onchange="this.style.color = 'black';" title=" " class="file-banner"></p>
            <?php
            if ($hasbanner) {
                echo '<input type="checkbox" class="w3-check" id="deleteBanner" name="deleteBanner" value="1">';
                echo '<label for="deleteBanner">Remove banner</label>';
            }
            ?>
            <input type="submit" value="Upload banner" id="banner" name="banner" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo">
        </form>
    </div><br />

    <div class="w3-row">
        <div class="w3-quarter">
            <form id="pictureForm" method="post" action="" enctype="multipart/form-data">
                <b><legend>Profile picture</legend></b>
                <p>We recommend your profile picture be 50x50 pixels</p>
                <p><input type="file" name="fileToUpload" id="fileToUpload" style="color:transparent;" onchange="this.style.color = 'black';" title=" " class="file-pfp"></p>
                <input type="submit" value="Upload picture" id="picture" name="picture" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo w3-col m4">
                <?php
                    if ($haspfp) {
                        echo '<span class="w3-col m1">&nbsp;</span>';
                        echo '<input type="submit" value="Remove picture" id="remove_picture" name="remove_picture" class="w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink w3-col m4">';
                    }
                ?>
            </form>
        </div><br /><br />

        <div class="w3-threequarter">
            <form id="username" name="username">
                <b><legend>Username</legend></b>
                <p>Your username can only be 2-50 numbers, letters, underscore, dash, or dot. Make sure it follows the <a href="/rules" target="_blank" rel="noopener noreferrer">Rules</a>.<br />Some usernames are reserved and cannot be used by anyone.</p>
                <p class="gr8-child"><span id="data-username-available" style="display: none; padding: 5px 5px 5px 5px;"></span></p>
                <input class="w3-input w3-border w3-mobile w3-third" value="<?php echo $current_user->username ?>" type="text" id="username-input" name="username" placeholder="<?php echo $combinedString ?>">
                <button class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" id="u_change" name="u_change">Update Username</button>
            </form><br />

            <form id="twitter" name="twitter">
                <b><legend>Twitter</legend></b>
                <p>To link your Twitter account, you will need to provide your handle.</p>
                <input class="w3-input w3-border w3-mobile w3-third" placeholder="@jack" value="<?= $current_user->twitter ?>" type="text" name="handle">
                <button class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" id="twitter_change" name="twitter_change">Update Twitter</button>
            </form><br />

            <form id="bsky" name="bsky">
                <b><legend>Bluesky</legend></b>
                <p>To link your Bluesky account, you will need to provide your handle (without the at part!). Note that some handles may not be seen as valid internally. If so, contact us so we can update it for you.</p>
                <input class="w3-input w3-border w3-mobile w3-third" placeholder="user.bsky.social" value="<?= $current_user->bsky ?>" type="text" name="handle">
                <button class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" id="bsky_change" name="bsky_change">Update Bluesky</button>
            </form><br />

            <form id="about" name="about">
                <b><legend>About</legend></b>
                <p>Your description can be 1-1000 characters. Keep it clean and make sure it doesn't violate our <a href="/rules" target="_blank" rel="noopener noreferrer">Rules</a>.</p>
                <textarea id="description" name="description" value="<?php echo $current_user->description ?>" placeholder="New about section" class="w3-input w3-border w3-mobile" rows="4" cols="50"><?php echo $current_user->description ?></textarea>
                <button class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" id="a_change" name="about">Change Description</button>
            </form><br />
        </div>
    </div><br /><hr />

    <h2>Account</h2>
    <form id="github-link" action="/ajax/auth.php" method="GET">
        <?php 
            $linked = empty($current_user->github_id) ? false : true;
            $linked_text = $linked ? 'Unlink' : 'Link';
        ?>

        <b><legend>Github oauth</legend></b>
        <p><?= $linked_text ?> your github account as a method to access your account.</p>
        <input type="hidden" name="authtype" value="github" />
        <?php if(!$linked) { ?>
            <button type="submit" name="github_link" class="w3-btn w3-round w3-padding-small w3-white w3-hover-light-grey w3-border w3-border-grey">
                <i class="fa fa-github-square" aria-hidden="true"></i> 
                Link github
            </button>
        <?php } else { ?>
            <button type="submit" name="github_unlink" class="w3-btn w3-round w3-padding-small w3-red w3-hover-light-grey w3-border w3-border-grey">
                <i class="fa fa-github-square" aria-hidden="true"></i> 
                Unlink github
            </button>
        <?php } ?>
    </form><br />

    <form id="private-page">
        <b><legend>Private page</legend></b>
        <p>Private or unprivate your page. Will prevent people that don't follow you from viewing your page.</p>

        <button class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" id="update_private_page">
            <?php echo $current_user->private_profile ? 'Unprivate' : 'Private' ?> Page
        </button>
    </form><br />

    <form id="password">
        <b><legend>Password</legend></b>
        <p>Your password to login to your account. Don't share this with anyone. Keep this saved somewhere, like a secure password manager.</p>
        <p>
            <input type="password" name="o_password" placeholder="Old password" class="w3-input w3-border w3-mobile w3-third" />
            <input type="password" name="n_password" placeholder="New password" class="w3-input w3-border w3-mobile w3-third" />
            <input type="password" name="c_password" placeholder="Confirm new password" class="w3-input w3-border w3-mobile w3-third" />
        </p>
        <input type="submit" name="change" value="Update Password" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" /><br />
    </form><br />

    <form id="email">
        <b><legend>Email address</legend></b>
        <p>Your email to login to your account. Don't share this either. Make sure you own this email incase you ever lose access to your account.</p>
        <p>
            <input type="email" value="<?php echo $current_user->email ?>" name="o_email" placeholder="Old email" class="w3-input w3-border w3-mobile w3-half" />
            <input type="email" name="n_email" placeholder="New email" class="w3-input w3-border w3-mobile w3-half" />
        </p>
        <input type="submit" name="e_change" value="Update Email Address" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" /><br />
    </form><br />

    <a class="w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink" href="login.php?status=logout">Logout</a><hr />

    <h2>Danger zone</h2>
    <div class="tooltip">
        <button onclick="document.getElementById('deactive').style.display='block'" name='deactive' class='w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink' />Delete Account</button>
        <span class="w3-tag w3-round w3-blue w3-padding-small tooltiptext"><i class="fa fa-info-circle" aria-hidden="true"></i> Begin the process of deleting your account and data</span>
    </div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/const/includes/linkbar.php'; ?>