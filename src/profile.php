<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/profile.php';

$use_username = false;
if(!isset($_GET['id'])) {
    if(!isset($_GET['name'])) {
    	header("HTTP/1.0 404 Not Found");
    	exit;
    } else {
        $use_username = true;
    }
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';

if(loggedin()) {
    $userid = $current_user->id;
}

$data = fetch_profile($_GET['id'] ?? urldecode($_GET['name']), $_SESSION['csrf'], $use_username);

if($data['message'] != null | !empty($data['message'])) {
    $error = $data['message'];
}

if(isset($data) && isset($data['userid'])) {
	$_GET['id'] = $data['userid'];
}

$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

if (isset($_POST['follow'])) {
    if(!isset($userid)) {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "Please login to follow this user";
    }

    $profile_id = (int)$_GET['id'];
    $time = time();

    if((int)$userid === $profile_id) {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "You cannot follow yourself";
    }

    if(!User::isVerified()) {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "User account is not verified";
    }

    if(!isset($error)) {
        $sql_follow = "INSERT INTO follow (userid, profileid, date) VALUES (?, ?, ?)";
        $stmt_follow = $conn->prepare($sql_follow);
        $stmt_follow->bind_param("iii", $userid, $profile_id, $time);
        $result = $stmt_follow->execute();
        $stmt_follow->close();

        if ($userid != $profile_id) {
            $content = $profile_id;
            $category = 1;

            $sql_notification = "INSERT INTO notifications (user, profile, timestamp, content, category) VALUES (?, ?, ?, ?, ?)";
            $stmt_notification = $conn->prepare($sql_notification);
            $stmt_notification->bind_param("iisii", $profile_id, $userid, $time, $content, $category);
            $stmt_notification->execute();
            $stmt_notification->close();

            $sql_alert_select = "SELECT alert FROM users WHERE id = ?";
            $stmt_alert_select = $conn->prepare($sql_alert_select);
            $stmt_alert_select->bind_param("i", $profile_id);
            $stmt_alert_select->execute();
            $stmt_alert_select->bind_result($alert);
            $stmt_alert_select->fetch();
            $stmt_alert_select->close();

            if($alert === null) {
                $alertnum = 1;
            } else {
                $alertnum = $alert + 1;
            }

            $sql_alert_update = "UPDATE users SET alert = ? WHERE id = ?";
            $stmt_alert_update = $conn->prepare($sql_alert_update);
            $stmt_alert_update->bind_param("ii", $alertnum, $profile_id);
            $stmt_alert_update->execute();
            $stmt_alert_update->close();
        }

        if ($result) {
            header("HTTP/1.0 200 OK");
            $message = "Followed this user with success";
        } else {
            header("HTTP/1.0 500 Internal Server Error");
            $error = "An error occured while following this user";
        }
    }
}

if(isset($_POST['unfollow'])) {
    if(!isset($userid)) {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "Please login to unfollow this user";
    }

    $profile_id = (int)$_GET['id'];

    if(!isset($error)) {
        $sql = "DELETE FROM follow WHERE userid = '$userid' AND profileid = '$profile_id'";
        $result = $conn->query($sql);
        if ($result) {
            header("HTTP/1.0 200 OK");
            $message = "Unfollowed this user with success";
        } else {
            header("HTTP/1.0 500 Internal Server Error");
            $error = "An error occured while unfollowing this user";
        }
    }
}

if (isset($_POST['block'])) {
    if(!isset($userid)) {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "Please login to block this user";
    }

    $profile_id = (int)$_GET['id'];
    $time = time();

    if((int)$userid === $profile_id) {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "You cannot block yourself";
    }
    
    $stmt = $conn->prepare("SELECT * FROM follow WHERE userid = ? AND profileid = ?");
    $stmt->bind_param("ii", $userid, $profile_id);
    $result = $stmt->execute();
    $stmt->close();
    if($result) {
    	$stmt = $conn->prepare("DELETE FROM follow WHERE userid = ? AND profileid = ?");
    	$stmt->bind_param("ii", $userid, $profile_id);
    	$result = $stmt->execute();
    	$stmt->close();

    	if (!$result) {
        	header("HTTP/1.0 500 Internal Server Error");
        	$error = "An error occured while blocking this user";
    	}
    }

    $sql_block = "INSERT INTO user_blocks (userid, profileid, date) VALUES (?, ?, ?)";
    $stmt_block = $conn->prepare($sql_block);
    $stmt_block->bind_param("iii", $userid, $profile_id, $time);
    $result = $stmt_block->execute();
    $stmt_block->close();

    if ($result) {
        header("HTTP/1.0 200 OK");
        $message = "Blocked this user with success";
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "An error has happened while blocking this user";
    }
}

if(isset($_POST['unblock'])) {
    if(!isset($userid)) {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "Please login to unblock this user";
    }

    $profile_id = (int)$_GET['id'];

    $sql = "DELETE FROM user_blocks WHERE userid = '$userid' AND profileid = '$profile_id'";
    $result = $conn->query($sql);
    if ($result) {
        header("HTTP/1.0 200 OK");
        $message = "Unblocked this user with success";
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "An error has happened while unblocking this user";
    }
}

if(isset($_POST['ban'])) {
    $profile_id = (int)$_GET['id'];
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $day = $_POST['day'];
	$month = $_POST['month'];
	$year = $_POST['year'];
	$ban_date = mktime(0, 0, 0, $month, $day, $year);
	$duration = $ban_date - time();
    $start_date = time();
    $end_date = $start_date + $duration;
    $email = hash('sha256', strtolower($data['email']));

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        exit('Invalid user ID!');
    }

    if ($duration <= 0) {
        exit('Ban date must be in the future!');
    }
		
    if($current_user->admin != false) {
        $sql = "INSERT INTO bans (user, email, reason, start_date, end_date) VALUES ($profile_id, '$email', '$reason', $start_date, $end_date)";
        $result = $conn->query($sql);
        if ($result) {
            $sql2 = "DELETE FROM sessions WHERE user = $profile_id";
            $result2 = $conn->query($sql2);
            if($result2) {
                header('Location: ' . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']);
                exit;
            }
        } else {
            exit('An SQL error occured!');
        }
    } else {
        exit('User is not an administrator!');
    }
}

if(isset($_POST['warn'])) {
    $profile_id = (int)$_GET['id'];
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $start_date = time();

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        exit('Invalid user ID!');
    }
		
    if($current_user->admin != false) {
        $sql = "INSERT INTO warnings (user, reason, timestamp) VALUES ($profile_id, '$reason', $start_date)";
        $result = $conn->query($sql);
        if ($result) {
           	header('Location: ' . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']);
        	exit;
        } else {
            exit('An SQL error occured!');
        }
    } else {
        exit('User is not an administrator!');
    }
}

if(isset($_POST['delete'])) {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        exit('Invalid user ID!');
    }

    $profile_id = (int)$_GET['id'];
    $email = $email = hash('sha256', strtolower(trim($data['email'])));
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : 'banned by admin request';

    if($current_user->admin != false) {
        $sql = "UPDATE users SET deactive = 1, verify_token = 1 WHERE id = $profile_id";
        $result = $conn->query($sql);
        if ($result) {
            $sql = "INSERT IGNORE INTO blacklist (value, type, reason) VALUES ('$email', 'email', '$reason')";
            $result = $conn->query($sql);

            header('Location: ' . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']);
            exit;
        } else {
            exit('An SQL error occured!');
        }
    } else {
        exit('User is not an administrator!');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $data['username'] ?? 'This user' ?>'s page</title>
    <?php include 'header.php' ?>
</head>
<body class="w3-container">

    <?php include 'navbar.php' ?>

    <?php if(isset($error)) { ?>
        <div class="message w3-padding w3-round w3-red"><?php echo $error ?></div><br /><br />
        <?php exit; ?>
    <?php } ?>

    <?php if(isset($message)) { ?>
        <div class="message w3-padding w3-round w3-light-grey"><?php echo $message ?></div><br /><br />
    <?php } ?>

    <script>
        const userid = '<?php echo $_GET['id'] ?>';

        $(document).ready(function() {
            $.ajax({
                url: "/ajax/profile",
                method: "GET",
                data: { followed_by: userid },
                success: function(response) {
                    let followedBy = "";
                    if (response.length > 0) {
                        if (response.length <= 3) {
                            followedBy = response.map(user => `
                            <a href="${user.url}">
                            <img src="${user.pfp}" width="15px" height="15px" />
                            ${user.username}</a>`).join(", ");
                        } else {
                            let first = response.slice(0, 3);
                            let others = response.length - 3;
                            followedBy = first.map(user => `<a href="${user.url}">
                            <img src="${user.pfp}" width="17px" height="17px" class="w3-circle" />
                            ${user.username}</a>`).join(", ");
                            followedBy += ` and ${others} others you know`;
                        }
                    } else {
                        followedBy = "nobody you know";
                    }
                    $("#followedby-wrapper").html(`Followed by ${followedBy}`);
                    $("#followedby-wrapper").css({
                        "display": "inline",
                        "font-size": "15px",
                        "text-shadow": "0px 0px 0px #fff"
                    })
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    var response = JSON.parse(jqXHR.responseText);
                    console.error('Server status code: ' + textStatus + ' ' + jqXHR.status + ' ' + errorThrown);
                }
            });

            window.params = new URL(window.location.href);
            window.pages = {};

            window.getUserBuilds = function(page) {
                $.ajax({
                    url: "/ajax/profile",
                    method: "GET",
                    data: { getUserBuilds:true,userid:userid,page:page },
                    dataType: "json",
                    success: function(response) {
                        window.pages.c = page;

                        let elm = $('#creationstab div')
                        elm.children().not('#gr8-creation-template').remove();

                        if(response.success === true && response.creations) {
                            response.creations.forEach(function(r) {
                                let $clone = $($('#gr8-creation-template').html());

                                $clone.find(".creation-title").text(r.name);
                                $clone.find(".creation-link").attr("href", "/build/" + r.id);
                                $clone.find(".meta-author a").text(r.username);
                                $clone.find(".meta-author a").attr("href", "/@" + r.username);
                                $clone.find(".meta-author span").text(r.date);
                                $clone.find(".creation-thumbnail").attr("src", r.screenshot);

                                elm.append($clone);
                                window.mode();
                            });
                        } else if(response.creations === null) {
                            $(`<div class='message w3-padding w3-round w3-light-grey'>${response.error}</div>`).appendTo(elm);
                        } else if(response.error) {
                            console.error(response.error);
                            $(`<div class='message w3-padding w3-round w3-red'>${response.error}</div>`).appendTo(elm);
                        } else {
                            console.log(response);
                        }
                    },
                    error: function(xhr, stat, err) {
                        console.error(stat, xhr.status, err);
                    }
                });
            }

            window.getUserLiked = function() {
                $.ajax({
                    url: "/ajax/profile",
                    method: "GET",
                    data: { getUserLiked:true,userid:userid },
                    dataType: "json",
                    success: function(response) {
                        let elm = $('#likestab div')
                        elm.children().not('#gr8-likes-template').remove();

                        if(response.success === true && response.creations) {
                            response.creations.forEach(function(r) {
                                let $clone = $($('#gr8-likes-template').html());

                                $clone.find(".creation-title").text(r.name);
                                $clone.find(".creation-link").attr("href", "/build/" + r.id);
                                $clone.find(".meta-author a").text(r.username);
                                $clone.find(".meta-author a").attr("href", "/@" + r.username);
                                $clone.find(".meta-author span").text(r.date);
                                $clone.find(".creation-thumbnail").attr("src", r.screenshot);

                                elm.append($clone);
                                window.mode();
                            });
                        } else if(response.creations === null) {
                            $(`<div class='message w3-padding w3-round w3-light-grey'>${response.error}</div>`).appendTo(elm);
                        } else if(response.error) {
                            console.error(response.error);
                            $(`<div class='message w3-padding w3-round w3-red'>${response.error}</div>`).appendTo(elm);
                        } else {
                            console.log(response);
                        }
                    },
                    error: function(xhr, stat, err) {
                        console.error(stat, xhr.status, err);
                    }
                });
            }

            window.getUserForums = function(page) {
                $.ajax({
                    url: "/ajax/profile",
                    method: "GET",
                    data: { getUserForums:true,userid:userid,page:page },
                    dataType: "json",
                    success: function(response) {
                        window.pages.f = page;

                        var elm = $('#poststab .w3-row')
                        elm.children().not('#gr8-posts-template').remove();

                        if(response.success === true && response.posts) {
                            response.posts.forEach(function(r) {
                                let $clone = $($('#gr8-posts-template').html());

                                $clone.find(".text").text(r.title);
                                $clone.find(".user").text(r.username);
                                $clone.find(".user").attr("href", "/@" + r.username);
                                $clone.find(".time").text(r.date);
                                $clone.find(".link-name").attr("href", "/topic/" + r.id);

                                elm.append($clone);
                            });
                        } else if(response.posts === null) {
                            $(`<div class='message w3-padding w3-round w3-light-grey'>${response.error}</div><br />`).appendTo(elm);
                        } else if(response.error) {
                            console.error(response.error);
                            $(`<div class='message w3-padding w3-round w3-red'>${response.error}</div><br />`).appendTo(elm);
                        } else {
                            console.log(response);
                        }
                    },
                    error: function(xhr, stat, err) {
                        console.error(stat, xhr.status, err);
                    }
                });
            }

            window.getUserComments = function(page) {
                $.ajax({
                    url: "/ajax/profile",
                    method: "GET",
                    data: { getUserComments:true,userid:userid,page:page },
                    dataType: "json",
                    success: function(response) {
                        window.pages.r = page;

                        var elm = $('#commentstab .w3-row')
                        elm.children().not('#gr8-comment-template').remove();

                        if(response.success === true && response.comments) {
                            response.comments.forEach(function(r) {
                                let $clone = $($('#gr8-comment-template').html());

                                $clone.find(".text").text(r.content);
                                $clone.find(".title").text(r.parent_name);
                                $clone.find(".user").text(r.username);
                                $clone.find(".user").attr("href", "/user/" + r.userid);
                                $clone.find(".time").text(r.date);

                                if(r.type === 'forum') {
                                    $clone.find(".link-name").attr("href", "/topic/" + r.parent);
                                    $clone.find(".title").attr("href", "/topic/" + r.parent);
                                } else if(r.type === 'model') {
                                    $clone.find(".link-name").attr("href", "/build/" + r.parent);
                                    $clone.find(".title").attr("href", "/build/" + r.parent);
                                }

                                elm.append($clone);
                            });
                        } else if(response.comments === null) {
                            $(`<div class='message w3-padding w3-round w3-light-grey'>${response.error}</div><br />`).appendTo(elm);
                        } else if(response.error) {
                            console.error(response.error);
                            $(`<div class='message w3-padding w3-round w3-red'>${response.error}</div><br />`).appendTo(elm);
                        } else {
                            console.log(response);
                        }
                    },
                    error: function(xhr, stat, err) {
                        console.error(stat, xhr.status, err);
                    }
                });
            }


            $("#reportForm").submit(function(e) {
                e.preventDefault();

                $.get("/ajax/config.php", {
                    get_csrf_token: true
                }, function(d) {
                    let csrf_token = d.csrf_token;

                    let payload = {
                        report_type: 'profile',
                        csrf_token: csrf_token,
                        reportv2: true,
                        reportable_id: userid,
                        other: $("#reportForm #otherReason").val(),
                        reason: $("#reportForm [name='reason']:checked").val(),
                    }

                    $.ajax({
                        url: "/creation.php?id=null",//placeholder, will move to seperate api page probably
                        type: "POST",
                        data: payload,
                        dataType: "json",
                        success: function(response) {
                            $("#modal-report").hide();

                            if (response.success) {
                                alert(response.success);
                                $("#reportForm")[0].reset();
                            } else {
                                alert(response.error);
                            }
                        },
                        error: function() {
                            $("#modal-report").hide();
                            alert("An error occurred. Please try again later.");
                        }
                    });
                }, "json").fail(function(xhr, text, err) {
                    alert(text);
                });
            });

            var tabPages = {
                '#creationstab': { page: parseInt(window.pages.c || 0), fetch: getUserBuilds },
                '#commentstab': { page: parseInt(window.pages.r || 0), fetch: getUserComments },
                '#poststab': { page: parseInt(window.pages.f || 0), fetch: getUserForums }
            };

            $(".foward-button, .back-button").on("click", function() {
                var $tab = $(this).closest("#creationstab, #commentstab, #poststab");
                var tabId = `#${$tab.attr('id')}`;
                var tabConfig = tabPages[tabId];

                if (!tabConfig) {
                    return;
                }

                tabConfig.page += $(this).hasClass("foward-button") ? 1 : -1;
                tabConfig.fetch(tabConfig.page);
            });

            window.openTab = function(tab) {
                var tabGroup = $('.tab'); 
                    
                tabGroup.each(function() {
                    $(this).hide();
                });

                var tabElement = $(`#${tab}`);
                tabElement.show();
                $('html, body').animate({ scrollTop: 0 }, 'slow');
            }

            openTab('creationstab');
            getUserBuilds(1);
            getUserForums(1);
            getUserComments(1);
            getUserLiked();
        });
    </script>

    <div class="w3-navbar w3-top w3-row w3-margin-top w3-center" style="flex-direction:row;">
        <a class='w3-button w3-light-grey w3-col m2 w3-hover-blue w3-border w3-border-grey w3-padding-small w3-card-2' onclick="openTab('creationstab')">Creations</a>
        <a class='w3-button w3-light-grey w3-col m2 w3-hover-blue w3-border w3-border-grey w3-padding-small w3-card-2' onclick="openTab('poststab')">Posts</a>
        <a class='w3-button w3-light-grey w3-col m2 w3-hover-blue w3-border w3-border-grey w3-padding-small w3-card-2' onclick="openTab('commentstab')">Comments</a>
        <a class='w3-button w3-light-grey w3-col m2 w3-hover-blue w3-border w3-border-grey w3-padding-small w3-card-2' onclick="openTab('likestab')">Favorites</a>
    </div><br /><br />

    <article id="user-card" class="gr8-theme w3-light-grey w3-card-2 w3-padding w3-round">
        <?php if(file_exists("acc/users/banners/" . htmlspecialchars($_GET['id']) . "..jpg")) { ?>
            <style>
                [data-testid="user-profile-card-banner_image"] {
                    background-image: url('/acc/users/banners/<?php echo htmlspecialchars($_GET['id']) ?>..jpg');
                }
            </style>

            <div data-testid="user-profile-card-banner">
                <span data-testid="user-profile-card-banner_image" id="banner"></span>
            </div>
        <?php } ?>
        
        <span style="font-size: 30px; display: inline-block; vertical-align: top; max-width: 100%;">
            <img id="picture" width="50px" height="50px" class="w3-round" src="<?php echo $data['picture'] ?>" />

            <?php if(!empty($data['admin'])) { ?>
                <span id="username" class="w3-text-red"><?php echo $data['username'] ?></span>
            <?php } else { ?>
                <span id="username"><?php echo $data['username'] ?></span>
            <?php } ?>

            <span style="font-size:20px;">
                <span><b id="model-count"><?php echo $data['model_count'] ?></b>&nbsp;creations</span>
                <span><b id="follower-count"><?php echo $data['followers'] ?></b>&nbsp;followers</span>
                <span><b id="following-count"><?php echo $data['following'] ?></b>&nbsp;following</span>
                <span><b id="view-count"><?php echo $data['views'] ?></b>&nbsp;views</span>
                <span><b id="like-count"><?php echo $data['likes'] ?></b>&nbsp;likes</span>
            </span>
        </span>

        <div><p id="description"><?php echo $data['description'] ?></p></div>

        <span id="joined-wrapper" style="display: inline; font-size: 15px; text-shadow: 0px 0px 0px #fff">Became a member <?php echo time_ago($data['age']) ?></span>
        
        <?php if(!empty($data['twitter'])) { ?>
            <b>-</b>
            <span id="twitter-wrapper" style="display: inline; font-size: 15px; text-shadow: 0px 0px 0px #fff">
                <a id="twitter-link" href="https://twitter.com/<?php echo $data['twitter'] ?>" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-twitter-x" viewBox="0 0 16 16">
  						<path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865z"/>
					</svg>
                    <?php echo $data['twitter'] ?>
                </a>
            </span>
        <?php } ?>
        
        <?php if(!empty($data['bsky'])) { ?>
            <b>-</b>
            <span id="bsky-wrapper" style="display: inline; font-size: 15px; text-shadow: 0px 0px 0px #fff">
                <a id="bsky-link" href="https://bsky.app/profile/<?php echo $data['bsky'] ?>" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bluesky" viewBox="0 0 16 16">
  						<path d="M3.468 1.948C5.303 3.325 7.276 6.118 8 7.616c.725-1.498 2.698-4.29 4.532-5.668C13.855.955 16 .186 16 2.632c0 .489-.28 4.105-.444 4.692-.572 2.04-2.653 2.561-4.504 2.246 3.236.551 4.06 2.375 2.281 4.2-3.376 3.464-4.852-.87-5.23-1.98-.07-.204-.103-.3-.103-.218 0-.081-.033.014-.102.218-.379 1.11-1.855 5.444-5.231 1.98-1.778-1.825-.955-3.65 2.28-4.2-1.85.315-3.932-.205-4.503-2.246C.28 6.737 0 3.12 0 2.632 0 .186 2.145.955 3.468 1.948"/>
					</svg>
                    <?php echo $data['bsky'] ?>
                </a>
            </span>
        <?php } ?>
        
        <b>-</b>
        <span id="followedby-wrapper" style="display: inline; font-size: 15px; text-shadow: 0px 0px 0px #fff"></span><br />

        <?php if(loggedin()) { ?>
            <?php if($current_user->id != trim($_GET['id'])) { ?>
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

                        <?php if($current_user->admin != false) { ?>
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
                <a href="/acc">Edit Profile</a>&nbsp;
            <?php } ?>
        <?php } ?>

    </span></article>
        
    <div id="modal-unfollow" class="w3-modal">
		<div class="w3-modal-content w3-card-2 w3-light-grey w3-center">
			<div class="w3-container">
				<span onclick="document.getElementById('modal-unfollow').style.display='none'" class="w3-button w3-large w3-red w3-hover-white w3-display-topright">&times;</span>
				    <form method='post' action=''>
					<h2>Are you sure you want to unfollow this user?</h2>
					<span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('unfollow').style.display='none'">No</span> 
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
					<span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('block').style.display='none'">No</span> 
					<input type="submit" value="Yes" name="block" class="w3-btn w3-large w3-white w3-hover-red">
				</form>
			</div>
		</div>
	</div>

    <?php if(loggedin() && $current_user->admin != false) { ?>
        <div id="modal-delete" class="w3-modal">
            <div class="gr8-theme w3-modal-content w3-card-2 w3-light-grey w3-center">
                <div class="w3-container">
                    <span onclick="document.getElementById('modal-delete').style.display='none'" class="w3-button w3-large w3-red w3-hover-white w3-display-topright">&times;</span>
                        <form method='post' action=''>
                        <h2>Are you sure you want to hard ban this user?</h2>
                        <textarea name="reason" placeholder="Moderator note about this ban" class="w3-input w3-border w3-mobile" rows="4" cols="50" required></textarea>
                        <span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('delete').style.display='none'">No</span>
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
                            <span name="close" class="w3-btn w3-large w3-white w3-hover-blue w3-round" onclick='document.getElementById("warn").style.display="none"'>No</span>
							<input type="submit" value="Yes" name="warn" class="w3-btn w3-large w3-white w3-hover-red w3-round">
						</form>
					</div>
				</div>
			</div><br />

            <div id="modal-ban" class="w3-modal">
				<div class="gr8-theme w3-modal-content w3-card-2 w3-light-grey w3-center">
					<div class="w3-container">
						<span onclick='document.getElementById("modal-ban").style.display="none"' class="w3-closebtn w3-red w3-hover-white w3-padding w3-display-topright">&times;</span><form method="post" action="">
							<h2>Are you sure you want to soft ban this user?</h2>
                            <p><div class="w3-row-padding"><select class="w3-select" name="day">
                                <option value="01" disabled selected>day</option>
                                <option>01</option>
                                <option>02</option>
                                <option>03</option>
                                <option>04</option>
                                <option>05</option>
                                <option>06</option>
                                <option>07</option>
                                <option>09</option>
                                <option>10</option>
                                <option>11</option>
                                <option>12</option>
                                <option>13</option>
                                <option>14</option>
                                <option>15</option>
                                <option>16</option>
                                <option>17</option>
                                <option>18</option>
                                <option>19</option>
                                <option>20</option>
                                <option>21</option>
                                <option>22</option>
                                <option>23</option>
                                <option>24</option>
                                <option>25</option>
                                <option>26</option>
                                <option>27</option>
                                <option>28</option>
                                <option>29</option>
                                <option>30</option>
                                <option>31</option>
                            </select>
                            <br/>
                            <select class="w3-select" name="month">
                                <option value="1" disabled selected>month</option>
                                <option value="1">Jan</option>
                                <option value="2">Feb</option>
                                <option value="3">Mar</option>
                                <option value="4">Apr</option>
                                <option value="5">May</option>
                                <option value="6">Jun</option>
                                <option value="7">Jul</option>
                                <option value="8">Aug</option>
                                <option value="9">Sep</option>
                                <option value="10">Oct</option>
                                <option value="11">Nov</option>
                                <option value="12">Dec</option>
                            </select>
                            <br/>
                                <select class="w3-select" name="year">
                                <option value="2026" disabled selected>year</option>
                                <option value="2026">2026</option>
                                <option value="2027">2027</option>
                                <option value="2028">2028</option>
                                <option value="2029">2029</option>
                                <option value="2030">2030</option>
                                <option value="2031">2031</option>
                                <option value="2032">2032</option>
                                <option value="2033">2033</option>
                                <option value="2034">2034</option>
                                <option value="2035">2035</option>
                                <option value="2036">2036</option>
                                <option value="2037">2037</option>
                                <option value="2038">2038</option>
                                <option value="2038">2038</option>
                                <option value="2040">2040</option>
                                <option value="2041">2041</option>
                                <option value="2042">2042</option>
                                <option value="2043">2043</option>
                                <option value="2044">2044</option>
                                <option value="2045">2045</option>
                                <option value="2046">2046</option>
                            </select></div></p>
                            <textarea name="reason" placeholder="Moderator note about this ban (required)" class="w3-input w3-border w3-mobile" rows="4" cols="50" required></textarea>
                            <span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick='document.getElementById("ban").style.display="none"'>No</span>
							<input type="submit" value="Yes" name="ban" class="w3-btn w3-large w3-white w3-hover-red">
						</form>
					</div>
				</div>
			</div><br />
        <?php } ?>

    <div ignore>
        <br />
        <br />
    </div>

    <span id="data-user-actions" class="w3-animate-bottom">
        <div class="tab" id="creationstab">
            <a href="#creations" id="creations"></a>
            <div class="w3-row-padding">
                <template id="gr8-creation-template">
                    <div class="w3-col l4 m6 s12 w3-margin-bottom">
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
            <button class="back-button w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Back</button>
            <button class="foward-button w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Foward</button><hr />
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
            <button class="foward-button w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Foward</button><hr />
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
            <button class="foward-button w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Foward</button><hr />
        </div>
        
        <div class="tab" id="likestab">
		    <a href="#likes" id="likes"></a>
			<div class="w3-row-padding">
                <template id="gr8-likes-template">
                    <div class="w3-col l4 m6 s12 w3-margin-bottom">
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
		    </div>
        </div>
    </span>

    <?php include('linkbar.php') ?>

</div>

</body>
</html>