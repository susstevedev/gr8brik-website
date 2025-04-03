<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';


$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    exit($conn->connect_error);
}

if (isset($_GET['user']) && $_GET['user']) {
    $profile_user = rawurldecode($_GET['user']);
    $sql = "SELECT * FROM users WHERE username = '$profile_user'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $profile_id = $row['id'];
    $error = false;

    $sql = "SELECT * FROM bans WHERE user = '$profile_id'";
    $result2 = $conn->query($sql);

    while ($row2 = $result2->fetch_assoc()) {
        if ($result2->num_rows > 0 && $row2['end_date'] >= time()) {
            $error = true;
            $errorText = "This account has been suspended.";
            $errorCode = "403";
        }
    }
    
    if ($result->num_rows < 0 || empty($row['username'])) {
        $error = true;
        $errorText = "This account does not exist.";
        $errorCode = "404";
    }

    if (!empty($row['deactive'])) {
        $error = true;
        $errorText = "This account has been deactivated.";
        $errorCode = "410";
    }
}

if (isset($_GET['id']) && $_GET['id']) {
    $profile_id = $_GET['id'];
    $sql = "SELECT * FROM users WHERE id = '$profile_id'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $profile_user = $row['username'];
    $error = false;

    $sql = "SELECT * FROM bans WHERE user = '$profile_id'";
    $result2 = $conn->query($sql);

    while ($row2 = $result2->fetch_assoc()) {
        if ($result2->num_rows > 0 && $row2['end_date'] >= time()) {
            $error = true;
            $errorText = "This account has been suspended.";
            $errorCode = "403";
        }
    }
    
    if ($result->num_rows < 0 || empty($row['username'])) {
        $error = true;
        $errorText = "This account does not exist.";
        $errorCode = "404";
    }

    if (!empty($row['deactive'])) {
        $error = true;
        $errorText = "This account has been deactivated.";
        $errorCode = "410";
    }
}

if (isset($_POST['follow'])) {
    $profile_id = $row['id'];
    
    $notifName = " followed you!";
    $notifPost = "Click to view " . $user . "s profile.";
    $notifTime = time();
    $notifUrl = "/profile.php?id=" . $id;

    $sql = "INSERT INTO follow (userid, profileid) VALUES ($id, $profile_id)";
    $result = $conn->query($sql);

    $sql = "INSERT INTO notifications (user, name, profile, post, timestamp, url) VALUES ('$profile_id', '$notifName', '$id', '$notifPost', '$notifTime', '$notifUrl')";
    $result = $conn->query($sql);

    $sql = "SELECT alert FROM users WHERE id = $profile_id";
    $result2 = $conn->query($sql);
    $row2 = $result2->fetch_assoc();
    $current_alert = $row2['alert'];

    $new_alert = $current_alert + 1;

    $result = $conn->query("UPDATE users SET alert = '$new_alert' WHERE id = $profile_id");

    if ($result) {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']);
        exit;
    } else {
        echo $conn->error;
        exit;
    }

    $stmt->close();
    $conn->close();
    die;
}

if(isset($_POST['unfollow'])) {
    $profile_id = $row['id'];
    $sql = "DELETE FROM follow WHERE userid = '$id' AND profileid = '$profile_id'";
    $result = $conn->query($sql);
    if ($result) {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']);
        exit;
    } else {
        echo $conn->error;
        exit;
    }
}
if(isset($_POST['ban'])) {
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $day = $_POST['day'];
	$month = $_POST['month'];
	$year = $_POST['year'];
	$ban_date = mktime(0, 0, 0, $month, $day, $year);
	$duration = $ban_date - time();
    $start_date = time();
    $end_date = $start_date + $duration;
		
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $row['username'] ?></title>
    <?php include 'header.php' ?>
    
</head>
<body class="w3-container w3-light-blue">

<?php 
    include('navbar.php');
    require_once 'com/bbcode.php';
    $bbcode = new BBCode;
	$conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
    $conn3 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);

    if($result->num_rows > 0) {
        $count_sql = "SELECT COUNT(*) as all_models FROM model WHERE user = $profile_id";
        $count_result = $conn2->query($count_sql);
        $count_row = $count_result->fetch_assoc();
        $model_count = $count_row['all_models'];

        $count_result = $conn->query("SELECT COUNT(*) as following FROM follow WHERE profileid = $profile_id");
        $count_row = $count_result->fetch_assoc();
        $followers = $count_row['following'];

        $count_result = $conn->query("SELECT COUNT(*) as following FROM follow WHERE userid = $profile_id");
        $count_row = $count_result->fetch_assoc();
        $following = $count_row['following'];
    }

    if($error === true) {
        $banner = null;
        $model_count = null;
        $followers = null;
        $following = null;
        $row['admin'] = 0;
        $row['verified'] = 0;
        $pfp = '/img/no_image.png';
        $row['description'] = null;
        $row['twitter'] = null;
        echo '<script>
        $(document).ready(function() {
            $(".consoleErrorBox").show();
            $(".consoleError").text("' . $errorText . '");
        });
        </script>';
    } else {
        $pfp = '/ajax/pfp?method=image&id=' . base64_encode($profile_id);
        $banner = '/acc/users/banners/' . $profile_id . '..jpg';
    }

    echo "<div class='w3-light-grey w3-card-2 gr8-theme w3-padding-small'>";
    if(file_exists('acc/users/banners/' . $profile_id . '..jpg') || $error === true) {
        echo '<div style="background-image: url(' . $banner . '); height: 20%; background-repeat: no-repeat; background-size: cover; background-position: 50% 50%;">';
    } else {
        echo '<div>';
    }

    if(file_exists('acc/users/pfps/' . $profile_id . '.jpg') || $error === true){
        echo '<img style="width: 50px; height: 50px; border-radius: 50px;" src="' . $pfp .  '" />&nbsp;';
    }
    echo "<span style='font-size: 30px; display: inline-block; vertical-align: top; max-width: 100%;'><b>" . $row['username'] . "</b>";
    if($row['verified'] != 0) {
        echo '&nbsp;<i class="fa fa-check w3-blue w3-xlarge"></i>';
    }
    if($row['admin'] != 0) {
        echo '&nbsp;<i class="fa fa-check w3-red w3-xlarge"></i>';
    }
    if(isset($_SESSION['username']) && trim($_SESSION['username']) === trim($profile_id)) {
        echo '&nbsp;<a href="/acc/index" class="reload"><i class="fa fa-pencil" aria-hidden="true"></i></a>';
    }
    
    echo '<b style="font-size:20px;" class="w3-padding-tiny">
    <span style="text-decoration: underline dotted;">' . $model_count . '&nbsp;creations</span>
    <span style="text-decoration: underline dotted;">' . $followers . '&nbsp;followers</span>
    <span style="text-decoration: underline dotted;">' . $following . '&nbsp;following</span></b>';

    //echo "<span class='margin: 0px 0px 0px 75px; display: inline-block;'></span>";
    if(!empty($row['description'])) {
        echo '<span><pre>' . $bbcode->toHTML(htmlspecialchars($row['description'])) . '</pre></span>';
    }
    echo '</div>';

    echo '<span style="font-size: 15px; text-shadow: 0px 0px 0px #fff;" class="w3-text-dark-grey">Joined ' . $row['age'] . '</span>&nbsp;|&nbsp;';
    
    if(!empty($row['twitter'])) {
        echo '<span style="font-size: 15px; text-shadow: 0px 0px 0px #fff;" class="w3-text-dark-grey">
        <a href="https://twitter.com/' . $row['twitter'] . '" target="_blank"><span class="w3-large">𝕏</span>&nbsp;/&nbsp;' . $row['twitter'] . '</a></span>&nbsp;|&nbsp;';
    }

    echo '<span style="font-size: 15px; text-shadow: 0px 0px 0px #fff;" class="w3-text-dark-grey">Followed by <span id="followedby"></span>&nbsp;</span><br />';

    if(isset($_SESSION['username'])) {

        $profileid = $row['id'];
        $sql2 = "SELECT * FROM follow WHERE userid = $id AND profileid = $profileid";
        $result2 = $conn->query($sql2);
        $row2 = $result2->fetch_assoc();
        if ($result2->num_rows > 0) {
            echo "<button onclick=document.getElementById('unfollow').style.display='block' name='unfollow' class='w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink' />Unfollow</button>&nbsp;";
        } elseif(trim($_SESSION['username']) != trim($profile_id)  && $error === false) {
            echo '<form id="followUser" action="" method="post"></form>';
            echo '<input form="followUser" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" type="submit" value="Follow" name="follow">&nbsp;';
        }

        if (trim($_SESSION['username']) != trim($profile_id) && $admin === '1' && $row['admin'] != '1' && $error === false) {
            echo "<button onclick=document.getElementById('ban').style.display='block' name='ban' class='w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink' />Ban</button>";
        }
    }

    echo "</span></div>";
								
?>
    <script>
    $(document).ready(function() {
        $(".consoleErrorBox").show();
        $(".consoleError").text('Profile short URIs will be deprecated in May. Please go to http://www.gr8brik.rf.gd/user/' + <?php echo $profile_id ?> + ' to view this profile later.');
            $.ajax({
                url: "/ajax/profile",
                method: "GET",
                data: { follow: <?php echo $profile_id ?> },
                success: function(response) {
                    let followedBy = "";
                    if (response.length > 0) {
                        if (response.length <= 3) {
                            followedBy = response.map(user => `<a href="/user/${user.userid}"><img src="/ajax/pfp?method=image&id=${btoa(user.userid)}" width="15px" height="15px" />${user.username}</a>`).join(", ");
                        } else {
                            let first = response.slice(0, 3);
                            let others = response.length - 3;
                            followedBy = first.map(user => `<a href="/user/${user.userid}"><img src="/ajax/pfp?method=image&id=${btoa(user.userid)}" width="15px" height="15px" />${user.username}</a>`).join(", ");
                            followedBy += ` and ${others} others`;
                        }
                    } else {
                        followedBy = "nobody you follow";
                    }
                    $("#followedby").html(followedBy);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    var response = JSON.parse(jqXHR.responseText);
                    console.error('Server status code: ' + textStatus + ' ' + jqXHR.status + ' ' + errorThrown);
                    alert(response.error);
                }
            });
    });
    </script>

            <div id="unfollow" class="w3-modal">
				<div class="w3-modal-content w3-card-2 w3-light-grey w3-center">
					<div class="w3-container">
					
						<span onclick="document.getElementById('unfollow').style.display='none'" class="w3-closebtn w3-red w3-hover-white w3-padding w3-display-topright">&times;</span>
							<form method='post' action=''>
							<h2>Are you sure you want to unfollow this user?</h2>
							<span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('unfollow').style.display='none'">No</span> 
							<input type="submit" value="Yes" name="unfollow" class="w3-btn w3-large w3-white w3-hover-red">
						</form>
					</div>
				</div>
			</div>

            <?php

            if($admin != 0) {

            echo '<div id="ban" class="w3-modal">
				<div class="w3-modal-content w3-card-2 w3-light-grey w3-center">
					<div class="w3-container">
					
						<span onclick=document.getElementById("ban").style.display="none" class="w3-closebtn w3-red w3-hover-white w3-padding w3-display-topright">&times;</span><form method="post" action="">
							
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
                            <span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick=document.getElementById("ban").style.display="none">No</span>
							<input type="submit" value="Yes" name="ban" class="w3-btn w3-large w3-white w3-hover-red">
						</form>
					</div>
				</div>
			</div><br />';
            }

            if ($error === true) {
                echo "<center>
                        <h4>An error occured (code " . $errorCode . ")</h4>
                    </center>";
                exit;
            }

            ?>

			<a href="#creations" id="creations"></a>
				<h3>creations</h3>
				<div class="w3-row">
				<?php
				
			
				$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

				if ($conn->connect_error) {
					die("Connection failed: " . $conn->connect_error);
				}
				
                $profileid = $row['id'];
                $sql = "SELECT * FROM model WHERE user = $profileid ORDER BY date DESC";
                $result = $conn->query($sql);

                while ($creation = $result->fetch_assoc()) {
                    echo "<div class='w3-display-container w3-left w3-padding-small'>";
					echo "<a href='/build/" . $creation['id'] . "'><img src='/cre/" . $creation['screenshot'] . "' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-card-2 w3-border'></a>";
                    echo "<b class='w3-display-middle w3-text-grey'>" . $creation['name'] . "</b></div>";
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

                $profileid = $row['id'];
                $sql = "SELECT * FROM posts WHERE user = $profileid ORDER BY date DESC";
                $result = $conn->query($sql);

                while ($topic = $result->fetch_assoc()) {
                    echo "<div class='w3-display-container w3-left w3-padding-small'>";
					echo "<a href='/topic/" . $topic['id'] . "'><img src='/img/com.jpg' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-card-2 w3-border'></a>";
                    echo "<b class='w3-display-middle w3-text-grey'>" . $topic['title'] . "</b></div>";
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
                    $profileid = $row['id'];
                    $sql = "SELECT * FROM comments WHERE user = $profileid ORDER BY id DESC";
                    $result = $conn1->query($sql);

                    echo "<h4>creation comments</h4><br />";
                    if ($result->num_rows === 0) {
                        echo "<b>no creation comments yet from this user</b>";
                    }
                    while ($comment = $result->fetch_assoc()) {
                        $truncatedPost = substr($comment['comment'], 0, 50);
                        if (strlen($comment['comment']) > 50) {
                            $truncatedPost .= "<b class='w3-large'>&nbsp;...more&nbsp;</b>";
                        }
                        echo "<div class='w3-display-container w3-left w3-padding-small'>";
					    echo "<a href='/build/" . $comment['model'] . "#c" . $comment['id'] . "'><img src='/img/com.jpg' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-card-2 w3-border'></a>";
                        echo "<b class='w3-display-middle w3-text-grey'>" . $truncatedPost . "</b></div>";
                    }

                    $sql = "SELECT * FROM replies WHERE user = $profileid ORDER BY date DESC";
                    $result = $conn2->query($sql);

                    echo "</div><div class='w3-row'><h4>post comments</h4><br />";
                    if ($result->num_rows === 0) {
                        echo "<b>no post comments yet from this user</b>";
                    }
                    while ($reply = $result->fetch_assoc()) {
                        $truncatedPost = substr($reply['post'], 0, 50);
                        if (strlen($reply['post']) > 50) {
                            $truncatedPost .= "<b class='w3-large'>&nbsp;...more&nbsp;</b>";
                        }
                        echo "<div class='w3-display-container w3-left w3-padding-small'>";
					    echo "<a href='/topic/" . $reply['reply'] . "#r" . $reply['id'] . "'><img src='/img/com.jpg' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-card-2 w3-border'></a>";
                        echo "<b class='w3-display-middle w3-text-grey'>" . $truncatedPost . "</b></div>";
                    }
                    $conn1->close();
                    $conn2->close();
                ?>
                </div>
			<hr />
			<br /><br /><br /><br />

    <?php include('linkbar.php') ?>


</div>

</body>
</html>