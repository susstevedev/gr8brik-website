<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/build.php';

$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

if ($conn->connect_error) {
    exit($conn->connect_error);
}

$model_id = $conn->real_escape_string($_GET['id']);
$data = json_decode(fetch_build($model_id, $_SESSION['csrf']), true);

if ($data['message']) {
    $error = $data['message'];
} else {
    if($data['is_removed']) {
        $message = 'This creation has been <b>removed</b> by an admin. This means that it\'s not visible to regular users.';
    }
}

if (isset($_POST['reportv2'])) {
    header('Content-Type: application/json');

    if ($_SESSION['csrf'] === $_POST['csrf_token']) {
        if (loggedin()) {
            $id = $current_user->id;
            $reportable_id = (int)$_POST['reportable_id'];

            $type = isset($_POST['report_type']) ? trim($_POST['report_type']) : null;
            $desc = isset($_POST['other']) ? trim($_POST['other']) : null;
            $reason = isset($_POST['reason']) ? trim($_POST['reason']) : null;

            $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
            if ($conn->connect_error) {
                echo json_encode(['error' => 'Database connection failed.']);
                exit;
            }

            if (!isset($type) || empty($type)) {
                echo json_encode(['error' => 'No report type provided']);
                exit;
            }

            if (!isset($reason) || empty($reason)) {
                echo json_encode(['error' => 'No reason provided']);
                exit;
            }

            $stmt_check = $conn->prepare("SELECT * FROM reports WHERE reporter_user_id = ? AND reportable_id = ? AND reportable_type = ?");
            $stmt_check->bind_param("iis", $id, $reportable_id, $type);
            $stmt_check->execute();
            $result = $stmt_check->get_result();

            if($result->num_rows !== 0) {
                echo json_encode(['error' => 'You have already reported this content.']);
                exit;
            }

            if ($reason === 'other' && empty($desc)) {
                echo json_encode(['error' => 'Please fill in the description box to explain your report.']);
                exit;
            }

            if ($desc !== null) {
                if(strlen($desc) > 500) {
                    echo json_encode(['error' => 'Description shall be under 500 characters.']);
                    exit;
                }
            }

            $stmt = $conn->prepare("INSERT INTO reports (reportable_id, reportable_type, reason, description, reporter_user_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", $reportable_id, $type, $reason, $desc, $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => 'Content reported! Thanks for making our platform a safe space for everyone!']);
            } else {
                echo json_encode(['error' => 'Oops! We couldn\'t report the submitted content at this moment. Please try again later.']);
            }

            $stmt->close();
            $conn->close();
        } else {
            echo json_encode(['error' => 'Oops! Please login to report content.']);
        }
    } else {
        echo json_encode(['error' => 'Oops! Your CSRF token seems to be invalid.']);
    }
    exit;
}

if (isset($_POST['delete_model'])) {
    header('Content-Type: application/json');
    $model_id = (int)$_POST['model_id'];

    if ($_SESSION['csrf'] === $_POST['csrf_token']) {
        if (loggedin() && $current_user->admin === true) {
            $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

            $sql = "SELECT id, removed FROM model WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $model_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $sql2 = "UPDATE model SET removed = 1 WHERE id = ?";

                if($row['removed']) {
                    $sql2 = "UPDATE model SET removed = 0 WHERE id = ?";
                }

                $stmt2 = $conn->prepare($sql2);
                $stmt2->bind_param("i", $model_id);

                if ($stmt2->execute()) {
                    echo json_encode(['success' => 'Creation updated']);
                } else {
                    echo json_encode(['error' => 'Error deleting model listing']);
                }
            } else {
                echo json_encode(['error' => 'Creation not found']);
            }
        } else {
            echo json_encode(['error' => 'An authentication error has occured']);
        }
    } else {
        echo json_encode(['error' => 'Oops! Your CSRF token seems to be invalid.']);
    }
    exit;
}

if (isset($_POST['delete_comment'])) {
    header('Content-Type: application/json');
    $comment_id = (int)$_POST['id'];

    if ($_SESSION['csrf'] === $_POST['csrf_token']) {
        if (loggedin()) {
            $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
            $id = $current_user->id;

            $sql = "SELECT id, hidden, user, model FROM comments WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $comment_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if(trim($row['user']) === trim($current_user->id) || $current_user->admin) {
                    $sql2 = "UPDATE comments SET hidden = 1 WHERE id = ?";
					$type = 'delete';
                    $model_id = $row['model'];

                    if($row['hidden']) {
                        $sql2 = "UPDATE comments SET hidden = 0 WHERE id = ?";
						$type = 'restore';
                    }

                    $stmt2 = $conn->prepare($sql2);
                    $stmt2->bind_param("i", $comment_id);

                    if ($stmt2->execute()) {
                        echo json_encode(['success' => 'Comment updated', 'type' => $type, 'admin' => $current_user->admin ? true : false]);
                    } else {
                        echo json_encode(['error' => 'Error deleting comment']);
                    }
                } else {
                    echo json_encode(['error' => 'An authentication error has occured']);
                }
            } else {
                echo json_encode(['error' => 'Comment not found']);
            }
        } else {
            echo json_encode(['error' => 'Not logged in']);
        }
    } else {
        echo json_encode(['error' => 'Oops! Your CSRF token seems to be invalid.']);
    }
    exit;
}

$model_embed = htmlspecialchars("<iframe src='https://gr8brik.rf.gd/viewer.html?model=" . htmlspecialchars($_GET['id']) . "' id='model_embed' title='model_embed' width='300' height='200'></iframe>");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title><?php echo $data['name'] ?? "This user's creation"?> by <?php echo $data['username'] ?? null ?></title>
    <?php include 'header.php' ?>

    <script type="text/javascript">
        $(document).ready(function() {
            window.embed_model = <?php echo (int)$_GET['id'] ?>;
        });
    </script>
    <script type="text/javascript" src="/lib/creation.js"></script>
</head>

<body class="w3-light-blue w3-container">
    <div id="report-comment" class="w3-modal" style="z-index: 999999">
        <div class="w3-modal-content w3-card-2 w3-light-grey w3-center">
            <div class="w3-container">
                <span onclick="$('#reportCommentForm')[0].reset();$('#report-comment').hide();" class="w3-button w3-large w3-red w3-hover-white w3-display-topright">&times;</span>
                <form id="reportCommentForm">
                    <h2>Why do you want to report this content?</h2>
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

                    <span class="w3-btn w3-large w3-white w3-hover-blue w3-round-small" onclick="$('#reportCommentForm')[0].reset();$('#report-comment').hide();">Close</span>
                    <button type="submit" class="w3-btn w3-large w3-white w3-hover-red w3-round-small">Report</button>
                </form>
            </div>
        </div>
    </div>

    <div id="delete-model" class="w3-modal" style="z-index: 999999">
        <div class="w3-modal-content w3-card-2 w3-light-grey w3-center">
            <div class="w3-container">
                <span onclick="$('#delete-model').hide();" class="w3-button w3-large w3-red w3-hover-white w3-display-topright">&times;</span>
                <form id="deleteModelForm">
                    <h2>Are you sure you want to <?php echo $data['is_removed'] ? 'Restore' : 'Delete'; ?> this creation?</h2>

                    <input type="hidden" name="model_id" value="<?php echo $model_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf']; ?>">
                    <input type="hidden" name="delete_model" value="1">

                    <span class="w3-btn w3-large w3-white w3-hover-blue w3-round-small" onclick="$('#delete-model').hide();">No</span>
                    <button type="submit" class="w3-btn w3-large w3-white w3-hover-red w3-round-small">Yes</button>
                </form>
            </div>
        </div>
    </div>

    <div id="delete-comment" class="w3-modal" style="z-index: 999999">
        <div class="w3-modal-content w3-card-2 w3-light-grey w3-center">
            <div class="w3-container">
                <span onclick="$('#delete-comment').hide();" class="w3-button w3-large w3-red w3-hover-white w3-display-topright">&times;</span>
                <form id="deleteCommentForm">
                    <h2>Are you sure you want to delete/restore this comment?</h2>

                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf']; ?>">
                    <span class="w3-btn w3-large w3-white w3-hover-blue w3-round-small" onclick="$('#delete-comment').hide();">No</span>
                    <button type="submit" class="w3-btn w3-large w3-white w3-hover-red w3-round-small">Yes</button>
                </form>
            </div>
        </div>
    </div>

    <?php include 'navbar.php' ?>

    <?php if (isset($error)) { ?>
        <div class="message w3-card-2 w3-padding w3-round-small w3-red"><?php echo $error ?></div><br />
        <?php exit; ?>
    <?php } ?>

    <?php if (isset($message)) { ?>
        <div class="message w3-card-2 w3-padding w3-round-small w3-light-grey"><?php echo $message ?></div><br />
    <?php } ?>

    <?php if ($loggedin === true) { ?>
        <form id="downvote" action="/ajax/build" method="post"><input type="hidden" value="<?php echo $model_id ?>" name="model_id"></form>
        <form id="upvote" action="/ajax/build" method="post"><input type="hidden" value="<?php echo $model_id ?>" name="model_id"></form>
    <?php } ?>

    <main id="wrapper">
        <div id="ajax-error" class="w3-col m9 w3-bottom w3-card-2 w3-padding w3-round-small w3-red"></div>
        <div id="ajax-success" class="w3-col m9 w3-bottom w3-card-2 w3-padding w3-round-small w3-light-grey"></div>

        <figure class="model-screenshot">
            <iframe id="model-embed" src="/viewer.html?model=<?php echo urlencode($_GET['id']) ?>" class="w3-border w3-card-2"></iframe>

            <figcaption data-testid="description" class="gr8-theme w3-light-grey w3-card-2 w3-border w3-padding" style="width: 65%">
                <header>
                    <h3 class="creation-page-title" id="name"><?php echo $data['name'] ?></h3>

                    <p class="creation-page-meta">By 
                        <b class="meta-author">
                            <?php if(!User::isDeleted($data['userid'])) { ?><a id="user-link" class="<?php echo $data['model_admin'] === true ? 'w3-text-red w3-hover-text-yellow' : ''; ?>" href="/@<?php echo urlencode($data['username'])?>"><?php } ?><i class="fa fa-at" aria-hidden="true"></i><?php echo $data['username'] ?><?php if(!User::isDeleted($data['userid'])) { ?></a><?php } ?>
                        </b>
                        • <span id="user-link-followers"><?php echo $data['followers'] ?> followers</span>
                    </p>

                    <p class="creation-page-meta" id="stats">
                        <span title="<?php echo $data['date'] ?>">Published <?php echo time_ago($data['date']) ?></span> • <span><?php echo $data['views'] ?> views</span>
                    </p>
                </header>
                <hr />

                <?php if (!empty($data['description'])) { ?>
                    <h4><span id="description" class="w3-large"><?php echo $data['description'] ?></span><br /></h4>
                    <hr />
                <?php } ?>

                <?php if (!empty($data['tags'])) { ?>
                    <h4><span id="tags" class="w3-medium">
                            <?php
                            $_tags = $data['tags'];
                            foreach ($_tags as $_tagg) {
                                if ($_tagg['display'] === true) {
                            ?>
                                    <a href="/list?t=<?php echo urlencode(htmlspecialchars($_tagg['name'])) ?>" class="w3-button w3-padding-small w3-hover-dark-grey w3-light-grey w3-border">
                                        <?php echo htmlspecialchars($_tagg['name']) ?>
                                    </a>
                            <?php
                                }
                            }
                            ?>
                        </span><br /></h4>
                    <hr />
                <?php } ?>

                <div class="w3-right">
					<?php if ($data['can_edit']) { ?>
						<div class="w3-dropdown-click" style="z-index: 999;">
							<div class="tooltip">
								<span class="w3-tag w3-blue tooltiptext">Download this model's screenshot and json file</span>
								<button onclick="dropdown('dropdown-download')" class="w3-btn w3-blue w3-hover-opacity w3-padding-small w3-border w3-border-indigo"><i class="fa fa-download" aria-hidden="true"></i> Download</button>
							</div>
							<div id="dropdown-download" class="w3-dropdown-content w3-bar-block w3-border" style="z-index: 999;">
								<a id="data-gr8-download" class="w3-bar-item w3-btn w3-hover-blue w3-border" href="<?php echo $data['model'] ?>" download="<?php echo htmlspecialchars($data['name']) ?> by <?php echo htmlspecialchars($data['username']) ?>.<?php echo substr(strrchr($data['model'], '.'), 1) ?>">Creation file</a>
								<a id="data-webp-download" class="w3-bar-item w3-btn w3-hover-blue w3-border" href="<?php echo $data['screenshot'] ?>" download="<?php echo htmlspecialchars($data['name']) ?> by <?php echo htmlspecialchars($data['username']) ?>.<?php echo substr(strrchr($data['screenshot'], '.'), 1) ?>">Thumbnail file</a>
							</div>
						</div>
					<?php } ?>

                    <?php if (loggedin()) { ?>
                        <?php if ($data['voted'] === true) { ?>
                            <div class="tooltip" id="data-unlike-creation">
                                <span class="w3-tag w3-blue tooltiptext">Unfavorite this creation</span>
                                &nbsp;<button class="unlike-creation w3-btn w3-red w3-hover-opacity w3-padding-small w3-border w3-border-pink"><span class="fa fa-star"></span>
                                    <span class="text">Unfavorite (<?php echo $data['likes'] ?>)</span>
                                </button>&nbsp;
                            </div>
                        <?php } else { ?>
                            <div class="tooltip" id="data-like-creation">
                                <span class="w3-tag w3-blue tooltiptext">Favorite this creation to support it and the creator</span>
                                &nbsp;<button class="like-creation w3-btn w3-yellow w3-hover-opacity w3-padding-small w3-border w3-border-orange"><span class="fa fa-star-o"></span>
                                    <span class="text">Favorite (<?php echo $data['likes'] ?>)</span>
                                </button>&nbsp;
                            </div>
                        <?php } ?>

						<div class="w3-dropdown-click" style="z-index: 999;">
							<div class="tooltip">
								<span class="w3-tag w3-blue tooltiptext"><?php if ($data['is_subbed']) { ?> Unsubscribe to cancel notifications out for this creation <?php } else { ?> Subscribe to get notifications for this creation <?php }?></span>
								<button onclick="dropdown('dropdown-subscribe')" class="w3-btn creation-subscribe <?php if ($data['is_subbed']) { ?> w3-red w3-border w3-border-pink <?php } else { ?> w3-yellow w3-border w3-border-orange <?php } ?> w3-hover-opacity w3-padding-small"><span class="fa <?php if ($data['is_subbed']) { ?> fa-plus-square <?php } else { ?> fa-plus-square-o <?php } ?>"></span>
                                    <span class="text"><?php if ($data['is_subbed']) { ?> Unsubscribe <?php } else { ?> Subscribe <?php } ?></span>
                                </button>
							</div>
							<div id="dropdown-subscribe" class="w3-dropdown-content w3-bar-block w3-border" style="z-index: 999;">
								<a class="<?php if ($data['is_subbed_comment']) { ?> creation-comment-unsubscribe w3-red <?php } else { ?> creation-comment-subscribe w3-yellow <?php } ?> w3-bar-item w3-btn w3-hover-blue w3-border"><?php if ($data['is_subbed_comment']) { ?> Unsubscribe from <?php } else { ?> Subscribe to <?php } ?> comments</a>
								<a class="<?php if ($data['is_subbed_fav']) { ?> creation-fav-unsubscribe w3-red <?php } else { ?> creation-fav-subscribe w3-yellow <?php } ?> w3-bar-item w3-btn w3-hover-blue w3-border"><?php if ($data['is_subbed_fav']) { ?> Unsubscribe from <?php } else { ?> Subscribe to <?php } ?> favorites</a>
							</div>
						</div>

                        <?php if ($current_user->admin === true) { ?>
                            <div class="tooltip" id="data-delete-model">
                                <span class="w3-tag w3-blue tooltiptext"><?php echo $data['is_removed'] ? 'Restore' : 'Delete'; ?> this creation as an admin</span>
                                <button onclick='document.getElementById("delete-model").style.display="block"' name="delete" class="w3-btn w3-red w3-hover-opacity w3-padding-small w3-border w3-border-pink" />
                                <i class="fa fa-trash" aria-hidden="true"></i> <?php echo $data['is_removed'] ? 'Restore' : 'Delete'; ?>
                                </button>&nbsp;
                            </div>
                        <?php } ?>

                        <?php if ($data['can_edit']) { ?>
                            <div class="tooltip" id="data-edit-model">
                                <span class="w3-tag w3-blue tooltiptext">Edit this creation</span>
                                <a href="/acc/creations?edit=<?php echo $_GET['id'] ?>">
                                    <button name="edit" class="w3-btn w3-blue w3-hover-opacity w3-padding-small w3-border w3-border-indigo" />
                                        <i class="fa fa-pencil" aria-hidden="true"></i> Edit
                                    </button>
                                </a>
                            </div>
                        <?php } ?>

                        <div class="tooltip" id="data-report-model">
                            <span class="w3-tag w3-blue tooltiptext">Report this creation to moderators</span>
                            <button data-id="<?php echo $_GET['id'] ?>" id="report-creation-button"  name="flag" class="w3-btn w3-red w3-hover-opacity w3-padding-small w3-border w3-border-pink" />
                            <i class="fa fa-flag" aria-hidden="true"></i> Report
                            </button>&nbsp;
                        </div>
                    <?php } ?>
                </div>
                <p><span class="w3-large">Embed:</span></p>
                <p><textarea class="w3-card-2 w3-round" rows='2' cols='75' readonly><?php echo $model_embed ?></textarea></p>
                <hr />
            </figcaption>
        </figure>

        <div class="w3-container w3-margin">
            <?php if ($data['message']) { ?>
                <p><div class="gr8-theme w3-light-grey w3-round w3-padding"><?php echo $data['message'] ?></div></p>
            <?php } elseif (loggedin()) { ?>
                <div id="comment-form" class="w3-half w3-row w3-display-container">
                    <div class="w3-left xw3-margin-right" id="comment-profile-picture">
                        <img class="w3-round" width="50px" height="50px" src="<?php echo $current_user->picture ?>">
                    </div>

                    <div class="w3-hide-small w3-left xw3-margin-right">
                        <i class="w3-large w3-text-white fa fa-play fa-rotate-180"></i>
                    </div>

                    <div id="post" class="w3-rest w3-hover-shadow w3-border w3-border-blue">
                        <textarea name="comment-box" id="comment-box" class="w3-input w3-col s12" placeholder="Add a comment... (@userid mentions someone, BBcode supported)"></textarea>
                    </div>

                    <div class="w3-col s12 w3-margin-top">
                        <button id="post-comment" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo">
                            <span id="comment-btn-text"><i class="fa fa-paper-plane-o" aria-hidden="true"></i> Post comment</span>
                        </button>
                    </div>
                </div>
            <?php } else { ?>
                <div>
                    <a href="/acc/login" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo"><i class="fa fa-paper-plane-o" aria-hidden="true"></i> Login to post comments</a>
                </div>
            <?php } ?>
        </div>
    </main>

    <div id="data-comment-wrapper">
        <h4><span class="fa fa-comments-o" aria-hidden="true"></span> <span id="comment-count"><?php echo $data['comments'] ?></span> comments</h4><hr />

            <div id="user-conversation-wrapper">
                <?php
                    foreach($data['conversation_subbed'] as $subbed) {
                        ?>
                        <span class="tooltip avatar">
                            <span class="w3-blue tooltiptext"><?php echo $subbed['username'] ?></span>
                            <a href="/user/<?php echo $subbed['id'] ?>"><img src="<?php echo $subbed['picture'] ?>" class="w3-circle w3-grey" width="50px" height="50px" alt="User Avatar" /></a>
                        </span>
                        <?php
                    }
                ?>
            </div>

        <?php
        $comment_data = json_decode(fetch_comments($model_id, $_SESSION['csrf']), true);

        if ($comment_data && is_array($comment_data)) {
            $grouped_comments = [];

            if(isset($_GET['depth']) && isset($_GET['parent'])) {
                ?>
                    You are viewing the replies of a comment.<br />
                    <a href="?depth=false">Go back</a>
                <?php
            }

            foreach ($comment_data as $comment) {
                if (!is_array($comment)) {
                    continue;
                }

                $parent_id = $comment['parent'];
                $grouped_comments[$parent_id][] = $comment;
            }

            function comment_tree(array $grouped_comments, $parent_id = null, $depth = 0) {
                global $current_user;

                if (!isset($grouped_comments[$parent_id])) {
                    return;
                }

                foreach ($grouped_comments[$parent_id] as $comment) {
                    $indentation = $depth * 25;
                    ?>
                    <div id="comment<?php echo $comment['id'] ?>" data-testid="<?php echo $comment['id'] ?>" data-level="<?php echo $depth ?>" data-parent="<?php echo $comment['parent'] ?>" class="comment w3-row w3-section" style="margin-left: <?php echo $indentation; ?>px;">
                        <?php
                            if($depth > 10) {
                                ?>
                                <a href="?depth=true&parent=<?php echo $parent_id?>">More comments...</a></div>
                                <?php
                                return;
                            }
                        ?>

                        <div class="w3-col comment-profile-picture">
                            <img class="w3-bar-item w3-round w3-card-2 w3-grey" loading="lazy" width="50" height="50" src="<?php echo $comment['picture'] ? $comment['picture'] : '/img/no_image.png' ?>">
                        </div>

                        <div data-testid="gr8-comment-divider" class="comment-divider w3-hide-small w3-col">
                            <i class="w3-large w3-text-white fa fa-play fa-rotate-180"></i>
                        </div>

                        <div id="comment-text" class="comment-body w3-col" style="width: <?php echo max(40, 60 - ($depth * 2)); ?>%;">
                            <article class="<?php echo $comment['votes'] >= 100 ? 'w3-orange' : 'gr8-theme w3-light-grey' ?> w3-card-2 w3-padding-small w3-round w3-border w3-border-grey">
                                <header class="w3-padding-bottom">
                                    <b>
                                        <?php if (!User::isDeleted($comment['userid'])) { ?>
                                            <a href="/@<?php echo urlencode($comment['username']) ?>" class="<?php echo $comment['user_admin'] === true ? 'w3-text-red w3-hover-text-yellow' : ''; ?>">
                                        <?php } ?>
                                        <?php echo $comment['username'] ?>
                                        <?php if (!User::isDeleted($comment['userid'])) { ?>
                                            </a>
                                        <?php } ?>
                                    </b>

                                    <span class="w3-mobile w3-right">
                                        <?php echo ($comment['is_op'] ?? false) ? '<b class="is-op" title="Original Poster">OP</b> - ' : '' ?>
                                        <time class="date" title="<?php echo $comment['date'] ?>" datetime="<?php echo $comment['date'] ?>"><?php echo $comment['date'] ?></time>
                                        <time class="edited-at" title="<?php echo $comment['edited_at'] ?>" datetime="<?php echo $comment['edited_at'] ?>"><?php echo !empty($comment['edited_at']) ? '(edited)' : null ?></time> -
                                        <span class="votes"><span class="count"><?php echo $comment['votes'] ?></span> favorites</span>
                                    </span>
                                </header>

                                <?php if(!empty($comment['is_hidden'])) { ?>
                                    <span class="comment-error w3-text-grey"><i class="fa fa-info-circle" aria-hidden="true"></i> <i>This comment has been removed</i></span><br />
                                <?php } ?>

                                <span class="text w3-padding-bottom" style="word-wrap: break-word; white-space: normal;">
                                    <?php if (!empty($comment['comment'])) { ?>
                                        <?php echo $comment['comment'] ?>
                                    <?php } else { ?>
                                        <i>Comment was removed</i>
                                    <?php } ?>
                                </span>

                                <?php if (loggedin() && trim($current_user->id) === trim($comment['userid'])) { ?>
                                    <form class="edit w3-hide">
                                        <textarea class="edit-textarea"><?php echo $comment['comment_og'] ?></textarea><br />
                                        <button class="save-btn w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo">Save</button>
                                        <button class="cancel-btn w3-btn w3-white w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-grey">Cancel</button>
                                    </form>
                                <?php } ?>
                                <br />

                                <?php if(loggedin()) { ?>
                                    <?php if (($comment['voted'] ?? false) === false) { ?>
                                        <div class="tooltip">
                                            <span class="w3-blue tooltiptext">Favorite Comment</span>
                                            <button data-id="<?php echo $comment['id'] ?>" class="upvote-btn fa fa-star-o w3-btn w3-yellow w3-hover-opacity w3-round w3-padding-small"></button>
                                        </div>
                                    <?php } elseif (($comment['voted'] ?? false) === true) { ?>
                                        <div class="tooltip">
                                            <span class="w3-blue tooltiptext">Unfavorite Comment</span>
                                            <button data-id="<?php echo $comment['id'] ?>" class="downvote-btn fa fa-star w3-btn w3-pink w3-hover-opacity w3-round w3-padding-small"></button>
                                        </div>
                                    <?php } ?>

                                    <div class="w3-right">
                                        <div class="tooltip">
                                            <span class="w3-blue tooltiptext">Reply to this comment</span>
                                            <button data-id="<?php echo $comment['id'] ?>" class="reply-btn fa fa-reply w3-btn w3-blue w3-hover-opacity w3-round w3-padding-small"></button>
                                        </div>

                                        <?php if (trim($current_user->id) === trim($comment['userid'])) { ?>
                                            <div class="tooltip">
                                                <span class="w3-blue tooltiptext">Edit this comment</span>
                                                <button data-id="<?php echo $comment['id'] ?>" class="edit-btn fa fa-pencil w3-btn w3-blue w3-hover-opacity w3-round w3-padding-small"></button>
                                            </div>
                                        <?php } ?>

                                        <?php if (trim($current_user->id) !== trim($comment['userid']) && !$current_user->admin) { ?>
                                            <div class="tooltip" id="data-report-comment">
                                                <span class="w3-blue tooltiptext">Report this comment to moderators</span>
                                                <button data-id="<?php echo $comment['id'] ?>" id="report-comment-button" name="flag-comment" class="fa fa-flag w3-btn w3-red w3-hover-opacity w3-padding-small w3-round"></button>
                                            </div>
                                        <?php } ?>

                                        <?php if (trim($current_user->id) === trim($comment['userid']) || $current_user->admin) { ?>
                                            <div class="tooltip" id="data-report-comment">
                                                <span class="w3-blue tooltiptext"><?php echo ($comment['is_hidden'] ?? false) ? 'Restore' : 'Delete'; ?> this comment</span>
                                                <button data-id="<?php echo $comment['id'] ?>" id="delete-comment-button" name="delete-comment" class="fa fa-trash w3-btn w3-red w3-hover-opacity w3-padding-small w3-round"></button>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </article>

                            <!--<div id="reply-form-container-<?php echo $comment['id'] ?>" class="w3-margin reply-form-box" style="display:none;">
                                <div class="w3-right w3-block">
                                    <textarea name="reply-box" id="reply-box-<?php echo $comment['id'] ?>" class="reply-box w3-input w3-border w3-border-blue w3-hover-shadow" placeholder="Add a reply... (@userid mentions someone, BBcode supported)" rows="2"></textarea>
                                    <button data-parent="<?php echo $comment['id'] ?>" class="post-reply w3-right w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo">
                                        <span class="comment-btn-text"><i class="fa fa-reply" aria-hidden="true"></i> Post reply</span>
                                    </button>
                                </div>
                            </div>-->
                        </div>
                    </div>
                    <?php
                    comment_tree($grouped_comments, $comment['id'], $depth + 1);
                }
            }

            if(isset($_GET['depth']) && isset($_GET['parent'])) {
                comment_tree($grouped_comments, $_GET['parent'], 0);
            } else {
                comment_tree($grouped_comments, 0, 0);
            }
        }
        ?>

        <template id="reply-box">
            <div id="reply-form-container-" class="w3-margin reply-form-box">
                <div class="w3-right w3-block">
                    <textarea name="reply-box" id="reply-box-" class="reply-box w3-input w3-border w3-border-blue w3-hover-shadow" placeholder="Add a reply... (@userid mentions someone, BBcode supported)" rows="2"></textarea>
                    <button data-parent="" class="post-reply w3-right w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo">
                        <span class="comment-btn-text"><i class="fa fa-reply" aria-hidden="true"></i> Post reply</span>
                    </button>
                </div>
            </div>
        </template>

        <template id="comment-template">
            <div id="comment" data-testid="" class="comment w3-row w3-section">
                <div class="comment-profile-picture w3-col">
                    <img class="w3-bar-item w3-round w3-card-2 w3-grey" width="50" height="50" src="/img/no_image.png">
                </div>

                <div data-testid="gr8-comment-divider" class="comment-divider w3-hide-small w3-col">
                    <i class="w3-large w3-text-white fa fa-play fa-rotate-180"></i>
                </div>

                <div class="comment-body w3-col" style="width: 60%;">
                    <article class="gr8-theme w3-card-2 w3-light-grey w3-padding-small w3-round w3-border w3-border-grey w3-text-black">
                        <header class="w3-padding-bottom">
                            <b><a class="comment-user" href="/@"></a></b>

                            <span class="w3-mobile w3-right">
                                <span class="is-op" title="Original Poster"></span>
                                <span class="date"></span> -
                                <span class="votes"><span class="count">0</span> favorites</span>
                            </span>
                        </header>

                        <span class="text w3-padding-bottom" style="word-wrap: break-word; white-space: normal;"></span>

                        <form class="edit w3-hide">
                            <textarea class="edit-textarea"></textarea><br />
                            <button class="save-btn w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo">Save</button>
                            <button class="cancel-btn w3-btn w3-white w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-grey">Cancel</button>
                        </form><br />

                        <div class="tooltip">
                            <span class="w3-blue tooltiptext">Favorite Comment</span>
                            <button class="upvote-btn fa fa-star-o w3-btn w3-yellow w3-hover-opacity w3-round w3-padding-small"></button>
                        </div>

                        <div class="w3-right">
                            <div class="tooltip">
                                <span class="w3-blue tooltiptext">Reply to this comment</span>
                                <button data-id="" class="reply-btn fa fa-reply w3-btn w3-blue w3-hover-opacity w3-round w3-padding-small"></button>
                            </div>

                            <div class="tooltip">
                                <span class="w3-blue tooltiptext">Edit this comment</span>
                                <button data-id="" class="edit-btn fa fa-pencil w3-btn w3-blue w3-hover-opacity w3-round w3-padding-small"></button>
                            </div>

                            <div class="tooltip" id="data-report-comment">
                                <span class="w3-blue tooltiptext">Delete this comment</span>
                                <button id="delete-comment-button" name="delete-comment" class="fa fa-trash w3-btn w3-red w3-hover-opacity w3-padding-small w3-round"></button>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </template>
    </div>
    <?php
    echo '<br />';
    include('linkbar.php');
    ?>
</body>

</html>