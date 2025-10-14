<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/build.php';
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

if ($conn->connect_error) {
	exit($conn->connect_error);
}

$model_id = $conn->real_escape_string($_GET['id']);

if(!isset($model_id)) {
    header("HTTP/1.0 404 Not Found");
    exit;
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';

$data = json_decode(fetch_build($model_id, $_SESSION['csrf']), true);

if ($data === null) {
    exit('no data returned');
}

if($data['message'] != "OK") {
    $error = $data['message'];
    $data['name'] = 'Creation';
    $data['username'] = 'User';
}

if ($_POST['report']) {
    header('Content-Type: application/json');

    if ($_SESSION['csrf'] === $_POST['csrf_token']) {
        if (isset($_COOKIE['token']) && $tokendata) {
            $id = (int)$token['user'];
            $report_id = bin2hex(random_bytes(16));
            $date = date("Y-m-d H:i:s");
            $model_id = (int)htmlspecialchars($_POST['model_id']);

            /*$reasons = isset($_POST['reason']) ? $_POST['reason'] : [];
            $reasons = array_map('htmlspecialchars', $reasons);
            
            if (!empty($_POST['other'])) {
                $reasons[] = htmlspecialchars($_POST['other']);
            }

            $reasonString = implode(', ', $reasons); */

            if (!empty($_POST['other']) || isset($_POST['reason']) && $_POST['reason'] === "something-else") {
                $reason = htmlspecialchars($_POST['other']);
            } elseif(isset($_POST['reason'])) {
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

if ($_POST['delete_model']) {
    header('Content-Type: application/json');

    if ($_SESSION['csrf'] === $_POST['csrf_token']) {
        if (isset($_COOKIE['token']) && $users_row && trim($users_row['id']) === trim($data['userid'])) {                
            $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
            $model_id = (int)$_POST['model_id'];

            $sql = "SELECT * FROM model WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $model_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $modelFile = basename($row['model']);
                $screenshotFile = basename($row['screenshot']);
                $crePath = realpath(__DIR__ . "/cre") . '/';

                if (file_exists($crePath . $modelFile)) {
                    unlink($crePath . $modelFile);
                }

                if (file_exists($crePath . $screenshotFile)) {
                    unlink($crePath . $screenshotFile);
                }

                $sql2 = "DELETE FROM model WHERE id = ?";
                $stmt2 = $conn->prepare($sql2);
                $stmt2->bind_param("i", $model_id);

                if($stmt2->execute()) {
                    $stmtVotes = $conn->prepare("DELETE FROM votes WHERE creation = ?");
                    $stmtVotes->bind_param("i", $model_id);
                    $stmtVotes->execute();

                    $stmtComments = $conn->prepare("DELETE FROM comments WHERE model = ?");
                    $stmtComments->bind_param("i", $model_id);
                    $stmtComments->execute();
                    
                    echo json_encode(['success' => 'Creation deleted']);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $data['name'] ?> by <?php echo $data['username'] ?></title>
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
                <input type="checkbox" name="reason" value="violent" class="w3-check"> <label>Violent</label><br />
                <input type="checkbox" name="reason" value="misinformation" class="w3-check"> <label>Misinformation</label><br />
                <input type="checkbox" name="reason" value="inappropriate-content" class="w3-check"> <label>Inappropriate content</label><br />
                <input type="checkbox" name="reason" value="harrasing-me" class="w3-check"> <label>Harassing me</label><br />
                <input type="checkbox" name="reason" value="spam" class="w3-check"> <label>Spam</label><br />
                <input type="checkbox" name="reason" value="something-else" class="w3-check" id="otherReasonToggle"> <label>Something else</label><br /><br />
                
                <textarea class="w3-input w3-card-2 w3-hover-shadow w3-mobile w3-round w3-hide" name="other" id="otherReason" placeholder="Explain more..." rows="4"></textarea><br />

                <input type="hidden" name="user" value="<?php echo $token['user']; ?>">
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
                <h2>Are you sure you want to delete this creation?</h2>
                <b>Deleting a creation does not delete comments, comment votes, or votes on it.</b><br />

                <input type="hidden" name="model_id" value="<?php echo $model_id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf']; ?>">
                <input type="hidden" name="delete_model" value="1">

                <span class="w3-btn w3-large w3-white w3-hover-blue w3-round-small" onclick="$('#delete-model').hide();">Close</span>
                <button type="submit" class="w3-btn w3-large w3-white w3-hover-red w3-round-small">Delete</button>
            </form>
        </div>
    </div>
</div>

<style>
.upvote-btn, .downvote-btn {
    transition: all 0.5s ease-in-out;
    z-index: 999999;
}

.upvote-btn:hover {
    transform: translateY(-3px);
}

.downvote-btn:hover {
    transform: translateY(3px);
}

@media screen and (max-width: 600px) {
    #data-profile-picture, #data-stat-label {
        display: none !important;
    }
    #data-stats span {
        display: inline;
    }
    #data-stat-break {
        display: block;
    }
    #data-model-embed {
        width:80vw !important;
    }
}

.loader {
    position: fixed;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    border: 16px solid #f3f3f3;
    border-radius: 50%;
    border-top: 16px solid #3498db;
    width: 120px;
    height: 120px;
    -webkit-animation: spin 2s linear infinite;
    animation: spin 2s linear infinite;
    z-index: 999999999;

    display: none;
}

@-webkit-keyframes spin {
  0% { -webkit-transform: rotate(0deg); }
  100% { -webkit-transform: rotate(360deg); }
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function () {
    $(document).ready(function() {
        let embed_style = "width:50vw;height:50vh;";
        let embed_class = "w3-border w3-border-black w3-round w3-card-2 w3-hover-shadow";
        let embed_model = "<?php echo urlencode($_GET['id']) ?>";

        $("#data-model-embed").html(`<iframe id="data-model-embed" src="/viewer.html?model=${embed_model}&t=<?php echo md5(time()) ?>" style="${embed_style}" class="${embed_class}">`);

        $("#otherReasonToggle").change(function() {
            $("#otherReason").toggleClass("w3-hide", !this.checked);
        });

        function fetchCSRFToken(callback) {
            $.get("/ajax/config.php", { get_csrf_token: 1 }, function(data) {
                $("#csrf_token").val(data.csrf_token);
                callback();
            }, "json");
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
                        alert("An error occurred. Please try again.");
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
                        alert("An error occurred. Please try again.");
                    }
                });
            });
        });

        $(document).on("click", ".like-creation", function() {
            event.preventDefault();
            let btn = $(this);

            $.ajax({
                url: "/ajax/build",
                method: "POST",
                data: { upvote: true, model_id: embed_model },
                success: function(response) {
                    console.log(response.success);
                    btn.replaceWith(`<button class="unlike-creation w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-orange"><span class="fa fa-arrow-down"></span>Unlike</button>`);
                },
                error: (jqXHR, textStatus, errorThrown) => {
                    console.error("error:", textStatus, errorThrown, jqXHR);
                    const response = JSON.parse(jqXHR.responseText);
                    alert(response.error);
                }
            });
        });

        $(document).on("click", ".unlike-creation", function() {
            event.preventDefault();
            let btn = $(this);

            $.ajax({
                url: "/ajax/build",
                method: "POST",
                data: { downvote: true, model_id: embed_model },
                success: function(response) {
                    console.log(response.success);
                    btn.replaceWith(`<button class="like-creation w3-btn w3-yellow w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-orange"><span class="fa fa-arrow-up"></span>Like</button>`);
                },
                error: (jqXHR, textStatus, errorThrown) => {
                    console.error("error:", textStatus, errorThrown, jqXHR);
                    const response = JSON.parse(jqXHR.responseText);
                    alert(response.error);
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
                data: { upvote_comment: true, comment_id: comment_id },
                success: function(response) {
                    console.log(response.success);
                    btn.replaceWith(`<button data-id="${comment_id}" class="downvote-btn fa fa-arrow-down w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-orange"></button>`);
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
                data: { downvote_comment: true, comment_id: comment_id },
                success: function(response) {
                    console.log(response.success);
                    btn.replaceWith(`<button data-id="${comment_id}" class="upvote-btn fa fa-arrow-up w3-btn w3-yellow w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-orange"></button>`);
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
                const commentBox = $("#comment-form textarea[name='comment-box']").val();
                const commentBtnText = $("#comment-btn-text");
                const errorElm = $("#ajax-error");
                const csrf = $("#comment-form input[name='csrf_token']").val();

                commentBtnText.html('<img src="/img/loading.gif" style="width: 20px; height: 20px;" />');
                btn.prop("disabled", true);

                $.ajax({
                    url: "/ajax/build",
                    method: "POST",
                    data: { comment: true, buildId: <?php echo $_GET['id'] ?>, commentbox: commentBox, csrf_token: csrf },
                    success: function(response) {
                        if (response.success) {
                            commentBtnText.html('Comment');
                            btn.prop("disabled", false);
                            alert('Comment posted!');
                            window.location.reload();
                        } else {
                            errorElm.text(response.error).show(500).delay(5000).hide(500);
                        }
                    },
                    error: (jqXHR, textStatus, errorThrown) => {
                        commentBtnText.html('Comment');
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

<?php 
    include('navbar.php'); 
?>

<?php if(isset($error)) { ?>
    <div class="message w3-padding w3-round w3-red"><?php echo $error ?></div><br /><br />
    <?php exit; ?>
<?php } ?>

<?php if(isset($message)) { ?>
    <div class="message w3-padding w3-round w3-light-grey"><?php echo $message ?></div><br /><br />
<?php } ?>

<?php if($loggedin === true) { ?>
    <form id="downvote" action="/ajax/build" method="post"><input type="hidden" value="<?php echo $model_id ?>" name="model_id"></form>
    <form id="upvote" action="/ajax/build" method="post"><input type="hidden" value="<?php echo $model_id ?>" name="model_id"></form>
<?php } ?>

<main id="wrapper">
    <div id="ajax-error" style="display: none;" class="w3-top w3-padding w3-round w3-red"></div>

    <center><header class="loader"></header></center>
    <header>
        <h2 id="data-name"><?php echo $data['name'] ?></h2>
        <h4 id="data-stats">
            <i class="fa fa-clock-o" aria-hidden="true"></i>Posted&nbsp;<span title="<?php echo $data['date'] ?>"><?php echo time_ago($data['date']) ?></span><br />
            <i class="fa fa-user-o" aria-hidden="true"></i>By
            	<?php if($data['userid'] === 0) { ?>
            		<span id="data-user-link"><?php echo $data['username'] ?></span>
                <?php } elseif($data['model_admin'] === '1') { ?>
                	<a id="data-user-link" class="w3-text-red w3-hover-text-yellow" href="/user/<?php echo $data['userid'] ?>"><?php echo $data['username'] ?></a>
                <?php } else { ?>
                	<a id="data-user-link" href="/user/<?php echo $data['userid'] ?>"><?php echo $data['username'] ?></a>
                <?php } ?>
            <br />
            <i class="fa fa-arrow-circle-o-up" aria-hidden="true"></i><?php echo $data['likes'] ?>&nbsp;likes<br />
            <i class="fa fa-eye" aria-hidden="true"></i><?php echo $data['views'] ?>&nbsp;views<br />
        </h4>
    </header>

    <figure class="data-model-screenshot" style="margin: 20px !important;">
        <p><img id="data-model-screenshot" src="/cre/<?php echo $data['screenshot'] ?>" style="width:50vw;height:50vh;border:1px solid;border-radius:15%;display:none;" loading='lazy'></p>
        <p id="data-model-embed">Your browser failed to load the viewer. Please make sure you didn't stop the page from loading, Javascript is enabled, and your browser is up-to-date.</p>
        <figcaption class="gr8-theme w3-card-2 w3-hover-shadow w3-light-grey w3-padding w3-large w3-round">
        <?php if(!empty($data['description'])) { ?>
            <h4><span id="data-description"><?php echo $data['description'] ?></span><br /></h4>
        <?php } ?>
        </figcaption>
    </figure>

    <div class="w3-dropdown-click" style="z-index: 999;">
        <div class="tooltip">
            <span class="w3-tag w3-round w3-blue tooltiptext"><i class="fa fa-info-circle" aria-hidden="true"></i>Download a model to save it</span>
            <button onclick="dropdown()" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo">Download...</button>
        </div>
        <div id="gr8-dropdown" class="w3-dropdown-content w3-bar-block w3-border" style="z-index: 999;">
            <a id="data-build-download" class="w3-bar-item w3-btn w3-hover-blue w3-border" href="/cre/<?php echo $data['model'] ?>" download="<?php echo htmlspecialchars($data['name']) ?>.json">creation</a>
            <a id="data-screenshot-download" class="w3-bar-item w3-btn w3-hover-blue w3-border" href="/cre/<?php echo $data['screenshot'] ?>" download="<?php echo htmlspecialchars($data['name']) ?>.png">screenshot</a>
        </div>
    </div>

    <?php if($loggedin === true) { ?>
        <?php if ($data['voted'] === true) { ?>
            <div class="tooltip" id="data-unlike-creation">
                <span class="w3-tag w3-round w3-blue tooltiptext"><i class="fa fa-info-circle" aria-hidden="true"></i>Remove your vote from this creation</span>
                &nbsp;<button class="unlike-creation w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-orange"><span class="fa fa-arrow-down"></span>Unlike</button>&nbsp;
            </div>
        <?php } else { ?>
            <div class="tooltip" id="data-like-creation">
                <span class="w3-tag w3-round w3-blue tooltiptext"><i class="fa fa-info-circle" aria-hidden="true"></i>Vote on this creation and support the creator</span>
                &nbsp;<button class="like-creation w3-btn w3-yellow w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-orange"><span class="fa fa-arrow-up"></span>Like</button>&nbsp;
            </div>
        <?php } ?>

        <?php if(trim($token['user']) === trim($data['userid'])) { ?>
            <div class="tooltip" id="data-delete-model">
                <span class="w3-tag w3-round w3-blue tooltiptext"><i class="fa fa-info-circle" aria-hidden="true"></i>Delete this creation</span>
                <button onclick=document.getElementById("delete-model").style.display="block" name="delete" class="w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink" />
                    <i class="fa fa-trash" aria-hidden="true"></i>Delete
                </button>&nbsp;
            </div>
        <?php } ?>

        <div class="tooltip" id="data-report-model">
            <span class="w3-tag w3-round w3-blue tooltiptext"><i class="fa fa-info-circle" aria-hidden="true"></i>Report this creation to moderators</span>
            <button onclick=document.getElementById("report").style.display="block" name="flag" class="w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink" />
                <i class="fa fa-flag" aria-hidden="true"></i>Report
            </button>&nbsp;
        </div>
    <?php } ?>
        
    <p><span class="w3-large">Embed:</span></p><p><textarea class="w3-card-2 w3-round" rows='2' cols='65' readonly>http://www.gr8brik.rf.gd/new-viewer.html?model=<?php echo htmlspecialchars($data['model']) ?></textarea></p><hr />

    <div class="w3-container">
        <?php if($data['message'] != "OK") { ?>
            <p><div class="gr8-theme w3-light-grey w3-round w3-padding"><?php echo $data['message'] ?></div></p>
        <?php } elseif($loggedin === true) { ?>
            <div id='comment-form'>
                <div id='post'>
                    <textarea name='comment-box' id='comment-box' class='w3-input w3-card-2 w3-hover-shadow w3-mobile w3-half w3-round' placeholder='add a comment... (type @username to mention someone)' rows='4' cols='40'></textarea>
                </div><br />
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf']; ?>">
                <button id='post-comment' class='w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo'>
                    <span id="comment-btn-text">Comment</span>
                </button>
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
        $comments = 0;

        if ($comment_data && is_array($comment_data)) {
            $comments = count($comment_data);
            echo '<h4><span class="fa fa-comments-o" aria-hidden="true"></span>&nbsp;<span id="data-comment-count-wrapper">' . $comments . '</span> comments</h4><hr />';

            foreach ($comment_data as $comment) {
    ?>
        <?php if(!empty($comment['message'])) { ?>
            <p><div class="gr8-theme w3-light-grey w3-round w3-padding"><?php echo $comment['message'] ?></div></p>
        <?php } ?>
        <div id="comment<?php echo $comment['id'] ?>" class="w3-row w3-margin-bottom">
            <div class="w3-col" style="width: 50px;">
                <img class="w3-bar-item w3-circle w3-card-2" width="50px" height="50px" src="/acc/users/pfps/<?php echo $comment['userid'] ?>.jpg">
            </div>
            <div class="w3-hide-small w3-col w3-margin" style="width: 1px;">
                <i class="w3-large w3-text-white gr8-theme fa fa-play fa-rotate-180" xstyle="font-size: 15px;"></i>
            </div>
            <div class="w3-col w3-card-2 w3-hover-shadow" style="width: 75%;">
                <article class="gr8-theme w3-light-grey w3-padding" style="min-height: 75px;">
                    <header class="w3-padding-bottom">
                        <?php if($comment['userid'] != 0) { ?>
                        <b>
                            <a href="/user/<?php echo $comment['userid'] ?>" 
                               class="<?php echo $comment['user_admin'] === '1' ? 'w3-text-red w3-hover-text-yellow' : '' ?>">
                                <?php echo $comment['username'] ?>
                            </a>
                        </b>
                        <?php } else { ?>
                        	<b title="User has either deleted their account or are banned"><?php echo $comment['username'] ?></b>
                        <?php } ?>
                        <span class="w3-mobile w3-right">
                            <time datetime="<?php echo $comment['date'] ?>"><?php echo $comment['date'] ?></time> -
                            <span id="votes"><?php echo $comment['votes'] ?> votes</span>
                        </span>
                    </header>
                    <span class="w3-padding-bottom" style="word-wrap: break-word; white-space: normal;">
                        <?php echo $comment['comment'] ?>
                    </span><br />

                    <?php if ($loggedin === true) { ?>
                        <?php if ($comment['voted'] === false) { ?>
                            <div class="tooltip">
                                <span class="w3-tag w3-round w3-blue tooltiptext">
                                    <i class="fa fa-info-circle" aria-hidden="true"></i>&nbsp;Upvote Comment
                                </span>
                                <button data-id="<?php echo $comment['id'] ?>" class="upvote-btn fa fa-arrow-up w3-btn w3-yellow w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-orange"></button>
                            </div>
                        <?php } elseif ($comment['voted'] === true) { ?>
                            <div class="tooltip">
                                <span class="w3-tag w3-round w3-blue tooltiptext">
                                    <i class="fa fa-info-circle" aria-hidden="true"></i>&nbsp;Downvote Comment
                                </span>
                                <button data-id="<?php echo $comment['id'] ?>" class="downvote-btn fa fa-arrow-down w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-orange"></button>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <span><b>Please login to upvote.</b></span>
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