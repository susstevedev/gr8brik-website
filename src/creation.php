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

if (isset($_POST['report'])) {
    header('Content-Type: application/json');

    if ($_SESSION['csrf'] === $_POST['csrf_token']) {
        if (loggedin()) {
            $id = $current_user->id;
            $report_id = bin2hex(random_bytes(16));
            $date = date("Y-m-d H:i:s");
            $model_id = (int)htmlspecialchars($_POST['model_id']);

            if (!empty($_POST['other']) || isset($_POST['reason']) && $_POST['reason'] === "something-else") {
                $reason = htmlspecialchars($_POST['other']);
            } elseif (isset($_POST['reason'])) {
                $reason = htmlspecialchars($_POST['reason']);
            } else {
                echo json_encode(['error' => 'Oops! No reason selected.']);
            }

            $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
            if ($conn->connect_error) {
                echo json_encode(['error' => 'Database connection failed.']);
                exit;
            }

            $stmt = $conn->prepare("INSERT INTO reported (id, build, date, reason, user) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sissi", $report_id, $model_id, $date, $reason, $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => 'Creation reported! Thanks for making our platform a safe space for everyone!']);
            } else {
                echo json_encode(['error' => 'Oops! We couldn\'t report the submitted creation at this moment. Please try again later.']);
            }

            $stmt->close();
            $conn->close();
        } else {
            echo json_encode(['error' => 'Oops! Please login to report a creation.']);
        }
    } else {
        echo json_encode(['error' => 'Oops! Your cross-site-request-forgery token seems to be invalid.']);
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

$model_embed = htmlspecialchars("<iframe src='https://gr8brik.rf.gd/viewer.html?model=" . htmlspecialchars($_GET['id']) . "' id='model_embed' title='model_embed' width='300' height='200'></iframe>");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title><?php echo $data['name'] ?? "This user's creation"?> by <?php echo $data['username'] ?? null ?></title>
    <?php include 'header.php' ?>
</head>

<body class="w3-light-blue w3-container">

    <div id="report" class="w3-modal" style="z-index: 999999">
        <div class="w3-modal-content w3-card-2 w3-light-grey w3-center">
            <div class="w3-container">
                <span onclick="$('#report').hide();" class="w3-button w3-large w3-red w3-hover-white w3-display-topright">&times;</span>
                <form id="reportForm">
                    <h2>Why do you want to report this creation?</h2>
                    <b>You can only report a creation when it violates our <a href="/rules?src=creation" target="_blank"><i class="fa fa-external-link" aria-hidden="true"></i>rules</a>.</b><br />
                    <input type="checkbox" name="reason" value="violent" class="w3-check"> <label>Violent or extreme content</label><br />
                    <input type="checkbox" name="reason" value="misinformation" class="w3-check"> <label>Misinformation/disinformation</label><br />
                    <input type="checkbox" name="reason" value="inappropriate-content" class="w3-check"> <label>Inappropriate content</label><br />
                    <input type="checkbox" name="reason" value="harrasing-me" class="w3-check"> <label>Harassing me or others</label><br />
                    <input type="checkbox" name="reason" value="spam" class="w3-check"> <label>Spam</label><br />
                    <input type="checkbox" name="reason" value="something-else" class="w3-check" id="otherReasonToggle"> <label>Something else</label><br /><br />

                    <textarea class="w3-input w3-card-2 w3-hover-shadow w3-mobile w3-round w3-hide" name="other" id="otherReason" placeholder="Explain more..." rows="4"></textarea><br />

                    <input type="hidden" name="user" value="<?php echo $current_user->id; ?>">
                    <input type="hidden" name="model_id" value="<?php echo $model_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf']; ?>">
                    <input type="hidden" name="report" value="1">

                    <span class="w3-btn w3-large w3-white w3-hover-blue w3-round-small" onclick="$('#report').hide();">Close</span>
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

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            $(document).ready(function() {
                $("#otherReasonToggle").change(function() {
                    $("#otherReason").toggleClass("w3-hide", !this.checked);
                });

                function fetchCSRFToken(callback) {
                    $.get("/ajax/config.php", {
                            get_csrf_token: true
                        }, function(data) {
                            console.log(data);
                            $("#csrf_token").val(data.csrf_token);
                            window.csrf_token = data.csrf_token;
                            callback();
                        }, "json")
                        .fail(function(xhr, text, err) {
                            alert(text);
                        });
                }

                $("#reportForm").submit(function(e) {
                    e.preventDefault();
                    fetchCSRFToken(function() {
                        $.ajax({
                            url: "",
                            type: "POST",
                            data: $("#reportForm").serialize(),
                            dataType: "json",
                            success: function(response) {
                                if (response.success) {
                                    alert(response.success);
                                    $("#report").hide();
                                    $("#reportForm")[0].reset();
                                } else {
                                    alert(response.error);
                                }
                            },
                            error: function() {
                                alert("An error occurred. Please try again later.");
                            }
                        });
                    });
                });

                $("#deleteModelForm").submit(function(e) {
                    e.preventDefault();
                    fetchCSRFToken(function() {
                        $.ajax({
                            url: "",
                            type: "POST",
                            data: $("#deleteModelForm").serialize(),
                            dataType: "json",
                            success: function(response) {
                                if (response.success) {
                                    alert(response.success);
                                    window.location.reload();
                                } else {
                                    alert(response.error);
                                }
                            },
                            error: function() {
                                alert("An error occurred. Please try again later.");
                            }
                        });
                    });
                });

                $(document).on("click", ".like-creation, .unlike-creation", function(event) {
                    event.preventDefault();

                    let btn = $(this);
                    let btnspan = btn.find('span.text');
                    let btnicon = btn.find('span.fa');

                    let is_like = btn.hasClass('like-creation');
                    let data = {
                        model_id: embed_model
                    };

                    if (is_like) {
                        data.upvote = true;
                    } else {
                        data.downvote = true;
                    }

                    $.ajax({
                        url: "/ajax/build",
                        method: "POST",
                        dataType: 'json',
                        data: data,
                        success: function(res) {
                            if ((is_like && res.success) || !is_like) {
                                if (is_like) {
                                    btnspan.text(res.text);
                                    btnicon.removeClass('fa-star-o').addClass('fa-star');
                                    btn.removeClass('like-creation w3-yellow')
                                        .addClass('unlike-creation w3-red');
                                } else {
                                    btnspan.text(res.text);
                                    btnicon.removeClass('fa-star').addClass('fa-star-o');
                                    btn.removeClass('unlike-creation w3-red')
                                        .addClass('like-creation w3-yellow');
                                }
                            } else if (res.error) {
                                console.error(res.error);
                            }
                        },
                        error: (xhr, text, err) => {
                            console.error(text, err, xhr);
                            try {
                                let res = JSON.parse(xhr.responseText);
                                alert(res.error || "An error occurred, try again later");
                            } catch (e) {
                                alert("An error occurred, try again later");
                            }
                        }
                    });
                });

                $(document).on("click", ".upvote-btn", function() {
                    event.preventDefault();
                    let comment_id = $(this).data("id");
                    let btn = $(this);

                    $.ajax({
                        url: "/ajax/build",
                        method: "POST",
                        dataType: 'json',
                        data: {
                            upvote_comment: true,
                            comment_id: comment_id
                        },
                        success: function(response) {
                            if (response.success) {
                                btn.replaceWith(`<button data-id="${comment_id}" class="downvote-btn fa fa-star w3-btn w3-pink w3-hover-opacity w3-round w3-padding-small"></button>`);
                            } else if (response.error) {
                                $("#ajax-error").text(response.error).show(500).delay(5000).hide(500);
                            }
                        },
                        error: (jqXHR, textStatus, errorThrown) => {
                            console.error("error:", textStatus, errorThrown, jqXHR);
                            const response = JSON.parse(jqXHR.responseText);
                            alert(response.error);
                        }
                    });
                });

                $(document).on("click", ".downvote-btn", function() {
                    event.preventDefault();
                    let comment_id = $(this).data("id");
                    let btn = $(this);

                    $.ajax({
                        url: "/ajax/build",
                        method: "POST",
                        dataType: 'json',
                        data: {
                            downvote_comment: true,
                            comment_id: comment_id
                        },
                        success: function(response) {
                            if (response.success) {
                                btn.replaceWith(`<button data-id="${comment_id}" class="upvote-btn fa fa-star-o w3-btn w3-yellow w3-hover-opacity w3-round w3-padding-small"></button>`);
                            } else if (response.error) {
                                $("#ajax-error").text(response.error).show(500).delay(5000).hide(500);
                            }
                        },
                        error: (jqXHR, textStatus, errorThrown) => {
                            console.error("error:", textStatus, errorThrown, jqXHR);
                            const response = JSON.parse(jqXHR.responseText);
                            alert(response.error);
                        }
                    });
                });

                $(document).on("click", "#post-comment", function() {
                    event.preventDefault();

                    fetchCSRFToken(function() {
                        const btn = $(this);
                        const commentBox = $("[data-testid='gr8-comment-box--comment-value']").val();
                        console.log(commentBox);
                        const prevCommentBtnText = $("#comment-btn-text").html();
                        const commentBtnText = $("#comment-btn-text");
                        const errorElm = $("#ajax-error");
                        const csrf = window.csrf_token;

                        commentBtnText.html('<img src="/img/loading.gif" style="width: 20px; height: 20px;" />');
                        btn.prop("disabled", true);

                        $.ajax({
                            url: "/ajax/build",
                            method: "POST",
                            dataType: 'json',
                            data: {
                                comment: true,
                                buildId: <?php echo $_GET['id'] ?>,
                                commentbox: commentBox,
                                csrf_token: csrf
                            },
                            success: function(response) {
                                if (response.success) {
                                    commentBtnText.html(prevCommentBtnText);
                                    btn.prop("disabled", false);
                                    window.location.reload();
                                } else {
                                    errorElm.text(response.error).show(500).delay(5000).hide(500);
                                }
                            },
                            error: (jqXHR, textStatus, errorThrown) => {
                                commentBtnText.html(prevCommentBtnText);
                                btn.prop("disabled", false);
                                console.error("error:", textStatus, errorThrown, jqXHR);
                                const response = JSON.parse(jqXHR.responseText);
                                errorElm.text(response.error).show(500).delay(5000).hide(500);
                            }
                        });
                    });
                });
            });
        });
    </script>

    <?php include 'navbar.php' ?>

    <?php if (isset($error)) { ?>
        <h4 class="message w3-card-2 w3-padding w3-round w3-red"><?php echo $error ?></h4><br />
        <?php exit; ?>
    <?php } ?>

    <?php if (isset($message)) { ?>
        <h4 class="message w3-card-2 w3-padding w3-round w3-light-grey"><?php echo $message ?></h4><br />
    <?php } ?>

    <?php if ($loggedin === true) { ?>
        <form id="downvote" action="/ajax/build" method="post"><input type="hidden" value="<?php echo $model_id ?>" name="model_id"></form>
        <form id="upvote" action="/ajax/build" method="post"><input type="hidden" value="<?php echo $model_id ?>" name="model_id"></form>
    <?php } ?>

    <main id="wrapper">
        <div id="ajax-error" style="display: none;" class="w3-bottom w3-padding w3-round w3-red"></div>

        <figure class="model-screenshot">
            <iframe id="model-embed" src="/viewer.html?model=<?php echo urlencode($_GET['id']) ?>" class="w3-border w3-card-2"></iframe>

            <figcaption data-testid="description" class="gr8-theme w3-light-grey w3-card-2 w3-border w3-padding" style="width: 65%">
                <header>
                    <h3 class="creation-page-title" id="name"><?php echo $data['name'] ?></h3>

                    <p class="creation-page-meta">By 
                        <b class="meta-author"><a id="user-link" class="<?php echo $data['model_admin'] === true ? 'w3-text-red w3-hover-text-yellow' : ''; ?>" href="/@<?php echo urlencode($data['username']) ?>"><i class="fa fa-at" aria-hidden="true"></i><?php echo $data['username'] ?></a></b>
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

                    <?php if (loggedin()) { ?>
                        <?php if ($data['voted'] === true) { ?>
                            <div class="tooltip" id="data-unlike-creation">
                                <span class="w3-tag w3-blue tooltiptext">Unfavorite this creation</span>
                                &nbsp;<button class="unlike-creation w3-btn w3-red w3-hover-opacity w3-padding-small w3-border w3-border-orange"><span class="fa fa-star"></span>
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

                        <?php if ($current_user->admin === true) { ?>
                            <div class="tooltip" id="data-delete-model">
                                <span class="w3-tag w3-blue tooltiptext"><?php echo $data['is_removed'] ? 'Restore' : 'Delete'; ?> this creation as an admin</span>
                                <button onclick='document.getElementById("delete-model").style.display="block"' name="delete" class="w3-btn w3-red w3-hover-opacity w3-padding-small w3-border w3-border-pink" />
                                <i class="fa fa-trash" aria-hidden="true"></i> <?php echo $data['is_removed'] ? 'Restore' : 'Delete'; ?>
                                </button>&nbsp;
                            </div>
                        <?php } ?>

                        <?php if (trim($current_user->id) === trim($data['userid'])) { ?>
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
                            <button onclick='document.getElementById("report").style.display="block"' name="flag" class="w3-btn w3-red w3-hover-opacity w3-padding-small w3-border w3-border-pink" />
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
                <p>
                <div class="gr8-theme w3-light-grey w3-round w3-padding"><?php echo $data['message'] ?></div>
                </p>
            <?php } elseif (loggedin()) { ?>
                <div id='comment-form w3-half'>
                    <div id='post'>
                        <textarea data-testid="gr8-comment-box--comment-value" name='comment-box' id='comment-box' class='w3-input w3-half' placeholder='Add a comment... (@username mentions someone, BBcode supported)' rows='auto' cols='40'></textarea>
                    </div>
                    <div class="w3-margin-top">
                        <button id='post-comment' class='w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo'>
                            <span id="comment-btn-text"><i class="fa fa-paper-plane-o" aria-hidden="true"></i> Post comment</span>
                        </button>
                    </div>
                </div>
            <?php } else { ?>
                <div>
                    <a href="/acc/login" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo">Login to comment</a>
                </div>
            <?php } ?>
        </div>
    </main>

    <div id="data-comment-wrapper">
        <?php
        $comment_data = json_decode(fetch_comments($model_id, $_SESSION['csrf']), true);

        if ($comment_data && is_array($comment_data)) {
            echo '<h4><span class="fa fa-comments-o" aria-hidden="true"></span>&nbsp;<span id="comment-count">' . $data['comments'] . '</span> comments</h4><hr />';

            foreach ($comment_data as $comment) {
        ?>

                <?php if (!empty($comment['message'])) { ?>
                    <div class="gr8-theme w3-light-grey w3-padding-small"><?php echo $comment['message'] ?></div>
                <?php } ?>

                <div id="comment<?php echo $comment['id'] ?>" data-testid="<?php echo $comment['id'] ?>" class="w3-row w3-section">
                    <div class="w3-col" id="comment-profile-picture" style="width: 50px;">
                        <img class="w3-bar-item w3-circle w3-card-2" width="50px" height="50px" src="<?php echo $comment['picture'] ? $comment['picture'] : '/img/no_image.png' ?>">
                    </div>

                    <div data-testid="gr8-comment-divider" class="w3-hide-small w3-col" style="width: max-content; height: max-content;">
                        <i class="w3-large w3-text-white fa fa-play fa-rotate-180"></i>
                    </div>

                    <div id="comment-text" class="w3-col w3-card-2" style="width: 60%;">
                        <article class="gr8-theme w3-light-grey w3-padding-small w3-round w3-border w3-border-grey" style="min-height: 75px;">
                            <header class="w3-padding-bottom">
                                <b>
                                    <?php $is_deleted = User::isDeleted($comment['userid']);
                                    if (!$is_deleted) { ?>
                                        <a href="/@<?php echo urlencode($comment['username']) ?>"
                                            class="<?php echo $comment['user_admin'] === true ? 'w3-text-red w3-hover-text-yellow' : ''; ?>">
                                        <?php }
                                    echo $comment['username'];
                                    if (!$is_deleted) { ?>
                                        </a>
                                    <?php } ?>
                                </b>

                                <span class="w3-mobile w3-right">
                                    <?php echo $comment['is_op'] ? '<b id="is-op" title="Original Poster">OP</b> - ' : '' ?>
                                    <time title="<?php echo $comment['date'] ?>" datetime="<?php echo $comment['date'] ?>"><?php echo $comment['date'] ?></time> -
                                    <span id="votes"><?php echo $comment['votes'] ?> favorites</span>
                                </span>
                            </header>

                            <span class="w3-padding-bottom" style="word-wrap: break-word; white-space: normal;">
                                <?php if (!empty($comment['comment'])) { ?>
                                    <?php echo $comment['comment'] ?>
                                <?php } else { ?>
                                    <i>Empty message</i>
                                <?php } ?>
                            </span><br />

                            <?php if ($comment['voted'] === false) { ?>
                                <div class="tooltip">
                                    <span class="w3-blue tooltiptext">Favorite Comment</span>
                                    <button data-id="<?php echo $comment['id'] ?>" class="upvote-btn fa fa-star-o w3-btn w3-yellow w3-hover-opacity w3-round w3-padding-small"></button>
                                </div>
                            <?php } elseif ($comment['voted'] === true) { ?>
                                <div class="tooltip">
                                    <span class="w3-blue tooltiptext">Unfavorite Comment</span>
                                    <button data-id="<?php echo $comment['id'] ?>" class="downvote-btn fa fa-star w3-btn w3-pink w3-hover-opacity w3-round w3-padding-small"></button>
                                </div>
                            <?php } ?>
                        </article>
                    </div>
                </div>
        <?php
            }
        } else {
            echo "<h4>No comments yet.</h4>";
        }
        ?>
    </div>
    <?php
    echo '<br /><br />';
    include('linkbar.php');
    ?>
</body>

</html>