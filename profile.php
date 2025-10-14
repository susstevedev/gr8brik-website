<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/profile.php';

if(loggedin()) {
    $userid = $token['user'];
}

if(!isset($_GET['id'])) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';

$data = json_decode(fetch_profile($_GET['id'], $_SESSION['csrf']), true);

if ($data === null) {
    exit('An unknown error occured');
}

if($data['message'] != null | !empty($data['message'])) {
    $error = $data['message'];
    $data['username'] = 'User';
}

if (isset($_POST['follow'])) {
    if(!isset($token['user'])) {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "Please login to follow this user.";
    }

    $profile_id = (int)$_GET['id'];
    $time = time();

    if((int)$token['user'] === $profile_id) {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "You cannot follow yourself.";
    }

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
        $message = "Followed this user with success!";
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "An error occured while following this user.";
    }
}

if(isset($_POST['unfollow'])) {
    if(!isset($token['user'])) {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "Please login to follow this user.";
    }

    $profile_id = (int)$_GET['id'];

    if((int)$token['user'] === $profile_id) {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "You cannot follow yourself.";
    }

    $sql = "DELETE FROM follow WHERE userid = '$userid' AND profileid = '$profile_id'";
    $result = $conn->query($sql);
    if ($result) {
        header("HTTP/1.0 200 OK");
        $message = "Unfollowed this user with success!";
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "An error occured while unfollowing this user.";
    }
}

if (isset($_POST['block'])) {
    if(!isset($token['user'])) {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "Please login to follow this user.";
    }

    $profile_id = (int)$_GET['id'];
    $time = time();

    if((int)$token['user'] === $profile_id) {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "You cannot follow yourself.";
    }

    $sql_block = "INSERT INTO user_blocks (userid, profileid, date) VALUES (?, ?, ?)";
    $stmt_block = $conn->prepare($sql_block);
    $stmt_block->bind_param("iii", $userid, $profile_id, $time);
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
    if(!isset($token['user'])) {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "Please login to follow this user.";
    }

    $profile_id = (int)$_GET['id'];

    if((int)$token['user'] === $profile_id) {
        header("HTTP/1.0 500 Internal Server Error");
        $error = "You cannot follow yourself.";
    }

    $sql = "DELETE FROM user_blocks WHERE userid = '$userid' AND profileid = '$profile_id'";
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
    $profile_id = (int)$_GET['id'];
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $day = $_POST['day'];
	$month = $_POST['month'];
	$year = $_POST['year'];
	$ban_date = mktime(0, 0, 0, $month, $day, $year);
	$duration = $ban_date - time();
    $start_date = time();
    $end_date = $start_date + $duration;

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        exit('Invalid user ID!');
    }

    if ($duration <= 0) {
        exit('Ban date must be in the future!');
    }
		
    if($users_row['admin'] != '0') {
        $sql = "INSERT INTO bans (user, reason, start_date, end_date) VALUES ($profile_id, '$reason', $start_date, $end_date)";
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

if(isset($_POST['delete'])) {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        exit('Invalid user ID!');
    }

    $profile_id = (int)$_GET['id'];
    $today = date("Y-m-d H:i:s");
		
    if($users_row['admin'] != '0') {
        $sql = "UPDATE users SET username = NULL, blog_user_id = NULL, deactive = '$today' WHERE id = $profile_id";
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
        const userid = '<?php echo $_GET['id'] ?>';
                               
        $(document).ready(function() {
            function followed() {
                $.ajax({
                    url: "/ajax/profile",
                    method: "GET",
                    //data: { follow: userid },
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
                                followedBy = first.map(user => `<a href="/profile?id=${user.userid}">
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
                <span><b id="data-model-count"><?php echo $data['model_count'] ?></b>&nbsp;creations</span>
                <span><b id="data-follower-count"><?php echo $data['followers'] ?></b>&nbsp;followers</span>
                <span><b id="data-following-count"><?php echo $data['following'] ?></b>&nbsp;following</span>
                <span><b id="data-view-count"><?php echo $data['views'] ?></b>&nbsp;views</span>
                <span><b id="data-like-count"><?php echo $data['likes'] ?></b>&nbsp;likes</span>
            </span>
        </span>

        <pre id="description"><?php echo $data['description'] ?></pre>

        <span id="joined-wrapper" style="display: inline; font-size: 15px; text-shadow: 0px 0px 0px #fff">Registered <?php echo time_ago($data['age']) ?></span>
        <?php if(!empty($data['twitter'])) { ?>
            <b>-</b>
            <span id="twitter-wrapper" style="display: inline; font-size: 15px; text-shadow: 0px 0px 0px #fff">
                <a id="twitter-link" href="https://twitter.com/<?php echo $data['twitter'] ?>" target="_blank">
                    <!-- <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-twitter-x" viewBox="0 0 16 16">
                        <path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865z"/>
                    </svg> -->
                    <i class="fa fa-twitter" aria-hidden="true"></i>
                    <?php echo $data['twitter'] ?>
                </a>
            </span>
        <?php } ?>
        <b>-</b>
        <span id="followedby-wrapper" style="display: inline; font-size: 15px; text-shadow: 0px 0px 0px #fff"></span><br />

        <?php if(loggedin()) { ?>
            <?php if($token['user'] != trim($_GET['id'])) { ?>
            <span id="action-buttons">
                <?php if($data['isFollowing'] === true) { ?>
                    <button onclick='document.getElementById("unfollow").style.display="block"' name="unfollow" class="button-unfollow w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink" />
                        Unfollow User
                    </button>&nbsp;
                <?php } elseif($data['isFollowing'] === false) { ?>
                    <input id="button-follow" form="followUser" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" type="submit" value="Follow User" name="follow">&nbsp;
                <?php } ?>

                <div class="w3-dropdown-hover">
                    <button class="gr8-theme w3-btn w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-gray">More...</button>
                    <div class="w3-dropdown-content gr8-theme w3-bar-block w3-padding-small w3-border w3-border-gray w3-round-small">
                        <?php if($data['blockedUser'] === false) { ?>
                            <button onclick='document.getElementById("block").style.display="block"' name="block" class="w3-bar-item w3-button" />
                                Block User
                            </button>
                        <?php } elseif($data['blockedUser'] === true) { ?>
                            <input id="button-unblock" form="unblockUser" class="w3-bar-item w3-button" type="submit" value="Unblock User" name="unblock">
                        <?php } ?>

                        <?php if($users_row['admin'] != (int)'0') { ?>
                            <button id="button-ban-user" onclick='document.getElementById("ban").style.display="block"' name="ban" class="w3-bar-item w3-button" />
                                Ban User
                            </button>
                            <button id="button-delete-user" onclick='document.getElementById("delete").style.display="block"' name="delete" class="w3-bar-item w3-button" />
                                Delete User
                            </button>
                        <?php } ?>
                    </div>
                </div>

                <form id="followUser" action="" method="post"></form>
                <form id="unblockUser" action="" method="post"></form>
            </span>
            <?php } else { ?>
                <a href="/acc/index.php" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" />Edit Profile</a>&nbsp;
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
        <div id="delete" class="w3-modal">
            <div class="w3-modal-content w3-card-2 w3-light-grey w3-center">
                <div class="w3-container">
                    <span onclick="document.getElementById('delete').style.display='none'" class="w3-button w3-large w3-red w3-hover-white w3-display-topright">&times;</span>
                        <form method='post' action=''>
                        <h2>Are you sure you want to delete this user?</h2>
                        <span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('delete').style.display='none'">No</span> 
                        <input type="submit" value="Yes" name="delete" class="w3-btn w3-large w3-white w3-hover-red">
                    </form>
                </div>
            </div>
        </div>

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
                $stmt = $conn->prepare("SELECT * FROM model WHERE user = ? ORDER BY date DESC");
                $stmt->bind_param("i", $profileid);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($creation = $result->fetch_assoc()) {
                    if (empty($creation['name'])) {
                        $creation['name'] = $data['username'] . "'s creation";
                    }

                    $truncatedName = htmlspecialchars(substr($creation['name'], 0, 30));
                    if (strlen($creation['name']) >= 30) {
                        $truncatedName .= '...';
                    }

                    echo "<div class='w3-display-container w3-left w3-padding'>";
                    echo "<a href='/creation.php?id=" . $creation['id'] . "'><img src='/cre/" . $creation['screenshot'] . "' width='320px' height='240px' loading='lazy' class='w3-card-2 w3-hover-shadow'></a>";
                    echo "<div class='w3-card-2 gr8-theme w3-light-grey w3-padding-small'><h4>" . $truncatedName . "</h4>";
                    echo "<span>By <a href='/profile.php?id=" . $_GET['id'] . "'>" . $data['username'] . "</a> on " . date("D, M d, Y", strtotime($creation['date'])) . "</span>";
                    echo "</div></div>";
                }

                $stmt->close();
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
                $sql = "SELECT * FROM messages WHERE userid = $profileid AND parent = 0 ORDER BY timestamp DESC";
                $result = $conn->query($sql);

                while ($topic = $result->fetch_assoc()) {
                    $truncatedName = htmlspecialchars(substr($topic['title'], 0, 30));
                    if (strlen($topic['title']) >= 30) {
                        $truncatedName .= '...';
                    }

                    echo "<div class='w3-display-container w3-left w3-padding'>";
                    echo "<a href='/com/view.php?id=" . $topic['id'] . "'><img src='/img/com.jpg' width='320px' height='240px' loading='lazy' class='w3-card-2 w3-hover-shadow'></a>";
                    echo "<div class='w3-card-2 gr8-theme w3-light-grey w3-padding-small'><h4>" . $truncatedName . "</h4>";
                    echo "<span>By <a href='/profile.php?id=" . $_GET['id'] . "'>" . $data['username'] . "</a> on " . date("D, M d, Y", strtotime($topic['timestamp'])) . "</span>";
                    echo "</div></div>";
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

                echo "<h4>Replies to creations</h4><br />";
                if ($result->num_rows === 0) {
                    echo "<b>Nothing here yet</b>";
                }
                while ($comment = $result->fetch_assoc()) {
                    $truncatedPost = htmlspecialchars(substr($comment['comment'], 0, 30));
                    if (strlen($comment['comment']) >= 30) {
                        $truncatedPost .= '...';
                    }

                    echo "<div class='w3-display-container w3-left w3-padding'>";
                    echo "<a href='/creation.php?id=" . $comment['model'] . "#comment" . $comment['id'] . "'><img src='/img/creations.jpg' width='320px' height='240px' loading='lazy' class='w3-card-2 w3-hover-shadow'></a>";
                    echo "<div class='w3-card-2 gr8-theme w3-light-grey w3-padding-small'><h4>" . $truncatedPost . "</h4>";
                    echo "<span>By <a href='/profile.php?id=" . $_GET['id'] . "'>" . $data['username'] . "</a> on " . date("D, M d, Y", (int)$comment['date']) . "</span>";
                    echo "</div></div>";
                }

                $sql = "SELECT * FROM messages WHERE userid = $profileid AND parent != 0 ORDER BY timestamp DESC";
                $result = $conn2->query($sql);

                echo "</div><div class='w3-row'><h4>Replies to posts</h4><br />";
                if ($result->num_rows === 0) {
                    echo "<b>Nothing here yet</b>";
                }
                while ($reply = $result->fetch_assoc()) {
                    $truncatedPost = htmlspecialchars(substr($reply['content'], 0, 30));
                    if (strlen($reply['content']) >= 30) {
                        $truncatedPost .= '...';
                    }

                    echo "<div class='w3-display-container w3-left w3-padding'>";
                    echo "<a href='/com/view.php?id=" . $reply['parent'] . "#reply" . $reply['id'] . "'><img src='/img/com.jpg' width='320px' height='240px' loading='lazy' class='w3-card-2 w3-hover-shadow'></a>";
                    echo "<div class='w3-card-2 gr8-theme w3-light-grey w3-padding-small'><h4>" . $truncatedPost . "</h4>";
                    echo "<span>By <a href='/profile.php?id=" . $_GET['id'] . "'>" . $data['username'] . "</a> on " . date("D, M d, Y", strtotime($reply['timestamp'])) . "</span>";
                    echo "</div></div>";
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