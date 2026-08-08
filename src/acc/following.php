<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
if(!loggedin()) {
    header('Location:login.php');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Social</title>
    <?php include '../header.php' ?>
</head>
<body class="w3-light-blue w3-container">

    <?php 
        include '../navbar.php';
        include 'panel.php';
    ?>

    <script>
        $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);
            const page = urlParams.get('p');

            window.updateTab = function(tab) {
                let button = document.querySelector('[data-testid=' + tab + ']');
                openTab(tab, button);
                urlParams.set('p', tab);
                window.history.pushState({}, '', window.location.pathname + '?' + urlParams);
            }
            
            if(page === null || page === 'followingtab') {
                updateTab(page);
            } else {
                updateTab(page);
            }
        });
    </script>

    <div class="message w3-padding-small w3-round-small w3-card-2 w3-yellow w3-text-black">
        <p>Some accounts may not appear here.</p>
        <p>When an account is deactivated for longer than 14 days, we start deleting data, like follow and block relations, to maintain user privacy.</p>
    </div><br />

    <div class="w3-bar">
        <div data-testid="followingtab" class='w3-bar-item w3-bottombar' style="width: 25%; text-align: center;" onclick="updateTab('followingtab')">Following</div>
        <div data-testid="followerstab" class='w3-bar-item w3-bottombar' style="width: 25%; text-align: center;" onclick="updateTab('followerstab')">Followers</div>
        <div data-testid="blockedtab" class='w3-bar-item w3-bottombar' style="width: 25%; text-align: center;" onclick="updateTab('blockedtab')">Blocking</div>
        <div data-testid="blockertab" class='w3-bar-item w3-bottombar' style="width: 25%; text-align: center;" onclick="updateTab('blockertab')">Blockers</div>
    </div>

    <?php
        $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        if ($conn2->connect_error) {
            exit($conn2->connect_error);
        }

        $id = $current_user->id;
        $sql = "SELECT DISTINCT u.* FROM follow f JOIN users u ON f.profileid = u.id WHERE f.userid = ? ORDER BY f.id DESC";

        $stmt = $conn2->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        echo "<div id='followingtab' class='tab w3-animate-opacity w3-hide'><h4>Who you follow</h4>";

        while ($row2 = $result->fetch_assoc()) {
            $profileid = (int)$row2['id'];
            $username = $row2['username'] ?? 'inactive';

            $clean_username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
            $clean_description = htmlspecialchars($row2['description'] ?? '', ENT_QUOTES, 'UTF-8');
            $clean_age = htmlspecialchars($row2['age'] ?? '', ENT_QUOTES, 'UTF-8');
            $url_username = strtolower(urlencode($username));

            echo "<article data-gr8brik-item='follow-" . htmlspecialchars($row2['blog_user_id'] ?? '') . "' id='user-" . $profileid . "-name-" . $url_username . "' class='gr8-theme w3-card-2 w3-light-grey w3-padding w3-large'>";
            echo "<a href='../user/" . $profileid . "'>" . $clean_username . '</a><br />';
            
            if (!empty($clean_description)) {
                echo "<span>" . $clean_description . '</span><br />';
            }
            echo "<span>Member since " . $clean_age . '</span>';
            
            echo "<form id='unfollow-" . $profileid . "' method='post' action='profile.php?id=" . $profileid . "'></form>";
            echo "<input form='unfollow-" . $profileid . "' type='submit' value='Unfollow' name='unfollow' class='w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink'>";
            echo "</article><br />";
        }

        $stmt->close();
        echo "</div>";

        $sql_followers = "SELECT DISTINCT u.* FROM follow f JOIN users u ON f.userid = u.id WHERE f.profileid = ? ORDER BY f.id DESC";

        $stmt1 = $conn2->prepare($sql_followers);
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        $result3 = $stmt1->get_result();

        echo "<div id='followerstab' class='tab w3-animate-opacity w3-hide'><h4>Who follows you</h4>";

        while ($row4 = $result3->fetch_assoc()) {
            $userid = (int)$row4['id'];
            $username = $row4['username'] ?? 'inactive';
            
            $clean_username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
            $clean_description = htmlspecialchars($row4['description'] ?? '', ENT_QUOTES, 'UTF-8');
            $clean_age = htmlspecialchars($row4['age'] ?? '', ENT_QUOTES, 'UTF-8');
            $url_username = strtolower(urlencode($username));

            echo "<article data-gr8brik-item='follow-" . htmlspecialchars($row4['blog_user_id'] ?? '') . "' id='user-" . $userid . "-name-" . $url_username . "' class='gr8-theme w3-card-2 w3-light-grey w3-padding w3-large'>";
            echo "<a href='../user/" . $userid . "'>" . $clean_username . '</a><br />';
            if (!empty($clean_description)) {
                echo "<span>" . $clean_description . '</span><br />';
            }
            echo "<span>Member since " . $clean_age . '</span>';
            echo "</article><br />";
        }
        $stmt1->close();
        echo "</div>";

        $sql_blocked = "SELECT DISTINCT u.* FROM user_blocks b JOIN users u ON b.profileid = u.id WHERE b.userid = ? ORDER BY b.id DESC";

        $stmt2 = $conn2->prepare($sql_blocked);
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $result4 = $stmt2->get_result();

        echo "<div id='blockedtab' class='tab w3-animate-opacity w3-hide'><h4>Who you blocked</h4>";

        while ($row5 = $result4->fetch_assoc()) {
            $userid = (int)$row5['id'];
            $username = $row5['username'] ?? 'inactive';

            $clean_username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
            $clean_description = htmlspecialchars($row5['description'] ?? '', ENT_QUOTES, 'UTF-8');
            $clean_age = htmlspecialchars($row5['age'] ?? '', ENT_QUOTES, 'UTF-8');
            $url_username = strtolower(urlencode($username));

            echo "<article data-gr8brik-item='block-" . htmlspecialchars($row5['blog_user_id'] ?? '') . "' id='user-" . $userid . "-name-" . $url_username . "' class='gr8-theme w3-card-2 w3-light-grey w3-padding w3-large'>";
            echo "<a href='../user/" . $userid . "'>" . $clean_username . '</a><br />';
            if (!empty($clean_description)) {
                echo "<span>" . $clean_description . '</span><br />';
            }
            echo "<span>Member since " . $clean_age . '</span>';
            
            echo "<form id='unblock-" . $userid . "' method='post' action='profile.php?id=" . $userid . "'></form>";
            echo "<input form='unblock-" . $userid . "' type='submit' value='Unblock' name='unblock' class='w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink'>";
            echo "</article><br />";
        }
        $stmt2->close();
        echo "</div>";

        $sql_blockers = "SELECT DISTINCT u.* FROM user_blocks b JOIN users u ON b.userid = u.id WHERE b.profileid = ? ORDER BY b.id DESC";

        $stmt3 = $conn2->prepare($sql_blockers);
        $stmt3->bind_param("i", $id);
        $stmt3->execute();
        $result6 = $stmt3->get_result();

        echo "<div id='blockertab' class='tab w3-animate-opacity w3-hide'><h4>Who blocks you</h4>";

        while ($row7 = $result6->fetch_assoc()) {
            $userid = (int)$row7['id'];
            $username = $row7['username'] ?? 'inactive';

            $clean_username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
            $clean_description = htmlspecialchars($row7['description'] ?? '', ENT_QUOTES, 'UTF-8');
            $clean_age = htmlspecialchars($row7['age'] ?? '', ENT_QUOTES, 'UTF-8');

            echo "<article id='" . $userid . "' class='gr8-theme w3-card-2 w3-light-grey w3-padding-small w3-large'>";
            echo "<span><a href='../user/" . $userid . "'>" . $clean_username . '</a></span><br />';
            if (!empty($clean_description)) {
                echo "<span>" . $clean_description . '</span><br />';
            }
            echo "<span>Member since " . $clean_age . '</span>';
            echo "</article><br />";
        }
        $stmt3->close();
        $conn2->close();
        echo "</div>";
	?>
		
    <?php include '../linkbar.php' ?>
</body>
</html>