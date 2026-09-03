<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';

if (loggedin()) {
    header('Location: forgot_pwd.php');
    exit;
}

if(!isset($_GET['code'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Change password</title>
    <?php include '../header.php' ?>
</head>
<body class="w3-container w3-light-blue">
    <?php include '../navbar.php'; ?>
    <script>
        $(document).ready(function() {
            $("#reset").click(function(event) {
                event.preventDefault();

                var btn = $(this);
                var pwd = $("#resetForm input[name='pwd']").val();
                var token = $("#resetForm input[name='token']").val();
                var prevBtnText = btn.html();

                btn.html('<i class="fa fa-refresh fa-spin" aria-hidden="true"></i>');
                btn.prop("disabled", true);

                $.ajax({
                    url: '/ajax/auth',
                    method: "POST",
                    dataType: 'json',
                    data: { change_pwd: true, pwd: pwd, token: token },
                    success: function(response) {
                        if(response) {
                            if(response.success === true) {
                                $("#resetForm").hide();
                                $("#successForm").show();
                                $("#successForm .success").text(response.message);
                            } else if(response.error) {
                                $("#error").show();
                                $("#error-text").text(response.error);
                                $("#error").delay(5000).fadeOut(2500, function() {
                                    $("#error-text").text('');
                                });
                            }
                        }

                        btn.html(prevBtnText).prop("disabled", false);
                    },

                    error: function(xhr, text, err) {
                        console.error(text, xhr, err);

                        $("#error").show();
                        $("#error-text").text(err);
                        $("#error").delay(5000).fadeOut(2500, function() {
                        	$("#error-text").text('');
                        });

                        btn.html(prevBtnText).prop("disabled", false);
                    }
                });
            });
        });
    </script>

    <div id="error" style="display: none;" class="w3-red w3-card-2 w3-padding-small w3-round-small"><span class="fa fa-exclamation-circle" aria-hidden="true"></span>&nbsp;<span id="error-text"></span></div>
    <div id="welcome-large">
        <h2>Change password</h2>
    </div>

    <div id="resetForm" class="w3-container">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars(isset($_GET['code']) ? $_GET['code'] : null) ?>">
        <p><input class="login-input w3-input w3-border" type="password" name="pwd" size="50px" placeholder="New password"></p>
        <button class="w3-btn w3-round w3-padding-small w3-blue w3-hover-light-grey w3-border w3-border-indigo" id="reset" name="reset">Change</button>
    </div>

    <div id="successForm" style="display: none;" class="w3-container w3-center">
        <h3>Success!</h3>
        <div class="success w3-panel w3-padding w3-green w3-round-small w3-card-2"></div>
    </div>

    <?php include '../linkbar.php' ?>
</body>
</html>