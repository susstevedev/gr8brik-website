<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/account_settings.php';

if (isset($_GET['reactive'])) {
    $new_token = $conn->real_escape_string($_GET['token']);
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

    $stmt = $conn->prepare("SELECT * FROM sessions WHERE id = ? LIMIT 1");
    $stmt->bind_param("s", $new_token);
    $stmt->execute();
    $new_tokendata = $stmt->get_result();
    $new_tokenrow = $new_tokendata->fetch_assoc();
    $user = (int)$new_tokenrow['user'];

    $stmt_2 = $conn->prepare("UPDATE users SET deactive = NULL WHERE id = ? LIMIT 1");
    $stmt_2->bind_param("i", $user);
    if ($stmt_2->execute()) {
        setcookie('token', $new_token, time() + (10 * 365 * 24 * 60 * 60), "/");
        echo "<h2>Account has been reactivated, please login after this.</h2>";
        header('Location: index.php');
        exit;
    }
}

if (!loggedin()) {
    header('Location:login.php');
}

if (isset($_POST['banner'])) {
    $id = $current_user->id;
    $uploadOkay = 1;

    if ($current_user->verify_token !== NULL) {
        echo "<center>Please verify your account to continue this action.</center>";
        exit;
    }

    if (isset($_POST['deleteBanner'])) {
        $bannerPath = "users/banners/" . $id . "..jpg";
        if (file_exists($bannerPath)) {
            unlink($bannerPath);
        }
        header("Location: index.php?deletedBanner=true");
        exit;
    } else {
        if (empty($_FILES['fileToUpload']['tmp_name'])) {
            $uploadOkay = 0;
        }

        if ($uploadOkay === 1) {
            if ($_FILES["fileToUpload"]["size"] > 5242880) {
                $uploadOkay = 0;
            }

            if ($uploadOkay === 1) {
                $data = file_get_contents($_FILES["fileToUpload"]["tmp_name"]);
                $image = imagecreatefromstring($data);
                if (!$image) {
                    $uploadOkay = 0;
                }
            }
        }

        if ($uploadOkay === 0) {
            echo '<center>Sorry, there was an error uploading your file.</center>';
        } else {
            $dir = "../acc/users/banners/";
            $upload = $dir . $id . '..jpg';

            if (imagewebp($image, $upload, 50)) {
                echo "<center>Banner updated successfully.</center>";
                header("refresh:3; url=index.php");
            } else {
                echo '<center>Sorry, there was an error saving your banner.</center>';
            }
        }
    }
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <title>Settings</title>
    <?php include '../header.php' ?>
</head>

<body class="w3-light-blue w3-container">
    <?php
        include '../navbar.php';
        include 'panel.php';

        $utils = new ScreenNameUtils();
        $combinedString = $utils->generateRandomScreenName();
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

    <script>
        $(document).ready(function() {
            $("#username-input").on("input", function() {
                var newUsername = $(this).val();
                $("#u_change").attr('disabled', true);
                $("#data-username-available").text('');

                $.ajax({
                    url: "../ajax/account_settings",
                    method: "GET",
                    dataType: 'json',
                    data: { check_username_available: true, username: newUsername },
                    success: function(response) {
                        if (response.available == true) {
                            $("#u_change").attr('disabled', false);
                            $("#data-username-available").removeClass('w3-red').addClass('w3-green');
                        } else if (response.available == false) {
                            $("#data-username-available").removeClass('w3-green').addClass('w3-red');
                        }

                        $("#data-username-available").show().text(response.reason).delay(5000).hide(0);
                    },

                    error: function(jqXHR, textStatus, errorThrown) {
                        var response = JSON.parse(jqXHR.responseText);
                        console.error(jqXHR, textStatus, errorThrown);
                        DOMerror("An error occurred. Please try again later.");
                    }
                });
            });

            $("#u_change").click(function(event) {
                event.preventDefault();

                var username = $("#username input[name='username']").val();
                var token = $.cookie('token');
                if ($("#username input[name='username']").val().trim() === '') {
                    var username = $("#username input[name='username']").attr('placeholder');
                }

                $.ajax({
                    url: "../ajax/account_settings",
                    method: "GET",
                    dataType: 'json',
                    data: {
                        username_change: true,
                        username: username,
                        token: token
                    },
                    success: function(response) {
                        DOMsuccess(response.success || "Unknown response");
                        $(".usertext").text(username);
                    },

                    error: function(jqXHR, textStatus, errorThrown) {
                        var response = JSON.parse(jqXHR.responseText);
                        DOMerror(response.error || "An error occurred. Please try again later.");
                    }
                });
            });

            $("#password").submit(function(event) {
                event.preventDefault();
                var formData = $("#password").serialize() + "&change=true";

                $.ajax({
                    url: "../ajax/account_settings",
                    method: "POST",
                    dataType: 'json',
                    data: formData,
                    success: function(response) {
                        if(response.success) {
                            DOMsuccess(response.success);
                        } else if(response.error) {
                            DOMerror(response.error || "An error occurred. Please try again later.");
                        }
                    },

                    error: function(jqXHR, textStatus, errorThrown) {
                        var response = JSON.parse(jqXHR.responseText);
                        DOMerror(response.error || "An error occurred. Please try again later.");
                    }
                });
            });

            $("#email").submit(function(event) {
                event.preventDefault();
                var formData = $("#email").serialize() + "&mail_change=true";

                $.ajax({
                    url: "../ajax/account_settings",
                    method: "POST",
                    dataType: 'json',
                    data: formData,
                    success: function(response) {
                        if(response.success) {
                            DOMsuccess(response.success);
                        } else if(response.error) {
                            DOMerror(response.error || "An error occurred. Please try again later.");
                        }
                    },

                    error: function(jqXHR, textStatus, errorThrown) {
                        var response = JSON.parse(jqXHR.responseText);
                        DOMerror(response.error || "An error occurred. Please try again later.");
                    }
                });
            });

            $("#blog_user_change").click(function(event) {
                event.preventDefault();

                let blog_user = $("#blog_user_change input[name='user']").val();
                var token = $.cookie('token');
                if ($("#blog_user_change input[name='user']").val().trim() === '') {
                    let blog_user = null;
                }

                $.ajax({
                    url: "../ajax/account_settings",
                    method: "GET",
                    dataType: 'json',
                    data: {
                        blog_user_change: true,
                        blog_user: blog_user,
                        token: token
                    },
                    success: function(response) {
                        DOMsuccess(response.success || "Unknown response");
                    },

                    error: function(jqXHR, textStatus, errorThrown) {
                        var response = JSON.parse(jqXHR.responseText);
                        DOMerror(response.error || "An error occurred. Please try again later.");
                    }
                });
            });

            $("#a_change").click(function(event) {
                event.preventDefault();

                var description = $("#about textarea[name='description']").val();
                var token = $.cookie('token');

                $.ajax({
                    url: "../ajax/account_settings",
                    method: "GET",
                    dataType: 'json',
                    data: {
                        about_change: true,
                        description: encodeURIComponent(description),
                        token: token
                    },
                    success: function(response) {
                        DOMsuccess(response.success || "Unknown response");
                    },

                    error: function(jqXHR, textStatus, errorThrown) {
                        var response = JSON.parse(jqXHR.responseText);
                        DOMerror(response.error || "An error occurred. Please try again later.");
                    }
                });
            });

            $("#twitter_change").click(function(event) {
                event.preventDefault();

                var handle = $("#twitter input[name='handle']").val();
                var token = $.cookie('token');

                $.ajax({
                    url: "../ajax/account_settings",
                    method: "GET",
                    dataType: 'json',
                    data: {
                        twitter_change: true,
                        handle: handle,
                        token: token
                    },
                    success: function(response) {
                        DOMsuccess(response.success || "Unknown response");
                    },

                    error: function(jqXHR, textStatus, errorThrown) {
                        var response = JSON.parse(jqXHR.responseText);
                        DOMerror(response.error || "An error occurred. Please try again later.");
                    }
                });
            });

            $("#bsky_change").click(function(event) {
                event.preventDefault();

                var handle = $("#bsky input[name='handle']").val();
                var token = $.cookie('token');

                $.ajax({
                    url: "../ajax/account_settings",
                    method: "GET",
                    dataType: 'json',
                    data: {
                        bsky_change: true,
                        handle: handle,
                        token: token
                    },
                    success: function(response) {
                        if(response.success) {
                            DOMsuccess(response.success);
                        } else if(response.error) {
                            DOMerror(response.error || "An error occurred. Please try again later.");
                        }
                    },

                    error: function(jqXHR, textStatus, errorThrown) {
                        var response = JSON.parse(jqXHR.responseText);
                        DOMerror(response.error || "An error occurred. Please try again later.");
                    }
                });
            });

            // Source - https://stackoverflow.com/a/23981045
            // Posted by bloodyKnuckles, modified by community. See post 'Timeline' for change history
            // Retrieved 2026-08-02, License - CC BY-SA 4.0

            $('#pictureForm #picture').on('click', function(event) {
                event.preventDefault();

                var file_data = $('#pictureForm #fileToUpload').prop('files')[0];
                var form_data = new FormData();
                form_data.append('fileToUpload', file_data);
                form_data.append('picture', 'true');

                $.ajax({
                    url: "../ajax/account_settings",
                    dataType: 'json',
                    contentType: false,
                    processData: false,
                    data: form_data,
                    type: 'POST',
                    success: function(res) {
                        if (res && res.success && res.image) {
                            $('#pictureForm #fileToUpload').css('background-image', 'url(' + res.image + ')');
                        } else if (res && res.error) {
                            alert(res.error);
                        } else {
                            DOMerror('An error occured while uploading the image');
                        }
                    },
                    error: function(xhr, text, error) {
                        var response = JSON.parse(xhr.responseText);
                        DOMerror(response.error || "An error occured while uploading the image");
                    }
                });
            });

            $('#pictureForm #remove_picture').on('click', function(event) {
                event.preventDefault();

                $.ajax({
                    url: "../ajax/account_settings",
                    dataType: 'json',
                    data: {
                        remove_picture: true
                    },
                    type: 'POST',
                    success: function(res) {
                        if (res && res.success && res.image) {
                            $('#pictureForm #fileToUpload').css('background-image', 'url(' + res.image + ')');
                        } else if (res && res.error) {
                            alert(res.error);
                        } else {
                            DOMerror('An error occured while removing the image');
                        }
                    },
                    error: function(xhr, text, error) {
                        var response = JSON.parse(xhr.responseText);
                        DOMerror(response.error || "An error occured while removing the image");
                    }
                });
            });
        });

        function DOMerror(error) {
            $(".error").show();
            if (error) {
                $(".error").text(error);
            }
            $('html, body').animate({
                scrollTop: 0
            }, 'slow');
        }

        function DOMsuccess(message) {
            $(".success").show();
            if (message) {
                $(".success").text(message);
            }
            $('html, body').animate({
                scrollTop: 0
            }, 'slow');
        }
    </script>

    <style>
        .file-pfp {
            background-image: url('<?php echo $current_user->picture ?: "../acc/users/pfps/" . $current_user->id . ".jpg?" ?>');
        }

        .file-banner {
            background-image: url('<?php
                                    if (file_exists("users/banners/" . $current_user->id . "..jpg")) {
                                        echo "users/banners/" . $current_user->id . "..jpg";
                                    } else {
                                        echo "/img/no_image.png";
                                    }
                                    ?>');
        }
    </style>

    <h4 id="ajax-success" class="success w3-light-grey w3-card-2 w3-padding w3-round"></h4>
    <h4 id="ajax-error" class="error w3-red w3-card-2 w3-padding w3-round"></h4>
    <h1>Account settings</h1><hr />

    <h2>Profile</h2>
    <div class="w3-row">
        <form class="pictureForm" method="post" action="" enctype="multipart/form-data">
            <b><legend>Profile banner</legend></b>
            <p>We recommend your banner be 1200x400</p>
            <p><input type="file" name="fileToupload" id="fileToupload" style="color:transparent;" onchange="this.style.color = 'black';" title=" " class="file-banner"></p>
            <?php
            if ($current_user->banner != null) {
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
                if ($current_user->picture != null) {
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
                <p>To link your X/Twitter account, you will need to provide your handle.</p>
                <input class="w3-input w3-border w3-mobile w3-third" placeholder="@me" value="<?php echo $current_user->twitter ?>" type="text" name="handle">
                <button class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" id="twitter_change" name="twitter_change">Update Twitter</button>
            </form><br />

            <form id="bsky" name="bsky">
                <b><legend>Bluesky</legend></b>
                <p>To link your Bluesky account, you will need to provide your handle (without the at part!). Note that some handles may not be seen as valid internally. If so, contact us so we can update it for you.</p>
                <input class="w3-input w3-border w3-mobile w3-third" placeholder="user.bsky.social" value="<?php echo $current_user->bsky ?>" type="text" name="handle">
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

    <?php include('../linkbar.php') ?>
</body>

</html>