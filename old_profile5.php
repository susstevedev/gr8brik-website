<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/com/bbcode.php';

$bbcode = new BBCode;
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
$conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
$conn3 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);

if ($conn->connect_error) {
    exit($conn->connect_error);
}

if (isset($_GET['follow'])) {
    $profile_id = $_GET['user'];

    $sql_follow = "INSERT INTO follow (userid, profileid) VALUES (?, ?)";
    $stmt_follow = $conn->prepare($sql_follow);
    $stmt_follow->bind_param("ii", $id, $profile_id);
    $result = $stmt_follow->execute();
    $stmt_follow->close();

    if ($id != $profile_id) {
        $time = time();
        $content = $profile_id;
        $category = 1;

        $sql_notification = "INSERT INTO notifications (user, profile, timestamp, content, category) VALUES (?, ?, ?, ?, ?)";
        $stmt_notification = $conn->prepare($sql_notification);
        $stmt_notification->bind_param("iisii", $profile_id, $id, $time, $content, $category);
        $stmt_notification->execute();
        $stmt_notification->close();

        $date = time();

        $sql_alert_select = "SELECT alert FROM users WHERE id = ?";
        $stmt_alert_select = $conn->prepare($sql_alert_select);
        $stmt_alert_select->bind_param("i", $profile_id);
        $stmt_alert_select->execute();
        $stmt_alert_select->bind_result($alert);
        $stmt_alert_select->fetch();
        $stmt_alert_select->close();

        if($alert === null){
            $alertnum = 1;
        }else{
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
        echo json_encode(['success' => 'success']);
        exit;
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['error' => $conn->error]);
        exit;
    }
}

if(isset($_POST['unfollow'])) {
    $profile_id = $_GET['user'];
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
    $profile_id = $_GET['user'];
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
    <title>Profile</title>
    <?php include 'header.php' ?>
    
</head>
<body class="w3-container w3-light-blue">

<?php 
    include('navbar.php');
?>

<script>

    $(document).ready(function() {
        /*const finduserid = window.location.href.split('/')[4].match(/\d+/g);
        const userid = finduserid[0];
        
        if(!userid) {
            $(".consoleErrorBox").show();
            $(".consoleError").text("Profile short URI's are deprecated.");

            $("#data-profile-error").show();
            $("#data-error-code").text('301');
            $("#data-error-text").text('Moved Permanently error');
        }*/

        const urlParams = new URLSearchParams(window.location.search);
        const userid = <?php echo $_GET['user'] ?>;

        $(".button-unfollow").click(function(event) {
            $("#unfollow").toggle();
        });

        $.ajax({
                url: "/ajax/profile",
                method: "GET",
                data: { user: userid, sqlfail: urlParams.get('sqlfail') },
                success: function(response) {
                    document.title = response.username + " - Gr8brik";
                    const isLoggedin = response.isLoggedin;

                    if(response.hasBanner === 1) {
                        $("#banner").show();
                        $("#banner").attr("src", `/acc/users/banners/${userid}..jpg`);
                    }

                    $("#picture").attr("src", `/acc/users/pfps/${userid}.jpg`);

                    if(isLoggedin === true) {
                        if(userid != response.tokenuser) {
                            $("#action-buttons").show();
                            if(response.isFollowing === false) {
                                $(".button-unfollow").hide();
                            } else if(response.isFollowing === true) {
                                $("#button-follow").hide();
                            }

                            if(response.canBan === false) {
                                $("#button-ban-user").hide();
                            }
                        } else {
                            $("#data-edit-profile").html(`<a href="/acc/" class="reload"><i class="fa fa-pencil w3-xlarge" aria-hidden="true"></i></a>`);
                        }
                        $("#data-user-actions").show();
                        followed();
                    } else {
                        $("#followedby-wrapper").hide();
                        $("#twitter-wrapper").hide();
                        $("#data-user-actions").hide();

                        if(response.message) {
                            console.log(response.message);
                            $("#data-user-message").show().text(response.message);
                        }
                    }

                    if(response.admin === '1') {
                        $("#data-admin").html(`<span class="fa fa-check w3-red w3-padding-tiny w3-xlarge" title="This user has volunteered to moderate Gr8brik." aria-hidden="true"></span>`);
                    }

                    if(response.verified === '1') {
                        $("#data-verified").html(`<span class="fa fa-check w3-blue w3-padding-tiny w3-xlarge" title="This profile is important." aria-hidden="true"></span>`);
                    }

                    $("#username").html(response.username);
                    $("#data-model-count").text(response.model_count);
                    $("#data-follower-count").text(response.followers);
                    $("#data-following-count").text(response.following);

                    $("#joined-wrapper").html(`Joined ${response.age}`);
                    $("#joined-wrapper").css({
                        "display": "inline",
                        "font-size": "15px",
                        "text-shadow": "0px 0px 0px #fff"
                    })

                    
                    if(response.twitter !== null && response.twitter !== undefined && response.twitter !== '') {
                        $("#twitter-wrapper").html(`<b>|</b>&nbsp;<a id="twitter-link" href="http://twitter.com/${response.twitter}" target="_blank"><span class="w3-large fa fa-twitter"></span>${response.twitter}</a>`);
                        $("#twitter-wrapper").css({
                            "display": "inline",
                            "font-size": "15px",
                            "text-shadow": "0px 0px 0px #fff"
                        })
                    }

                    if(response.description) {
                        $("#description").html(response.description);
                    } else {
                        $("#description").replaceWith('<br />');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    var response = JSON.parse(jqXHR.responseText);
                    console.error('Server status code: ' + textStatus + ' ' + jqXHR.status + ' ' + errorThrown);
                    $(".consoleErrorBox").show();
                    $(".consoleError").text(response.error);
                }
            });
             
            function follow_user() {
                $.ajax({
                    url: "/profile.php",
                    method: "GET",
                    data: { follow: true, user: userid },
                    success: function(response) {
                        if(!alert("Followed user")) {
                            window.location.reload();
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        var response = JSON.parse(jqXHR.responseText);
                        console.error('Server status code: ' + textStatus + ' ' + jqXHR.status + ' ' + errorThrown);
                        alert(response.error);
                    }
                });
            }

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
                                    <a href="/user/${user.userid}?s=${user.random}">
                                        <img src="/acc/users/pfps/${user.userid}.jpg" id="${user.random}" width="15px" height="15px" />
                                    ${user.username}</a>`).join(", ");
                            } else {
                                let first = response.slice(0, 3);
                                let others = response.length - 3;
                                followedBy = first.map(user => `<a href="/user/${user.userid}?s=${user.random}">
                                    <img src="/acc/users/pfps/${user.userid}.jpg" id="${user.random}" width="15px" height="15px" />
                                ${user.username}</a>`).join(", ");
                                followedBy += ` and ${others} others`;
                            }
                        } else {
                            followedBy = "nobody you know";
                        }
                        $("#followedby-wrapper").html(`<b>|</b>&nbsp;Followed by ${followedBy}`);
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

            $("#button-follow").click(function(event) {
                follow_user();
            });
    });
    </script>

    <div id="user-card" class='w3-light-grey w3-card-2 gr8-theme w3-padding-small'>
    <center>
        <img id="banner" style="display: none; width: 100%; height: 25vh; border-radius: 0px;" src="/" />
    </center>
    

    <span style='font-size: 30px; display: inline-block; vertical-align: top; max-width: 100%;'>
        <img id="picture" style="width: 50px; height: 50px; border-radius: 50px;" src="/img/no_image.png" />
        <span id="username">Loading</span>
        <span id="data-verified"></span>
        <span id="data-admin"></span>
        <span id="data-edit-profile"></span>
        <span style="font-size:20px;">
            <span><b id="data-model-count" style="text-decoration: underline dotted;">0</b>&nbsp;creations</span>
            <span><b id="data-follower-count" style="text-decoration: underline dotted;">0</b>&nbsp;followers</span>
            <span><b id="data-following-count" style="text-decoration: underline dotted;">0</b>&nbsp;following</span>
        </span>
    </span>

    <pre id="description"></pre>

    <span id="joined-wrapper"></span>
    <span id="twitter-wrapper"></span>
    <span id="followedby-wrapper"></span>
    <hr style="border-color: inherit; width: 50%; text-align: left; margin-left: 0;" />

    <span id="action-buttons" style="display: none;">
        <button xonclick="document.getElementById('unfollow').style.display='block'" name='unfollow' class='button-unfollow w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink' />
            Unfollow
        </button>&nbsp;

        <!--<input id="button-follow" form="followUser" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" type="submit" value="Follow" name="follow">&nbsp;-->

        <button id="button-follow" name='button-follow' class='w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo' />Follow</button>&nbsp;

        <button id="button-ban-user" onclick=document.getElementById('ban').style.display='block' name='ban' class='w3-btn w3-red w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-pink' />
            Ban
        </button>&nbsp;

        <form id="followUser" action="" method="post"></form>
    </span>

</span></div>

            <div id="unfollow" class="w3-modal">
				<div class="w3-modal-content w3-card-2 w3-light-grey w3-center">
					<div class="w3-container">
						<button xonclick="document.getElementById('unfollow').style.display='none'" class="button-unfollow w3-closebtn w3-red w3-hover-white w3-padding w3-display-topright">&times;</button>
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

            ?>

            <br />
            <h3>
                <span id="data-user-message" style="display: none;"></span>
            </h3>

            <span id="data-user-actions" style="display: none;">

			<a href="#creations" id="creations"></a>
				<h3>creations</h3>
				<div class="w3-row">
				<?php
				
			
				$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

				if ($conn->connect_error) {
					die("Connection failed: " . $conn->connect_error);
				}
				
                $profileid = $_GET['user'];
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

                $profileid = $_GET['user'];
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
                    $profileid = $_GET['user'];
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
            </span>
			<hr />
			<br /><br /><br /><br />

    <?php include('linkbar.php') ?>


</div>

</body>
</html>