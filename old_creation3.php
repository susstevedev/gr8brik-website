<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
if ($conn->connect_error) {
	exit($conn->connect_error);
}	
$model_id = $conn->real_escape_string($_GET['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Creation</title>
    <?php include 'header.php' ?>
</head>

<body class="w3-light-blue w3-container">

<div style="z-index: 999999" id="report" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-24 w3-light-grey w3-center w3-round w3-padding">
		<div class="w3-container">
			<span onclick="document.getElementById('report').style.display='none'" class="w3-closebtn w3-red w3-hover-white w3-padding w3-round w3-display-topright">&times;</span>
			<form method="post" action="/report.php">		
			<h2>Why do you want to flag this creation?</h2>
            <b>You can only report a creation when it violates the <a href="/rules?src=creation" target="_blank">Rules</a>.</b><br />
			<input type="checkbox" name="reason" value="violent" class="w3-check">
			<label class="w3-validate">Violent</label><br />
			<input type="checkbox" name="reason" value="misinformation" class="w3-check">
			<label class="w3-validate">Misinformation</label><br />
			<input type="checkbox" name="reason" value="inappropriate-content" class="w3-check">
			<label class="w3-validate">Inappropriate content</label><br />
			<input type="checkbox" name="reason" value="harrasing-me" class="w3-check">
			<label class="w3-validate">Harrasing me</label><br />
		    <input type="checkbox" name="reason" value="something-else" class="w3-check" onclick="document.getElementById('other').classList.toggle('w3-hide');">
			<label class="w3-validate">Something else</label><br />
			<input style="display: none;" type="hidden" value="<?php echo $token['user'] ?>" name="user" readonly>
			<input style="display: none;" type="hidden" value="<?php echo $model_id ?>" name="model_id" readonly>
            <input style="display: none;" type="hidden" value="<?php echo $_SESSION['csrf'] ?>" name="csrf_token" readonly><br />

			<center><textarea class='w3-card-24 w3-input w3-round w3-hide' name="other" id='other' placeholder='Expain more...' rows='4' cols='50'></textarea><br /></center>
		<span name="close" class="w3-btn w3-large w3-white w3-hover-blue w3-round" onclick="document.getElementById('report').style.display='none';">Close</span> 
							<input type="submit" value="Report" name="report_1" class="w3-btn w3-large w3-white w3-hover-red w3-round">
						</form>
					</div>
				</div>
			</div>

            <!--<div id="delete" class="w3-modal">
				<div class="w3-modal-content w3-animate-top w3-card-24 w3-light-grey w3-center">
					<div class="w3-container">
						<span onclick="document.getElementById('delete').style.display='none'" class="w3-closebtn w3-red w3-hover-white w3-padding w3-display-topright">&times;</span>
                        <form method='post' action='/report.php?<?php echo $model ?>'>
							<h2>Are you sure you want to delete your creation (<?php echo $name ?>)?</h2>
							<span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('delete').style.display='none'">CLOSE</span> 
							<input type="submit" value="DELETE" name="delete" class="w3-btn w3-large w3-white w3-hover-red">
						</form>
					</div>
				</div>
			</div>-->

<style>
.upvote-btn, .downvote-btn {
    transition: all 0.5s ease-in-out;
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
</style>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        $(document).ready(function() {
            const $success = $("#success");
            const $consoleErrorBox = $(".consoleErrorBox");
            const $consoleError = $(".consoleError");
            const $wrapper = $("#wrapper");
            const $commentForm = $("#commentForm");
            const $dataCommentWrapper = $("#data-comment-wrapper");
            const $dataCommentCountWrapper = $("#data-comment-count-wrapper");

            $success.hide();
            $consoleErrorBox.hide();
            $wrapper.hide();

            const urlParams = new URLSearchParams(window.location.search);
            const modelId = <?php echo $model_id ?>;
            const commentSent = urlParams.get('sentComment');

            if (commentSent === '1') {
                $success.show().text("Comment posted.");
            }

            if (!modelId) {
                $consoleErrorBox.show();
                $consoleError.text("Invalid model ID.");
                return;
            }

            fetchBuildData(modelId);
            fetchComments(modelId);

            $("#postComment").click(handleCommentSubmit);

            function fetchBuildData(modelId) {
                $.ajax({
                    url: "/ajax/build",
                    method: "GET",
                    data: { fetch: true, buildId: modelId },
                    success: handleBuildDataSuccess,
                    error: handleAjaxError
                });
            }

            function fetchComments(modelId) {
                $.ajax({
                    url: "/ajax/build",
                    method: "GET",
                    data: { build_comments: true, buildId: modelId },
                    success: function(response) {
                        if (response.count != 0 || response.count === null) {
                            console.log(response);
                            $("#data-comment-wrapper").empty();
                            response.forEach(comment => {
                                const postDiv = document.createElement('div');
                                postDiv.id = "comment-wrapper";
                                let vote_btn = '';
                                if (comment.loggedin === true) {
                                    vote_btn += comment.voted === false
                                        ? `<div class="tooltip">
                                        <span class="w3-tag w3-round w3-blue tooltiptext">
                                        <i class="fa fa-info-circle" aria-hidden="true"></i>&nbsp;Upvote Comment</span>
                                        <button data-id="${comment.id}" class="upvote-btn fa fa-arrow-up w3-btn w3-yellow w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-orange"></button>`
                                        : '';
                                    vote_btn += comment.voted === true
                                        ? `<div class="tooltip">
                                        <span class="w3-tag w3-round w3-blue tooltiptext">
                                        <i class="fa fa-info-circle" aria-hidden="true"></i>&nbsp;Downvote Comment</span>
                                        <button data-id="${comment.id}" class="downvote-btn fa fa-arrow-down w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-orange"></button>`
                                        : '';
                                } else {
                                    vote_btn = '<span><b>Please login to upvote.</b></span>';
                                }
                                postDiv.innerHTML = `
                                    <div id="post"><img class="pfp-post w3-card-2 w3-hover-shadow w3-light-grey" src="/acc/users/pfps/${comment.userid}.jpg">
                                    <article class="w3-card-2 w3-hover-shadow w3-light-grey w3-padding-tiny w3-round-small" style="width:75%">
                                        <header><a href="/user/${comment.userid}">${comment.username}</a>
                                        <span class="w3-right"><time datetime="${comment.date}">${comment.date}</time> - <span id="votes">${comment.votes} votes</span></span></header>
                                        <pre id="comment">${comment.comment}</pre>
                                        ${vote_btn}
                                    </article></div><br />
                                `;

                                $("#data-comment-wrapper").append(postDiv);
                                $("#data-comment-count-wrapper").text(comment.count);
                            });
                        }
                    }, 
                    error: function(jqxhr, text_status, error_thrown) {
                        console.error("error loading comments: ", text_status, error_thrown, jqxhr);
                        const response = JSON.parse(jqxhr.responseText);
                        $("#data-comment-count-wrapper").text('0');
                    }
                });
            }

            function handleBuildDataSuccess(response) {
                console.log(response);
                document.title = `${response.name} by ${response.username} - Gr8brik`;

                $("#data-posted").text(response.date);
                $("#data-name").text(response.name);
                $("#data-user-link").attr("href", `/user/${response.userid}`);
                $("#data-username").text(response.username);
                $("#data-profile-picture").attr("src", `/acc/users/pfps/${response.userid}.jpg`);
                $("#data-views").text(response.views);
                $("#data-likes").text(response.likes);

                const $description = $("#data-description");
                if (response.description) {
                    $description.html(response.description);
                } else {
                    $description.hide();
                }

                if (response.screenshot) {
                    $("#data-model-screenshot").attr("src", `/cre/${response.screenshot}`);
                    $("#data-screenshot-download").attr("download", `${btoa(response.screenshot) + '.png'}`).attr("href", `/cre/${response.screenshot}`);
                }
                $("#data-model-embed").html(`<iframe id='data-model-embed' src='/new-viewer.html?model=${response.model}' style='width:50vw;height:50vh;border:1px solid;border-radius:15%;'>`);
                $("#data-build-download").attr("download", `${btoa(response.model) + '.json'}`).attr("href", `/cre/${response.model}`);

                if (response.myUserId != false) {
                    if (response.userid === response.myUserId) {
                        $("#data-report-model").remove();
                    } else {
                        $("#data-delete-model").remove();
                    }

                    if (response.voted === true) {
                        $("#data-vote").append('&nbsp;<input form="downvote" class="w3-btn w3-yellow w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-orange" type="submit" value="Unlike" name="downvote">&nbsp;');
                    } else {
                        $("#data-vote").append('&nbsp;<input form="upvote" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" type="submit" value="Like" name="upvote">&nbsp;');
                    }

                    $commentForm.show();
                    $("#data-user-actions").show();
                } else {
                    $("#data-delete-model").remove();
                    $("#data-report-model").remove();
                    $commentForm.remove();
                }

                $wrapper.show();
            }

            function handleCommentSubmit(event) {
                event.preventDefault();
                const commentBox = $("#commentForm textarea[name='commentBox']").val();
                $("#commentButtonText").html('<img src="/img/loading.gif" style="width: 20px; height: 20px;" />');
                $("#postComment").prop("disabled", true);

                $.ajax({
                    url: "/ajax/build",
                    method: "POST",
                    data: { comment: true, buildId: modelId, commentbox: commentBox },
                    success: function(response) {
                        $("#commentButtonText").html('Comment');
                        $("#postComment").prop("disabled", false);
                        if(!alert(response.success)) {
                            window.location.reload();
                        }
                    },
                    error: (jqXHR, textStatus, errorThrown) => {
                        $("#commentButtonText").html('Comment');
                        $("#postComment").prop("disabled", false);
                        console.error("error:", textStatus, errorThrown, jqXHR);
                        const response = JSON.parse(jqXHR.responseText);
                        alert(response.error);
                    }
                });
            }

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

            function handleAjaxError(jqXHR, textStatus, errorThrown) {
                console.error("AJAX Error:", textStatus, errorThrown, jqXHR);
                const response = JSON.parse(jqXHR.responseText);
                alert(response.error);
            }
        });
    });
</script>

<?php 
	include('navbar.php');
    require_once "com/bbcode.php";
    require_once "ajax/time.php";
    $bbcode = new BBCode;

?>
<main id="wrapper" style="display: none;">
    <span id='success' class='w3-padding w3-round w3-green w3-top'></span>
    <form id="downvote" action="/ajax/build" method="post"><input type="hidden" value="<?php echo $model_id ?>" name="model_id"></form>
    <form id="upvote" action="/ajax/build" method="post"><input type="hidden" value="<?php echo $model_id ?>" name="model_id"></form>

    <header>
        <h2 id="data-name"></h2>
    </header><hr />

    <figure class="data-model-screenshot" style="margin: 20px !important;">
        <p><img id="data-model-screenshot" src='/img/no_image.png' style="width:50vw;height:50vh;border:1px solid;border-radius:15%;display:none;" loading='lazy'></p>
        <p id="data-model-embed"></p>
        <figcaption class="gr8-theme w3-card-2 w3-hover-shadow w3-light-grey w3-padding w3-large w3-round">
            <h4>
                <span id="data-description"></span><br />
                <span id="data-stats" class="w3-border">
                    <i class="fa fa-clock-o" aria-hidden="true"></i><span id="data-stat-label">Posted&nbsp;</span><span id="data-posted"></span>
                    <b id="data-stat-break">&nbsp;-&nbsp;</b>
                    <i class="fa fa-user-o" aria-hidden="true"></i><span id="data-stat-label">By&nbsp;</span><a id="data-user-link" href="/user/0"><img id="data-profile-picture" style="width: 25px; height: 25px;" src="/img/no_image.png" /><span id="data-username"></span></a>
                    <b id="data-stat-break">&nbsp;-&nbsp;</b>
                    <i class="fa fa-arrow-circle-o-up" aria-hidden="true"></i><span id="data-likes"></span><span id="data-stat-label">&nbsp;likes</span>
                    <b id="data-stat-break">&nbsp;-&nbsp;</b>
                    <i class="fa fa-eye" aria-hidden="true"></i><span id="data-views"></span><span id="data-stat-label">&nbsp;views</span>
                </span>
            </h4>
        </figcaption>
    </figure>

    <div class="w3-dropdown-click" style="z-index: 999;">
        <div class="tooltip">
            <span class="w3-tag w3-round w3-blue tooltiptext"><i class="fa fa-info-circle" aria-hidden="true"></i>Download a model to save it</span>
            <button onclick="dropdown()" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo">Download...</button>
        </div>
        <div id="gr8-dropdown" class="w3-dropdown-content w3-bar-block w3-border" style="z-index: 999;">
            <a id="data-build-download" class="w3-bar-item w3-btn w3-hover-blue w3-border" href="" download="c.json">creation</a>
            <a id="data-screenshot-download" class="w3-bar-item w3-btn w3-hover-blue w3-border" href="" download="s.png">screenshot</a>
        </div>
    </div>

    <div id="data-vote" style="display: inline-block;"></div>

        <div class="tooltip" id="data-delete-model">
            <span class="w3-tag w3-round w3-blue tooltiptext"><i class="fa fa-info-circle" aria-hidden="true"></i>Delete not available right now</span>
            <button xonclick=document.getElementById("delete").style.display="block" name="delete" class="w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink" disabled/>Delete</button>&nbsp;
        </div>
        <div class="tooltip" id="data-report-model">
            <span class="w3-tag w3-round w3-blue tooltiptext"><i class="fa fa-info-circle" aria-hidden="true"></i>Report a model to Admins</span>
            <button onclick=document.getElementById("report").style.display="block" name="flag" class="w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink" />Flag</button>&nbsp;
        </div><br /><br />

        <div class="w3-container">
            <div id='commentForm'>
            <div id='post'>
            <textarea name='commentBox' id='commentBox' class='w3-input w3-card-2 w3-hover-shadow w3-mobile w3-half w3-round' placeholder='add a comment...' rows='4' cols='40'></textarea></div><br />
            <button id='postComment' class='w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo'>
                <span id="commentButtonText">Comment</span>
            </button></div>
            <h4><span class="fa fa-comments-o" aria-hidden="true"></span>&nbsp;<span id="data-comment-count-wrapper"></span> comments</h4>
        </div>
</main><hr />

<div id="data-comment-wrapper">
    <h4>Loading...</h4>
</div>
    <?php
        echo '<br /><br />';
        include('linkbar.php');
    ?>
</body>
</html>