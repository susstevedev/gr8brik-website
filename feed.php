<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/acc/classes/user.php';
    isLoggedin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Feed</title>
    <?php include 'header.php' ?>
</head>
<body class="w3-light-blue w3-container">

        <?php 
            /*require_once 'ajax/user.php';
            require_once 'ajax/bbcode.php';
            $bbcode = new BBCode;

            if(!isset($_COOKIE['token'])) {
                echo '<meta http-equiv="refresh" content="0;url=/acc/login.php">';
                exit;
            }

            $id = $token['user'];
            $row = fetchUser($id);

            $user = $row['username'];
            $pwd = $row['password'];
            $email = $row['email'];
            $about = $row['description'];
            $x = $row['twitter'];
            $admin = $row['admin'];
            $alert = $row['alert'];
            $age = $row['age'];
            $pic = $row['image']; */
            
            include 'com/bbcode.php';
            include 'navbar.php';
            include 'acc/panel.php';
            $bbcode = new BBCode;
        ?>

        <div class="w3-center">

        <h4 class="w3-padding w3-red">The feed page will be discontinued in April.<br />&nbsp;The creation's page in the side navigation now shows models from who your following by default.</h4>

        <script>
        function openTab(tab) {
            document.title = "";

            var amount;
            var tabGroup = document.getElementsByClassName("tab");
            for (amount = 0; amount < tabGroup.length; amount++) {
                tabGroup[amount].classList.add("w3-hide");
            }
            var tabElement = document.getElementById(tab);
            var recElement = document.getElementById("recommended");
            var text = document.getElementById("text");
            var user = document.getElementById("gr8-user").innerText;
            let path = window.location.href.split('feed')[0].split('.php')[0].split('?')[0];

            let tabText = tab.replace("tab", "");
            text.innerText = tabText;

            tabElement.classList.remove("w3-hide");
            recElement.classList.add("w3-hide");

            history.pushState({}, null, path);
            document.title = tabText + " on your feed - Gr8brik";
        }
        $(document).ready(function() {
            openTab('creationstab');
        });
        </script>

        <div class="w3-navbar w3-half w3-center" style="flex-direction:row;">
            <a class='w3-btn w3-light-grey w3-quarter w3-hover-blue w3-border w3-border-grey' onclick="openTab('creationstab')">Creations</a>
            <a class='w3-btn w3-light-grey w3-quarter w3-hover-blue w3-border w3-border-grey' onclick="openTab('poststab')">Posts</a>
            <a class='w3-btn w3-light-grey w3-quarter w3-hover-blue w3-border w3-border-grey' onclick="openTab('commentstab')">Comments</a>
            <a class='w3-btn w3-light-grey w3-quarter w3-hover-blue w3-border w3-border-grey w3-padding-small' onclick="openTab('likestab')">Likes <span class="w3-blue w3-tag w3-padding-tiny">NEW</span></a>
        </div>
        <h2 id="text" style="text-transform: capitalize;"></h2>
        
        <h4 id="recommended" class="">An error has occurred</h4>

        <table class="w3-table-all" style="color:black;">
        <div id="creationstab" class="tab w3-hide">
        
        <?php
							
			$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
            $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
			if ($conn->connect_error || $conn2->connect_error) {
				die("Error: " . $conn->connect_error . ", " . $conn2->connect_error);
			}

            $sql = "SELECT profileid FROM follow WHERE userid = $id";
            $result = $conn2->query($sql);
            $followed_users = array();
            while ($row = $result->fetch_assoc()) {
                $followed_users[] = $row['profileid'];
            }
            if ($result->num_rows > 0) {
                echo "<h4>Follow more people to get a better feed. <a href='/users'>Lets go</a>.</h4>";
                $followed_user_ids = implode(',', $followed_users);
                $sql = "SELECT * FROM model WHERE user IN ($followed_user_ids) ORDER BY date DESC";
                $result2 = $conn->query($sql);
                
                $i = 0;
                while ($row2 = $result2->fetch_assoc()) {
                    $userid = $row2['user'];
                    $sql2 = "SELECT * FROM users WHERE id = $userid";
                    $result3 = $conn2->query($sql2);
                    $user = $result3->fetch_assoc();

                    echo "<div class='w3-display-container w3-left w3-padding-tiny'>";
                    echo "<a href='/build/" . $row2['id'] . "'><img src='cre/" . $row2['screenshot'] . "' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-border'></a>";
                    echo "<h4 class='w3-display-bottommiddle w3-padding-tiny w3-card-2 w3-black w3-text-white'>" . $row2['name'] . "</h4>";
                    echo "<b class='w3-display-topright w3-padding-tiny w3-card-2 w3-black w3-text-white'>By <a href='/user/" . $row2['user'] . "'>" . $user['username'] . "</a></b></div>";
                }
            } else {
                echo "<h2>Your not following anyone</h2>";
                echo "<p>Start following people to engage with people and get a custom feed.</p>";
                echo "<a href='/users' class='w3-btn w3-blue w3-hover-white w3-border w3-border-indigo'>Lets go</a><br /><b>Or...</b><br />";
                echo "<a href='/rules' class='w3-btn w3-blue w3-hover-white w3-border w3-border-indigo'>Read The Rules</a>";
            }
			
        echo "</div>";
        ?>

        <div id="poststab" class="tab w3-hide">
        
            <?php
							
			$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
            $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
			if ($conn->connect_error || $conn2->connect_error) {
				die("Error " . $conn->connect_error . ", " . $conn2->connect_error);
			}
		    $sql = "SELECT * FROM posts ORDER BY date DESC";
			$result = $conn->query($sql);

			if ($result->num_rows > 0) {
				$i = 0;
				while ($row = $result->fetch_assoc()) {
                    $userid = $row['user'];
                    $sql2 = "SELECT * FROM users WHERE id = $userid";
                    $result2 = $conn2->query($sql2);
                    $user = $result2->fetch_assoc();

                    $truncatedPost = substr($row['title'], 0, 50);
                    if (strlen($row['title']) > 50) {
                        $truncatedPost .= "...";
                    }

                    echo "<div class='w3-display-container w3-left w3-padding-tiny'>";
					echo "<a href='/topic/" . $row['id'] . "'><img src='img/com.jpg' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-border'></a>";
                    echo "<h4 class='w3-display-bottommiddle w3-padding-tiny w3-card-2 w3-black w3-text-white'>" . $truncatedPost . "</h4>";
                    echo "<b class='w3-display-topright w3-padding-tiny w3-card-2 w3-black w3-text-white'>By <a href='/user/" . $userid . "'>" . $user['username'] . "</a></b></div>";
					if (++$i == 4) break;
				}
			}

            ?>
			
        </div>

        <div id="commentstab" class="tab w3-hide">
        
            <?php
                    $conn1 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
                    $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
                    $conn3 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
                    if ($conn1->connect_error || $conn2->connect_error || $conn3->connect_error) {
                        die("Error: " . $conn1->connect_error . ", " . $conn2->connect_error . " and " . $conn3->connect_error);
                    }

                    $sql1 = "SELECT * FROM comments ORDER BY date DESC";
                    $sql2 = "SELECT * FROM replies ORDER BY date DESC";
                    $result1 = $conn1->query($sql1);
                    $result2 = $conn2->query($sql2);

                    if ($result1->num_rows > 0) {
                        $i = 0;
                        while ($row = $result1->fetch_assoc()) {
                            $truncatedComment = substr($row['comment'], 0, 50);
                            if (strlen($row['comment']) > 50) {
                                $truncatedComment .= "...";
                            }
                            $userid = $row['user'];
                            $sql3 = "SELECT * FROM users WHERE id = $userid";
                            $result3 = $conn3->query($sql3);
                            $user = $result3->fetch_assoc();

                            $replace = array("[img]", "[/img]", "[youtube]", "[/youtube]");
                            $with = array("[url]", "[/url]", "[url]https://www.youtube.com/watch?v=", "[/url]");
                            $truncatedComment = str_replace($replace, $with, $truncatedComment);

                            echo "<div class='w3-display-container w3-left w3-padding-tiny'>";
					        echo "<a href='/build/" . $row['model'] . "'><img src='img/com.jpg' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-border'></a>";
                            echo "<h4 class='w3-display-bottommiddle w3-padding-tiny w3-card-2 w3-black w3-text-white'>" . $bbcode->toHTML($truncatedComment) . "</h4>";
                            echo "<b class='w3-display-topright w3-padding-tiny w3-card-2 w3-black w3-text-white'>By <a href='/user/" . $userid . "'>" . $user['username'] . "</a></b></div>";
                            if (++$i == 3) break;
                        }
                    }

                    if ($result2->num_rows > 0) {
				        $i = 0;
				        while ($row = $result2->fetch_assoc()) {
                            $truncatedComment = substr($row['post'], 0, 50);
                            if (strlen($row['post']) > 50) {
                                $truncatedComment .= "...";
                            }
                            $userid = $row['user'];
                            $sql3 = "SELECT * FROM users WHERE id = $userid";
                            $result3 = $conn3->query($sql3);
                            $user = $result3->fetch_assoc();
                            $username = urlencode(strtolower($user['username']));

                            $replace = array("[img]", "[/img]", "[youtube]", "[/youtube]");
                            $with = array("[url]", "[/url]", "[url]https://www.youtube.com/watch?v=", "[/url]");
                            $truncatedComment = str_replace($replace, $with, $truncatedComment);

                            $post_id = $row['reply'];
                            $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
                            $limit = 10;
                            $offset = ($page - 1) * $limit;

                            $count_sql = "SELECT COUNT(*) as reply_count FROM replies WHERE reply = $post_id";
                            $count_result = $conn2->query($count_sql);
                            if (!$count_result) {
                                die("Failed: " . $conn->error);
                            }
                            $count_row = $count_result->fetch_assoc();
                            $reply_count = $count_row['reply_count'];

                            $total_pages = ceil($reply_count / $limit);

                            echo "<div class='w3-display-container w3-left w3-padding-tiny'>";
					        echo "<a href='/topic/" . $row['reply'] . "&p=" . $total_pages . "'><img src='img/com.jpg' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-border'></a>";
                            echo "<h4 class='w3-display-bottommiddle w3-padding-tiny w3-card-2 w3-black w3-text-white'>" . $bbcode->toHTML($truncatedComment) . "</h4>";
                            echo "<b class='w3-display-topright w3-padding-tiny w3-card-2 w3-black w3-text-white'>By <a href='/user/" . $userid . "'>" . $user['username'] . "</a></b></div>";
					        if (++$i == 3) break;
				        }
			        }

            ?>

            </div>

            <div id="likestab" class="tab w3-hide">

            <?php
							
			$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
            $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
			if ($conn->connect_error || $conn2->connect_error) {
				exit($conn->connect_error . ", " . $conn2->connect_error);
			}

                $yourid = $token['user'];
                $sql = "SELECT * FROM votes WHERE user = '$yourid' ORDER BY id DESC";
                $result = $conn->query($sql);
                
                $i = 0;
                while ($row = $result->fetch_assoc()) {

                    $modelid = $row['creation'];
                    $sql3 = "SELECT * FROM model WHERE id = $modelid";
                    $result3 = $conn->query($sql3);
                    $model = $result3->fetch_assoc();

                    $userid = $model['user'];
                    $sql2 = "SELECT * FROM users WHERE id = $userid";
                    $result2 = $conn2->query($sql2);
                    $user = $result2->fetch_assoc();

                    echo "<div class='w3-display-container w3-left w3-padding-tiny'>";
                    echo "<a href='/build/" . $modelid . "'><img src='cre/" . $model['screenshot'] . "' width='320' height='240' loading='lazy' class='w3-hover-shadow w3-border'></a>";
                    echo "<h4 class='w3-display-bottommiddle w3-padding-tiny w3-card-2 w3-black w3-text-white'>" . $model['name'] . "</h4>";
                    echo "<b class='w3-display-topright w3-padding-tiny w3-card-2 w3-black w3-text-white'>By <a href='/user/" . $model['user'] . "'>" . $user['username'] . "</a></b></div>";
                }
			
        ?>
			
        </div></table><br /><br />


    <?php include('linkbar.php'); ?>


</body>
</html>