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

    $sql = "SELECT * FROM bans WHERE user = '$profile_id'";
    $result2 = $conn->query($sql);
    $row2 = $result2->fetch_assoc();
}

if (isset($_GET['id']) && $_GET['id']) {
    $profile_id = $_GET['id'];
    $sql = "SELECT * FROM users WHERE id = '$profile_id'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $profile_user = $row['username'];

    $sql = "SELECT * FROM bans WHERE user = '$profile_id'";
    $result2 = $conn->query($sql);
    $row2 = $result2->fetch_assoc();
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
        echo "Success!";
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
        echo "Success!";
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
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="/lib/theme.css">
    <script src="/lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="canonical" href="https://www.gr8brik.rf.gd/user/" />
    <meta charset="UTF-8">
    <meta name="description" content="Gr8brik is a block building browser game. No download required">
    <meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no"><!-- ios support -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/img/logo.jpg" />
    <meta name="apple-mobile-web-app-status-bar" content="#f1f1f1" />
    <meta name="theme-color" content="#f1f1f1" />
    
</head>
<body class="w3-container w3-light-blue">

<?php 
    include('navbar.php');
    require_once 'com/bbcode.php';
    $bbcode = new BBCode;
	$conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
    $conn3 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
    
    if ($result->num_rows === 0 || $row2['end_date'] >= time()) {
        echo "<h2>Oops! This profile does not exist.</h2>";
        echo "<button onclick='window.history.go(-1); return false;' class='w3-btn w3-blue w3-hover-white'>Back</button>";
        exit;
    }

    $count_sql = "SELECT COUNT(*) as all_models FROM model WHERE user = $profile_id";
    $count_result = $conn2->query($count_sql);
    $count_row = $count_result->fetch_assoc();
    $model_count = $count_row['all_models'];

    $count_result = $conn->query("SELECT COUNT(*) as following FROM follow WHERE profileid = $profile_id");
    $count_row = $count_result->fetch_assoc();
    $followers = $count_row['following'];

    $count_result = $conn->query("SELECT COUNT(*) as following FROM follow WHERE profileid = $profile_id");
    $count_row = $count_result->fetch_assoc();
    $following = $count_row['following'];

    echo "<div class='w3-light-grey w3-card-2 gr8-theme w3-padding-small'>";
    if($row['admin'] != 0) {
        echo '<b style="font-size: 30px;color: red;">';
    } else {
        echo '<b style="font-size: 30px;">';
    }
    if(file_exists('acc/users/pfps/' . $profile_id . '.jpg')){
        echo '<img id="pfp" src="' . '/acc/users/pfps/' . $profile_id . '.jpg' . '" />&nbsp;';
    }
    echo $row['username'];
    if(isset($_SESSION['username']) && trim($_SESSION['username']) === trim($profile_id)) {
        echo '&nbsp;<a href="/acc/index"><i class="fa fa-pencil" aria-hidden="true"></i></a>';
    }
    echo '</b><br /><b style="font-size:15px;color:#000;">' . $model_count . ' creations - ' . $followers . ' followers - ' . $following . ' following';
    if(!empty($row['twitter'])) {
        echo ' - <a href="http://go.gr8brik.rf.gd/index.php?uri=' . base64_encode('https://twitter.com/intent/user?screen_name=' . $row['twitter']) . '" class="fa fa-twitter w3-xlarge"></a>';
    }
    echo '</b><pre style="font-size: 15px;">' . $bbcode->toHTML(htmlspecialchars($row['description'])) . '</pre>';

    if(isset($_SESSION['username'])) {
        $profileid = $row['id'];
        $sql2 = "SELECT * FROM follow WHERE userid = $id AND profileid = $profileid";
        $result2 = $conn->query($sql2);
        $row2 = $result2->fetch_assoc();
        if ($result2->num_rows > 0) {
            echo "<button onclick=document.getElementById('unfollow').style.display='block' name='unfollow' class='w3-btn w3-red w3-hover-white' /> Unfriend </button>";
        } elseif(trim($_SESSION['username']) != trim($profile_id)) {
            echo '<form id="followUser" action="" method="post"></form>';
            echo '<input form="followUser" class="w3-btn w3-blue w3-hover-white" type="submit" value=" Friend " name="follow">';
        }
                        
        if (trim($_SESSION['username']) != trim($profile_id) && $admin === '1' && $row['admin'] != '1') {
            echo "<button onclick=document.getElementById('ban').style.display='block' name='ban' class='w3-btn w3-red w3-hover-white' /> Ban </button>";
        }
    }
								
			?>
        </div>

            <div id="unfollow" class="w3-modal">
				<div class="w3-modal-content w3-card-2 w3-light-grey w3-center">
					<div class="w3-container">
					
						<span onclick="document.getElementById('unfollow').style.display='none'" class="w3-closebtn w3-red w3-hover-white w3-padding w3-display-topright">&times;</span>
							<form method='post' action=''>
							<h2>Are you sure you want to unfriend @<b><?php echo $profile_user ?></b>?</h2>
							<span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('unfollow').style.display='none'">CLOSE</span> 
							<input type="submit" value="UNFRIEND" name="unfollow" class="w3-btn w3-large w3-white w3-hover-red">
						</form>
					</div>
				</div>
			</div>

            <div id="ban" class="w3-modal">
				<div class="w3-modal-content w3-card-2 w3-light-grey w3-center">
					<div class="w3-container">
					
						<span onclick="document.getElementById('ban').style.display='none'" class="w3-closebtn w3-red w3-hover-white w3-padding w3-display-topright">&times;</span><form method='post' action=''>
							
							<h2>Are you sure you want to ban @<b><?php echo $profile_user ?></b>?</h2>
                            <p><div class="w3-row-padding"><select class="w3-select" name="day">
                                <option value="01" disabled selected>Day</option>
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
                                <option value="1" disabled selected>Month</option>
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
                                <option value="2024" disabled selected>Year</option>
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
                            <textarea name="reason" placeholder="Reason for ban" class="w3-input w3-border w3-mobile" rows="4" cols="50"></textarea>
                            <span name="close" class="w3-btn w3-large w3-white w3-hover-blue" onclick="document.getElementById('ban').style.display='none'">CLOSE</span>
							<input type="submit" value="BAN" name="ban" class="w3-btn w3-large w3-white w3-hover-red">
						</form>
					</div>
				</div>
			</div><br />

			<a href="#creations" id="creations"></a>
				<h3>CREATIONS</h3>
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
					echo "<a href='/build/" . $creation['id'] . "'><img src='/cre/" . $creation['screenshot'] . "' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-border'></a>";
                    echo "<b class='w3-display-middle w3-text-grey'>" . $creation['name'] . "</b></div>";
                }
                $conn->close();
            ?>
			</div>
			<hr />
			<a href="#posts" id="posts"></a>
				<h3>POSTS</h3>
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
					echo "<a href='/topic/" . $topic['id'] . "'><img src='/img/com.jpg' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-border'></a>";
                    echo "<b class='w3-display-middle w3-text-grey'>" . $topic['title'] . "</b></div>";
                }
                $conn->close();
            ?>

			</div>
			<hr />
			<a href="#comments" id="comments"></a>
                <div class="w3-row">
				    <h3>COMMENTS</h3>
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

                    echo "<h4>Creation comments</h4><br />";
                    if ($result->num_rows === 0) {
                        echo "<b>No creation comments yet from @" . urldecode($profile_user) . ".</b>";
                    }
                    while ($comment = $result->fetch_assoc()) {
                        echo "<div class='w3-display-container w3-left w3-padding-small'>";
					    echo "<a href='/build/" . $comment['model'] . "#c" . $comment['id'] . "'><img src='/img/com.jpg' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-border'></a>";
                        echo "<b class='w3-display-middle w3-text-grey'>" . $comment['comment'] . "</b></div>";
                    }

                    $sql = "SELECT * FROM replies WHERE user = $profileid ORDER BY date DESC";
                    $result = $conn2->query($sql);

                    echo "</div><div class='w3-row'><h4>Post comments</h4><br />";
                    if ($result->num_rows === 0) {
                        echo "<b>No post comments yet from @" . urldecode($profile_user) . ".</b>";
                    }
                    while ($reply = $result->fetch_assoc()) {
                        echo "<div class='w3-display-container w3-left w3-padding-small'>";
					    echo "<a href='/topic/" . $reply['reply'] . "#r" . $reply['id'] . "'><img src='/img/com.jpg' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-border'></a>";
                        echo "<b class='w3-display-middle w3-text-grey'>" . $reply['post'] . "</b></div>";
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