<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';
isLoggedIn();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Notifications</title>
    <?php include '../header.php' ?>
</head>
<body class="w3-light-blue w3-container">

    <?php 
        include('../navbar.php');
        include('panel.php');
    ?>

        <script>
        $(document).ready(function() {
            function clear_notifications() {
                $.ajax({
                    url: "../ajax/account_settings",
                    method: "GET",
                    data: { clear_notifications: true },
                    success: function(response) {
                        window.location.reload();
                        $("#gr8-notif").hide();
                    },

                    error: function(jqXHR, textStatus, errorThrown) {
                        var response = JSON.parse(jqXHR.responseText);
                        console.error('Server status code: ' + textStatus + ' ' + jqXHR.status + ' ' + errorThrown);
                        alert(response.error);
                    }
                });
            }
            clear_notifications();
            $("#clearBtn").click(function(event) {
                event.preventDefault();
                clear_notifications();
            });
        });
        </script>

        <button class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" id="clearBtn" name="clearBtn">Clear inbox</button>

		<?php
            $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
            if ($conn2->connect_error) {
                exit($conn2->connect_error);
            }

            $result3 = $conn2->query("SELECT COUNT(*) as num FROM notifications WHERE user = " . $token['user']);
            $row3 = $result3->fetch_assoc();
            echo '<h2>' . $row3['num'] . ' total notifications since ' . time_ago($users_row['age']) . '</h2>';

			$sql = "SELECT * FROM notifications WHERE user = " . $token['user'] . " ORDER BY timestamp DESC";
            $result = $conn2->query($sql);

            $url = null;
            $post = 'New notification';
            $user = null;
            $img = '../img/info.jpg';

			while ($row = $result->fetch_assoc()) {
                $profile = $row['profile'];
                $sql2 = "SELECT * FROM users WHERE id = $profile";
                $result2 = $conn2->query($sql2);
                $row2 = $result2->fetch_assoc();

                if ($row['category'] === "1") {
                    $url = "/user/" . $profile;
                    $post = "followed you";
                    $user = $row2['username'];
                    $img = '../acc/users/pfps/' . $profile;
                } elseif ($row['category'] === "2") {
                    $conn3 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
                    $content = $row['content'];
                    $result3 = $conn3->query("SELECT * FROM model WHERE id = $content");
                    $row3 = $result3->fetch_assoc();

                    $img = '../cre/' . $row3['screenshot'];
                    $url = "/build/" . $content;
                    $post = "commented on " . $row3['name'];
                    $user = $row2['username'];

                    $result3->free();
                    $conn3->close();
                } elseif ($row['category'] === "3") {
                    $conn3 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
                    $content = $row['content'];
                    $result3 = $conn3->query("SELECT * FROM messages WHERE id = $content");
                    $row3 = $result3->fetch_assoc();

                    $img = '../img/com.jpg';
                    $url = "/topic/" . $row['content'];
                    $post = "replied to " . htmlspecialchars($row3['title']) ?: "[deleted]";
                    $user = $row2['username'];

                    $result3->free();
                    $conn3->close();
                }
                
                if (is_numeric($row['timestamp'])) {
                    $time = time_ago(date("Y-m-d H:i:s", $row['timestamp']));
                } else {
                    $time = "time_ago(null)";
                }

				echo "<article class='w3-card-2 w3-hover-shadow gr8-theme w3-light-grey w3-padding w3-large'>";
                echo "<a href='" . $url . "'><img src='" . $img . "' style='width: 150px; height: 150px; border-radius: 2px;' alt='" . $img . "' title='" . $img . "'>";
				echo "<span style='display: inline-block; vertical-align: top; padding: 5px 5px 5px 5px;'>";
                echo htmlspecialchars($user) . "&nbsp;" . htmlspecialchars($post) . "!</span></a>";
                echo "<time class='w3-right w3-text-grey' datetime=''>" . $time . "</time>";
				echo "</article><br />";

                $img = null;
                $result2->free();
            }
            $result->free();
            $conn2->close();
		
		?><br /><br />
		
    <?php include '../linkbar.php' ?>
</body>
</html>