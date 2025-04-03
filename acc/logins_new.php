<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';

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

    <script>
        $(document).ready(function() {
            $.ajax({
                url: "../ajax/auth",
                method: "GET",
                data: { sessions: true },
                success: function(response) {
                    /*$.each(response, function(key, value) {
                        $("#tokenId").text(value.id);
                        $("#signInId").val(value.id);
                        $("#signInUser").text(value.user);
                        $("#signInDate").text(value.time);
                    });*/

                    /*$.each(response, function() {
                        $.each(this, function(k, v) {
                            $("#tokenId").text(v.id);
                            $("#signInId").val(v.id);
                            $("#signInUser").text(v.user);
                            $("#signInDate").text(v.time);
                        });
                    });*/

                    //for (var i = 0; i < response.length; ++i) {
                        //console.log(response[i].time);
                    //}

                    console.log(response);
                    $("#tokenId").text(response.id);
                    $("#signInId").val(response.id);
                    $("#signInUser").text(response.user);
                    $("#signInDate").text(response.time);
                },

                error: function(jqXHR, textStatus, errorThrown) {
                    var response = JSON.parse(jqXHR.responseText);
                    console.error('Server status code: ' + textStatus + ' ' + jqXHR.status + ' ' + errorThrown);
                }
            });
        });
    </script>

    <form action='../ajax/auth' method='GET'>
		<article id='sessionModal' class='w3-card-2 w3-light-grey w3-padding w3-large'>
        <input type='hidden' id='tokenId' name='tokenId' value='' />
		<span>Sign in<span id="signInId"></span></span><br />
        <span>On account <span id="signInUser"></span></span><br />
        <span>Signed in on <span id="signInDate"></span>, UTC</span><br />";
        <input type='submit' id='revoke' name='revoke' class='w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo' value='Revoke Session' />
	</article></form><br />

		<?php
            $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
            if ($conn2->connect_error) {
                exit($conn2->connect_error);
            }

			$sql = "SELECT * FROM sessions WHERE user = '$id' ORDER BY timestamp DESC";
            $result = $conn2->query($sql);

            $sessionAmount = mysqli_num_rows($result);

            echo "<h4>" . $sessionAmount . " active sessions. Your currently on a " . $user_agent . " browser</h4>";

            $i = 0;
			while ($row = $result->fetch_assoc()) {
                if (is_numeric($row['timestamp'])) {
                    $time = gmdate("M d, Y h:i A", $row['timestamp']);
                } else {
                    $time = 'NaN';
                }

                echo "<form action='../ajax/auth' method='GET'>";
				echo "<article id='sessionModal' class='w3-card-2 w3-light-grey w3-padding w3-large'>";
                echo "<input type='hidden' id='tokenId' name='tokenId' value='" . $row['id'] . "' />";
				echo "<span>Sign in " . ++$i . "</span><br />";
                echo "<span>On account " . $user . "</span><br />";
                echo "<time datetime='" . $row['timestamp'] . "'>Signed in on " . $time . ", UTC</time><br />";
                if($_COOKIE['token'] != $row['id']) {
                    echo "<input type='submit' id='revoke' name='revoke' class='w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo' value='Revoke Session' />";
                } else {
                    echo "<p class='w3-text-dark-grey w3-yellow w3-card-2 w3-hover-shadow w3-padding-small'>This is your current session.<br /> To sign out, hover over your username on the navbar and select 'Logout'.</p>";
                }
				echo "</article></form><br />";
            }
            $result->free();
            $conn2->close();
		
		?><br /><br />
		
    <?php include '../linkbar.php' ?>
</body>
</html>