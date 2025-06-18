<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/profile.php';

if(!isset($_GET['id'])) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';

$data = json_decode(fetch_profile($_GET['id'], $token['user'], $users_row, $_SESSION['csrf']), true);

if ($data === null) {
    exit('no user');
}

if($data['message'] != "OK") {
    $error = $data['message'];
    $data['username'] = 'User';
}

if (isset($_POST['follow'])) {
    $profile_id = $_GET['id'];
    $time = time();

    $sql_follow = "INSERT INTO follow (userid, profileid, date) VALUES (?, ?, ?)";
    $stmt_follow = $conn->prepare($sql_follow);
    $stmt_follow->bind_param("iii", $id, $profile_id, $time);
    $result = $stmt_follow->execute();
    $stmt_follow->close();

    if ($id != $profile_id) {
        $content = $profile_id;
        $category = 1;

        $sql_notification = "INSERT INTO notifications (user, profile, timestamp, content, category) VALUES (?, ?, ?, ?, ?)";
        $stmt_notification = $conn->prepare($sql_notification);
        $stmt_notification->bind_param("iisii", $profile_id, $id, $time, $content, $category);
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
        $message = "Followed this user with success!";
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "An error has happened while following this user.";
    }
}

if(isset($_POST['unfollow'])) {
    $profile_id = $_GET['id'];
    $sql = "DELETE FROM follow WHERE userid = '$id' AND profileid = '$profile_id'";
    $result = $conn->query($sql);
    if ($result) {
        header("HTTP/1.0 200 OK");
        $message = "Unfollowed this user with success!";
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "An error has happened while unfollowing this user.";
    }
}

if (isset($_POST['block'])) {
    $profile_id = $_GET['id'];
    $time = time();

    $sql_block = "INSERT INTO user_blocks (userid, profileid, date) VALUES (?, ?, ?)";
    $stmt_block = $conn->prepare($sql_block);
    $stmt_block->bind_param("iii", $id, $profile_id, $time);
    $result = $stmt_block->execute();
    $stmt_block->close();

    if ($result) {
        header("HTTP/1.0 200 OK");
        $message = "Blocked this user with success!";
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "An error has happened while blocking this user.";
    }
}

if(isset($_POST['unblock'])) {
    $profile_id = $_GET['id'];
    $sql = "DELETE FROM user_blocks WHERE userid = '$id' AND profileid = '$profile_id'";
    $result = $conn->query($sql);
    if ($result) {
        header("HTTP/1.0 200 OK");
        $message = "Unblocked this user with success!";
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "An error has happened while unblocking this user.";
    }
}

if(isset($_POST['ban'])) {
    $profile_id = $_GET['id'];
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $day = $_POST['day'];
	$month = $_POST['month'];
	$year = $_POST['year'];
	$ban_date = mktime(0, 0, 0, $month, $day, $year);
	$duration = $ban_date - time();
    $start_date = time();
    $end_date = $start_date + $duration;
		
    if($admin != 0) {
        $sql = "INSERT INTO bans (user, reason, start_date, end_date) VALUES ($profile_id, '$reason', $start_date, $end_date)";
        $result = $conn->query($sql);
        if ($result) {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']);
            exit;
        } else {
            echo $conn->error;
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $data['username'] ?>'s page</title>
    <?php include 'header.php' ?>
</head>
<body class="w3-container w3-light-blue">

    <?php include('navbar.php'); ?>

    <?php if(isset($error)) { ?>
        <div class="message w3-padding w3-round w3-red"><?php echo $error ?></div><br /><br />
        <?php exit; ?>
    <?php } ?>

    <?php if(isset($message)) { ?>
        <div class="message w3-padding w3-round w3-light-grey"><?php echo $message ?></div><br /><br />
    <?php } ?>

    <script>
        const userid = <?php echo $_GET['id'] ?>;
        $(document).ready(function() {
            function followed() {
                $.ajax({
                    url: "/ajax/profile",
                    method: "GET",
                    data: { follow: userid },
                    success: function(response) {
                        let followedBy = "";
                        if (response.length > 0) {
                            if (response.length <= 3) {
                                followedBy = response.map(user => `
                                    <a href="/user/${user.userid}">
                                        <img src="/acc/users/pfps/${user.userid}.jpg" width="15px" height="15px" />
                                    ${user.username}</a>`).join(", ");
                            } else {
                                let first = response.slice(0, 3);
                                let others = response.length - 3;
                                followedBy = first.map(user => `<a href="/user/${user.userid}">
                                    <img src="/acc/users/pfps/${user.userid}.jpg" width="15px" height="15px" />
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
            }
            window.followed = followed();
        });
        document.addEventListener("DOMContentLoaded", function() {
            followed();
        });
    </script>

    <div id="user-card" class="w3-light-grey w3-card-2 gr8-theme w3-padding-small">
    <?php if(file_exists("acc/users/banners/" . htmlspecialchars($_GET['id']) . "..jpg")) { ?>
        <center>
            <img id="banner" style="width: 100%; height: 25vh; border-radius: 0px;" src="/acc/users/banners/<?php echo htmlspecialchars($_GET['id']) ?>..jpg" />
        </center>
    <?php } ?>
        
        <span style="font-size: 30px; display: inline-block; vertical-align: top; max-width: 100%;">
            <img id="picture" style="width: 50px; height: 50px; border-radius: 50px;" src="/acc/users/pfps/<?php echo htmlspecialchars($_GET['id']) ?>.jpg" />

            <?php if(!empty($data['admin'])) { ?>
                <span id="username" class="w3-text-red"><?php echo $data['username'] ?></span>
            <?php } else { ?>
                <span id="username"><?php echo $data['username'] ?></span>
            <?php } ?>
            
            <?php if(trim($token['user']) === trim($_GET['id'])) { ?>
                <span><a href="/acc/index"><i class="fa fa-pencil w3-xlarge" aria-hidden="true"></i></a></span>
            <?php } ?>

            <span style="font-size:20px;">
                <span><b id="data-model-count" style="text-decoration: underline dotted;"><?php echo $data['model_count'] ?></b>&nbsp;creations</span>
                <span><b id="data-follower-count" style="text-decoration: underline dotted;"><?php echo $data['followers'] ?></b>&nbsp;followers</span>
                <span><b id="data-following-count" style="text-decoration: underline dotted;"><?php echo $data['following'] ?></b>&nbsp;following</span>
            </span>
        </span>

        <pre id="description"><?php echo $data['description'] ?></pre>

        <span id="joined-wrapper" style="display: inline; font-size: 15px; text-shadow: 0px 0px 0px #fff">Registered <?php echo time_ago($data['age']) ?></span>
        <?php if(!empty($data['twitter'])) { ?>
            <b>-</b>
            <span id="twitter-wrapper" style="display: inline; font-size: 15px; text-shadow: 0px 0px 0px #fff">
                <a id="twitter-link" href="https://twitter.com/<?php echo $data['twitter'] ?>" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-twitter-x" viewBox="0 0 16 16">
                        <path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865z"/>
                    </svg>
                    <?php echo $data['twitter'] ?>
                </a>
            </span>
        <?php } ?>
        <b>-</b>
        <span id="followedby-wrapper" style="display: inline; font-size: 15px; text-shadow: 0px 0px 0px #fff"></span><br />
        <!-- <hr style="border-color: inherit; width: 50%; text-align: left; margin-left: 0;" /> -->

        <?php if($data['isLoggedin']) { ?>
            <?php if($token['user'] != trim($_GET['id'])) { ?>
            <span id="action-buttons">
                <?php if($data['isFollowing'] === true) { ?>
                    <button onclick='document.getElementById("unfollow").style.display="block"' name="unfollow" class="button-unfollow w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink" />
                        Unfollow
                    </button>&nbsp;
                <?php } elseif($data['isFollowing'] === false) { ?>
                    <input id="button-follow" form="followUser" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" type="submit" value="Follow" name="follow">&nbsp;
                <?php } ?>
                
                <?php if($data['canBan'] === true) { ?>
                    <button id="button-ban-user" onclick='document.getElementById("ban").style.display="block"' name="ban" class="w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink" />
                        Ban
                    </button>&nbsp;
                <?php } ?>
                
                <?php if($data['blockedUser'] === false) { ?>
                <button onclick='document.getElementById("block").style.display="block"' name="block" class="w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink" />
                    Block
                </button>&nbsp;
                <?php } elseif($data['blockedUser'] === true) { ?>
                    <input id="button-unblock" form="unblockUser" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" type="submit" value="Unblock" name="unblock">&nbsp;
                <?php } ?>

                <form id="followUser" action="" method="post"></form>
                <form id="unblockUser" action="" method="post"></form>
            </span>
            <?php } ?>
        <?php } ?>

    </span></div>

    
    <div id="unfollow" class="w3-modal">
		<div class="w3-modal-content w3-card-2 w3-light-grey w3-center">
			<div class="w3-container">
				<span onclick="document.getElementById('unfollow').style.display='none'" class="w3-button w3-large w3-red w3-hover-white w3-display-topright">&times;</span>
				    <form method='post' action=''>
					<h2>Are you sure you want to unfollow this user?</h2>
					<span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('unfollow').style.display='none'">No</span> 
					<input type="submit" value="Yes" name="unfollow" class="w3-btn w3-large w3-white w3-hover-red">
				</form>
			</div>
		</div>
	</div>

    <div id="block" class="w3-modal">
		<div class="w3-modal-content w3-card-2 w3-light-grey w3-center">
			<div class="w3-container">
				<span onclick="document.getElementById('block').style.display='none'" class="w3-button w3-large w3-red w3-hover-white w3-display-topright">&times;</span>
				    <form method='post' action=''>
					<h2>Are you sure you want to block this user?</h2>
					<span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('block').style.display='none'">No</span> 
					<input type="submit" value="Yes" name="block" class="w3-btn w3-large w3-white w3-hover-red">
				</form>
			</div>
		</div>
	</div>

    <?php if($admin != 0) { ?>
            <div id="ban" class="w3-modal">
				<div class="w3-modal-content w3-card-2 w3-light-grey w3-center">
					<div class="w3-container">
						<span onclick='document.getElementById("ban").style.display="none"' class="w3-closebtn w3-red w3-hover-white w3-padding w3-display-topright">&times;</span><form method="post" action="">
							<h2>Are you sure you want to ban this user?</h2>
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
                                <option value="2024" disabled selected>year</option>
                                <option value="2024">2024</option>
                                <option value="2025">2025</option>
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
                            </select></div></p>
                            <textarea name="reason" placeholder="Moderator note about this ban (required)" class="w3-input w3-border w3-mobile" rows="4" cols="50" required></textarea>
                            <span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick='document.getElementById("ban").style.display="none"'>No</span>
							<input type="submit" value="Yes" name="ban" class="w3-btn w3-large w3-white w3-hover-red">
						</form>
					</div>
				</div>
			</div><br />
        <?php } ?>

    <span id="data-user-actions">
	    <a href="#creations" id="creations"></a>
			<h3>creations</h3>
		    <div class="w3-row">
			<?php
				$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

				if ($conn->connect_error) {
					die("Connection failed: " . $conn->connect_error);
				}
				
                $profileid = $_GET['id'];
                $sql = "SELECT * FROM model WHERE user = $profileid ORDER BY date DESC";
                $result = $conn->query($sql);

                while ($creation = $result->fetch_assoc()) {
                    echo "<div class='w3-display-container w3-left w3-padding-small'>";
					echo "<a href='/build/" . $creation['id'] . "'><img src='/cre/" . $creation['screenshot'] . "' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-card-2 w3-border'></a>";
                    echo "<b class='w3-display-middle w3-text-grey'>" . htmlspecialchars($creation['name'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401) . "</b></div>";
                }
                $conn->close();
            ?>
			</div>
			<hr />
			<a href="#posts" id="posts"></a>
				<h3>posts</h3>
				<div class="w3-row">
			<?php
				$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
				if ($conn->connect_error) {
					exit($conn->connect_error);
				}

                $profileid = $_GET['id'];
                $sql = "SELECT * FROM posts WHERE user = $profileid ORDER BY date DESC";
                $result = $conn->query($sql);

                while ($topic = $result->fetch_assoc()) {
                    echo "<div class='w3-display-container w3-left w3-padding-small'>";
					echo "<a href='/topic/" . $topic['id'] . "'><img src='/img/com.jpg' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-card-2 w3-border'></a>";
                    echo "<b class='w3-display-middle w3-text-grey'>" . htmlspecialchars($topic['title'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401) . "</b></div>";
                }
                $conn->close();
            ?>

			</div>
			<hr />
		<a href="#comments" id="comments"></a>
            <div class="w3-row">
			<h3>comments</h3>
            <?php
                $conn1 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
                $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
                if ($conn2->connect_error || $conn1->connect_error) {
                    echo $conn2->connect_error;
                    echo $conn2->connect_error;
                    exit;
                }

                // comments and replies
                $profileid = $_GET['id'];
                $sql = "SELECT * FROM comments WHERE user = $profileid ORDER BY id DESC";
                $result = $conn1->query($sql);

                echo "<h4>creation comments</h4><br />";
                if ($result->num_rows === 0) {
                    echo "<b>no creation comments yet from this user</b>";
                }
                while ($comment = $result->fetch_assoc()) {
                    $truncatedPost = htmlspecialchars(substr($comment['comment'], 0, 50), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
                    if (strlen($comment['comment']) > 50) {
                        $truncatedPost .= "<b class='w3-large'>&nbsp;...more&nbsp;</b>";
                    }
                    echo "<div class='w3-display-container w3-left w3-padding-small'>";
					echo "<a href='/build/" . $comment['model'] . "#comment" . $comment['id'] . "'><img src='/img/com.jpg' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-card-2 w3-border'></a>";
                    echo "<b class='w3-display-middle w3-text-grey'>" . $truncatedPost . "</b></div>";
                }

                $sql = "SELECT * FROM replies WHERE user = $profileid ORDER BY date DESC";
                $result = $conn2->query($sql);

                echo "</div><div class='w3-row'><h4>post comments</h4><br />";
                if ($result->num_rows === 0) {
                    echo "<b>no post comments yet from this user</b>";
                }
                while ($reply = $result->fetch_assoc()) {
                    $truncatedPost = htmlspecialchars(substr($reply['post'], 0, 50), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
                    if (strlen($reply['post']) > 50) {
                        $truncatedPost .= "<h4>&nbsp;...more&nbsp;</h4>";
                    }
                    echo "<div class='w3-display-container w3-left w3-padding-small'>";
					echo "<a href='/topic/" . $reply['reply'] . "#reply" . $reply['id'] . "'><img src='/img/com.jpg' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-card-2 w3-border'></a>";
                    echo "<b class='w3-display-middle w3-text-grey'>" . $truncatedPost . "</b></div>";
                }
                $conn1->close();
                $conn2->close();
            ?>
        </div>
    </span>

    <?php include('linkbar.php') ?>

</div>

</body>
</html>