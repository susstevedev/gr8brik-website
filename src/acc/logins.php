<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/what_browser.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';
isLoggedin();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Sessions</title>
    <?php include '../header.php' ?>
</head>
<body class="w3-light-blue w3-container">

    <?php 
        include('../navbar.php');
        include('panel.php');
    ?>

		<?php
            $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
            if ($conn2->connect_error) {
                exit($conn2->connect_error);
            }

			$sql = "SELECT * FROM sessions WHERE user = '$id' ORDER BY timestamp DESC";
            $result = $conn2->query($sql);

            $sessionAmount = mysqli_num_rows($result);

            echo "<h4><span id='session-amount'>" . $sessionAmount . "</span> active sessions. You are currently on " . UA . ".</h4>";

            $i = 0;
			while ($row = $result->fetch_assoc()) {
                $time = time_ago(date('Y-m-d H:i:s', $row['timestamp']));

				echo "<article id='sessionModal" . $i . "' class='gr8-theme w3-card-2 w3-light-grey w3-padding w3-margin-bottom w3-round w3-large'>";
                echo "<span>Last IP " . $row['login_from'] . "</span><br />";
                echo "<span>On browser " . get_browser_name($row['user_agent']) . ", " . get_system_name($row['user_agent']) . "</span><br />";
                echo "Last active <time id='original' style='cursor: pointer;' datetime='" . date('Y-m-d H:i:s', $row['timestamp']) . "'>" . $time . "</time>";
                echo "<time id='complete' style='display: none; cursor: pointer;' datetime='" . date('Y-m-d H:i:s', $row['timestamp']) . "'> " . date("F j, Y, g:i a", $row['timestamp']) . "</time><br />";
                if($_SESSION['tokenid'] != $row['id']) {
                    echo "<button data-id='" . $row['id'] . "' class='revoke-session w3-btn w3-red w3-hover-opacity w3-padding-small w3-round w3-border w3-border-pink'>
                        Revoke Session
                    </button>";
                } else {
                    echo '<div class="w3-padding-small w3-round w3-amber w3-text-black"><span>This is your current session.</span><br />';
                    echo '<span>To logout on desktop, go to the sidebar, hover over your username or email and click "Logout".</span><br />';
                    echo '<span>To logout on mobile, the profile icon on the bottom menu, scroll all the way down to "Danger zone" and click "Logout". Then confirm.</span></div>';
                }
                echo "</article>";
                $i++;
            }
            $result->free();
            $conn2->close();
		
		?><br /><br />

        <script>
        $(document).ready(function() {
            $(document).on("click", ".revoke-session", function() {
                event.preventDefault();
                let session = $(this).data("id");
                let btn = $(this);
                let form = $(this).parent();
                
                btn.text = "...";
                    
                $.ajax({
                    url: "/ajax/auth",
                    method: "GET",
                    data: { revoke: true, tokenId: session },
                    success: function(response) {
                        console.log(response.success);
                        form.remove();
                        var amount = parseInt($('#session-amount').text());
                        $('#session-amount').text(amount - 1);
                    },
                    error: (jqXHR, textStatus, errorThrown) => {
                        console.error("error:", textStatus, errorThrown, jqXHR);
                        const response = JSON.parse(jqXHR.responseText);
                        alert(response.error);
                    }
                });
            });
            
            console.log($('#session-amount').text());
            console.log(parseInt($('#session-amount').text()) - 1);
            
            /*$(document).on("click", "#sessionModal #original", function() {
                $(this).hide();
                $("#complete").show();
            });
            
            $(document).on("click", "#sessionModal #complete", function() {
                $(this).hide();
                $("#original").show();
            }); */
            
            $(document).on("click", "#original", function() {
                $(this).hide();
                $(this).siblings("#complete").show();
            });

            $(document).on("click", "#complete", function() {
                $(this).hide();
                $(this).siblings("#original").show();
            });
        });
        </script>
		
    <?php include '../linkbar.php' ?>
</body>
</html>