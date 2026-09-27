<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';

if (loggedin()) {
    if (isset($_GET['status']) && ($_GET['status'] === 'logout' || $_GET['status'] === 'loggedout')) {
        logout(true);
        exit;
    } else {
        header('Location: /index.php');
        exit;
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Login</title>
    <?php include '../header.php' ?>
</head>
<body class="w3-container w3-light-blue">
    <?php include '../navbar.php'; ?>
    <script>
        $(document).ready(function() {
            $("#error").hide();

            $("#loginBtn").click(function(event) {
                event.preventDefault();

                var mail = $("#loginForm input[name='mail']").val();
                var pwd = $("#loginForm input[name='pwd']").val();
                var remember = $("#loginForm input[name='remember']").prop("checked");
                var prevBtnText = $("#loginBtn").html();
                
                $("#loginBtn").html('<img src="/img/loading.gif" style="width: 20px; height: 20px;" />');
                $("#loginBtn").prop("disabled", true);

                $.ajax({
                    url: '/ajax/auth',
                    method: "POST",
                    dataType: 'json',
                    data: { login: true, mail: mail, pwd: pwd, remember: remember },
                    success: function(response) {
                        $("#loginBtn").html(prevBtnText);
                		$("#loginBtn").prop("disabled", false);
                        
                        if(response.success === true) {
                            if(location.search == "?desktop=true") {
                                window.location.reload();
                            } else {
                                window.location.href = "/";
                            }
                        } else {
                            $("#error").show()
                            $("#error-text").text(response.error);
                            $("#error").delay(5000).fadeOut(2500, function() {
                        		$("#error-text").text('');
                        	});
                        }
                    },

                    error: function(jqXHR, textStatus, errorThrown) {
                        $("#loginBtn").html(prevBtnText);
                		$("#loginBtn").prop("disabled", false);

                        var response = JSON.parse(jqXHR.responseText);
                        console.error('Server status code: ' + textStatus + ' ' + jqXHR.status + ' ' + errorThrown);
                        
                        if(response.popup) {
                            let $popup = $('#popup-template-contain');
                            let $clone = $($('#popup-template').html());

                            $clone.find("#popup-title").text(response.title);
                            $clone.find("#popup-text").text(response.popup);

                            if(response.extras) {
                                $clone.find("#popup-extra").removeClass('w3-hide').text(response.extras);
                            }

                            if(response.links) {
                                response.links.forEach(elm => {
                                    let $btnclone = $($clone.find('.popup-goto').html());
                                    if(elm.goto) {
                                        $btnclone.find('a').attr("href", elm.goto);
                                    } else {
                                        $btnclone.find('a').addClass('popup-close');
                                    }

                                    $btnclone.find('a').text(elm.btn);
                                    $btnclone.find('a').removeClass('w3-hide');
                                    $clone.find('.popup-buttons').append($btnclone);
                                });
                            }
                            $popup.append($clone);
                            $popup.find("#popup").show();

                            $(".popup-close").click(function(event) {
                                $("#popup").remove();
                            });
                        } else {
                            $("#error").show()
                            $("#error-text").text(response.error);
                            $("#error").delay(5000).fadeOut(2500, function() {
                                $("#error-text").text('');
                            });
                        }
                    }
                });
            });
        });
    </script>

    <template id="popup-template">
        <div id="popup" class="w3-modal w3-card-2">
            <div class="gr8-theme w3-round w3-light-grey w3-modal-content">
                <header class="w3-container">
                    <span class="popup-close w3-button w3-display-topright">&times;</span>
                    <h2 id="popup-title"></h2>
                </header>

                <div class="w3-container">
                    <p id="popup-text"></p>
                    <p class="w3-hide w3-round w3-padding w3-border w3-border-grey" id="popup-extra"></p>
                    <div class="popup-buttons">
                        <span class="popup-goto w3-hide">
                            <span class="w3-padding-small">
                                <a class="w3-btn w3-blue w3-hover-opacity w3-round w3-padding w3-border w3-border-indigo"></a>
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <div id="popup-template-contain"></div>

    <div id="error" style="display: none;" class="login-input w3-red w3-card-2 w3-padding w3-round"><span class="fa fa-times-circle-o" aria-hidden="true"></span>&nbsp;<span id="error-text"></span></div>
    <div id="welcome-large">
        <h2>Login</h2>
    </div>
    <div id="loginForm" class="w3-container">
        <p>Don't have an account? <a href="register">Register</a></p>

        <p><input class="login-input w3-input w3-border" type="email" name="mail" size="50px" placeholder="Email or username"></p>
        <p><a href="forgot_pwd">Forgot password?</a></p>
        <p><input class="login-input w3-input w3-border" type="password" name="pwd" size="50px" placeholder="Password"></p>
        
        <p>
            <input type="checkbox" name="remember">
		    <label for="remember">Remember me</label>
        </p>

        <button class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" id="loginBtn" name="login">Login</button>
    </div>

    <p>Or...</p>
    <div id="sso" class="w3-container">
        <a href="/ajax/auth.php?authtype=github" class="w3-btn w3-round w3-padding-small w3-white w3-hover-light-grey w3-border w3-border-grey" id="github" name="github"><i class="fa fa-github-square" aria-hidden="true"></i> Login with github</a>
        <a href="/ajax/auth.php?authtype=google" class="w3-btn w3-round w3-padding-small w3-white w3-hover-light-grey w3-border w3-border-grey" id="google" name="google"><i class="fa fa-google" aria-hidden="true"></i> Login with google</a>
    </div>
    <?php include '../linkbar.php' ?>
</body>
</html>