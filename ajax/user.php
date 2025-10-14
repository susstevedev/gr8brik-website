<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/config.php';
session_start();
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
$loggedin = loggedin();

if(isset($_GET['ajax'])) {
    error_reporting(0);
}

if(loggedin() === true) {
    if (rand(1, 10) === 1) {
        regenerate_session();
        delete_old_sessions();
        //update_auth_timestamp();
        delete_inactive_users();
    }
}

function login() {
    global $conn, $id, $token, $tokendata, $users_row;

    if (loggedin() === true) {
        $sessionid = $_COOKIE['token'];

        $session_stmt = $conn->prepare("SELECT user, timestamp FROM sessions WHERE id = ?");
        $session_stmt->bind_param("s", $sessionid);
        $session_stmt->execute();
        $tokendata = $session_stmt->get_result();

        if($tokendata->num_rows != 0) {
            $token = $tokendata->fetch_assoc();
            $id = $token['user'];
            $_SESSION['userid'] = $token['user'];

            $user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $user_stmt->bind_param("i", $id);
            $user_stmt->execute();
            $user_res = $user_stmt->get_result();

            if($user_res->num_rows != 0) {
                global $user, $pwd, $email, $about, $x, $admin, $alert, $age, $changed, $loggedin;

                $users_row = $user_res->fetch_assoc();
                $user = $users_row['username'];
                $pwd = $users_row['password'];
                $email = $users_row['email'];
                $about = $users_row['description'];
                $x = $users_row['twitter'];
                $admin = (string)$users_row['admin'];
                $alert = (int)$users_row['alert'];
                $age = $users_row['age'];
               	$changed = $users_row['changed'];
                $loggedin = loggedin();

                return true;
            }
        }
    }

    return false;
}
login();

/*
function update_auth_timestamp() {
    global $conn, $token, $tokendata;
    $time = time();
    $ip = $_SERVER['REMOTE_ADDR'];
    $sessionid = $_COOKIE['token'];

    $stmt = $conn->prepare("UPDATE sessions SET timestamp = ?, login_from = ? WHERE id = ?");
    $stmt->bind_param("sss", $time, $ip, $sessionid);
    return $stmt->execute();
}
*/

function get_warn_status($id) {
    global $conn;
    if(loggedin()) {
    	$warning_stmt = $conn->prepare("SELECT * FROM warnings WHERE user = ? AND seen = 0 LIMIT 1");
        $warning_stmt->bind_param("i", $id);
        $warning_stmt->execute();
        $warning_stmt = $warning_stmt->get_result();
                
        if($warning_stmt->num_rows != 0) {
            $warning_row = $warning_stmt->fetch_assoc();
            
            $json = array(
                'status' => "yes", 
                'text' => "Your Gr8Brik account has a warning for the following reason",
                'reason' => htmlspecialchars($warning_row['reason'])
            );
        } else {
            $json = array(
                'status' => "no", 
            );
        }
        return $json;
    }
}

function seen_warn_status($id) {
    global $conn;
    if(loggedin()) {
    	$warning_stmt = $conn->prepare("UPDATE warnings SET seen = 1 WHERE user = ? LIMIT 1");
        $warning_stmt->bind_param("i", $id);
                
        if($warning_stmt->execute()) {
            return true;
        }
    }
   	return false;
}

if(isset($_GET['get_warn_status']) && basename($_SERVER['PHP_SELF']) === "user.php") {
    header("Content-type: application/json");
    $id = (int)$_SESSION['userid'];
    echo json_encode(get_warn_status($id));
	exit;
}

if(isset($_GET['seen_warn_status']) && basename($_SERVER['PHP_SELF']) === "user.php") {
    header("Content-type: application/json");
    $id = (int)$_SESSION['userid'];
    echo json_encode(seen_warn_status($id));
	exit;
}

if(isset($_GET['ajax'])) {
    header('Content-type: application/json');
    if(loggedin() === true) {
        $logindata = json_encode([
            'success' => true, 
            'id' => $id,  
            'user' => $user, 
            'alert' => $alert, 
        ]);
        echo $logindata;
        exit;
    } else {
        echo json_encode(['error' => 'User is not authenticated']);
        exit;
    }
}

function logout($redirect) {
    global $conn, $token;
    $sessionid = $token['id'];

    if (isset($sessionid)) {
        $stmt = $conn->prepare("DELETE FROM sessions WHERE id = ? LIMIT 1");
        $stmt->bind_param("s", $sessionid);
        $stmt->execute();
        $stmt->close();
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_unset();
    }

    if(isset($_COOKIE['token'])) {
        unset($_COOKIE['token']); 
        setcookie('token', '', time()-3600, "/", ".gr8brik.rf.gd");
    }

    if(isset($_GET['ajax'])) {
        header("HTTP/1.0 403 Forbidden");
        echo json_encode(['error' => 'Invalid session token provided']);
        exit;
    }

    if($redirect === true) {
        header('Location: /list.php');
        exit;
    }
}

function regenerate_session() {
    global $conn;

    if (loggedin()) {
        $oldid = $_COOKIE['token'];

        $stmt = $conn->prepare("SELECT timestamp FROM sessions WHERE id = ? LIMIT 1");
        $stmt->bind_param("s", $oldid);
        $stmt->execute();
        $res = $stmt->get_result();

        if($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $newid = uniqid();
            $user_ip = $_SERVER['REMOTE_ADDR'];
            $expires =  time()+(60 * 60 * 24 * 400);
            $active = time();
            $user_agent = htmlspecialchars($_SERVER['HTTP_USER_AGENT']);

            $stmt2 = $conn->prepare("UPDATE sessions SET id = ?, timestamp = ?, login_from = ?, user_agent = ? WHERE id = ?");
            $stmt2->bind_param("sisss", $newid, $active, $user_ip, $user_agent, $oldid);
            if ($stmt2->execute()) {
                setcookie('token', $newid, [
                    'expires' => $expires,
                    'path' => '/',
                    'domain' => '.gr8brik.rf.gd',
                    'secure' => false,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);

                return true;
            } else {
                echo("mysql error " . $stmt2->error);
                exit;
            }
        }
    }
    return false;
}

function delete_old_sessions() {
    global $conn;
    if (loggedin()) {
        $expiry = time() - (60 * 60 * 24 * 30);
        $old_sessions_stmt = $conn->prepare("DELETE FROM sessions WHERE timestamp < ?");
        $old_sessions_stmt->bind_param("i", $expiry);
        $old_sessions_stmt->execute();
        $old_sessions_stmt->close();
        return true;
    }
    return false;
}

function delete_inactive_users() {
    global $conn;

    $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
    $conn3 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);

    if ($conn->connect_error) {
        error_log("failed to connect to DB_NAME " . $conn->connect_error);
        return false;
    }
    if ($conn2->connect_error) {
        error_log("failed to connect to DB_NAME2 " . $conn2->connect_error);
        $conn->close();
        return false;
    }
    if ($conn3->connect_error) {
        error_log("failed to connect to DB_NAME3 " . $conn3->connect_error);
        $conn->close();
        $conn2->close();
        return false;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE deactive IS NOT NULL AND deactive < NOW() - INTERVAL 14 DAY");

    if (!$stmt) {
        error_log("failed statement for selecting users " . $conn->error);
        $conn->close();
        $conn2->close();
        $conn3->close();
        return false;
    }

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $inactive_user_ids = [];
        while ($row = $result->fetch_assoc()) {
            $inactive_user_ids[] = $row['id'];
        }
        $stmt->close();

        if (empty($inactive_user_ids)) {
            $conn->close();
            $conn2->close();
            $conn3->close();
            return true;
        }

        foreach ($inactive_user_ids as $user_delete_id) {
            // debug
            error_log("attempting to delete user " . $user_delete_id . "\n");

            $stmt_messages = $conn3->prepare("UPDATE messages SET userid = 0 WHERE userid = ?");
            if ($stmt_messages) {
                $stmt_messages->bind_param("i", $user_delete_id);
                $stmt_messages->execute();
                $stmt_messages->close();
            } else {
                error_log($conn3->error);
            }

            $stmt_models = $conn2->prepare("UPDATE model SET user = 0 WHERE user = ?");
            if ($stmt_models) {
                $stmt_models->bind_param("i", $user_delete_id);
                $stmt_models->execute();
                $stmt_models->close();
            } else {
                error_log($conn2->error);
            }

            $stmt_comments = $conn2->prepare("UPDATE comments SET user = 0 WHERE user = ?");
            if ($stmt_comments) {
                $stmt_comments->bind_param("i", $user_delete_id);
                $stmt_comments->execute();
                $stmt_comments->close();
            } else {
                error_log($conn2->error);
            }

            $stmt_follows = $conn->prepare("DELETE FROM follow WHERE userid = ? OR profileid = ?");
            if ($stmt_follows) {
                $stmt_follows->bind_param("ii", $user_delete_id, $user_delete_id);
                $stmt_follows->execute();
                $stmt_follows->close();
            } else {
                error_log($conn->error);
            }

            $stmt_blocks = $conn->prepare("DELETE FROM user_blocks WHERE userid = ? OR profileid = ?");
            if ($stmt_blocks) {
                $stmt_blocks->bind_param("ii", $user_delete_id, $user_delete_id);
                $stmt_blocks->execute();
                $stmt_blocks->close();
            } else {
                error_log($conn->error);
            }

            $stmt_notif = $conn->prepare("DELETE FROM notifications WHERE user = ? OR profile = ?");
            if ($stmt_notif) {
                $stmt_notif->bind_param("ii", $user_delete_id, $user_delete_id);
                $stmt_notif->execute();
                $stmt_notif->close();
            } else {
                error_log($conn->error);
            }

            $stmt_bans = $conn->prepare("DELETE FROM bans WHERE user = ?");
            if ($stmt_bans) {
                $stmt_bans->bind_param("i", $user_delete_id);
                $stmt_bans->execute();
                $stmt_bans->close();
            } else {
                error_log($conn->error);
            }

            $stmt_appeals = $conn->prepare("DELETE FROM appeals WHERE user = ?");
            if ($stmt_appeals) {
                $stmt_appeals->bind_param("i", $user_delete_id);
                $stmt_appeals->execute();
                $stmt_appeals->close();
            } else {
                error_log($conn->error);
            }

            $extensions = ['jpg', 'png', 'jpeg', 'webp', 'gif'];
            foreach ($extensions as $ext) {
                $file = "../acc/users/pfps/{$user_delete_id}.$ext";
                if (file_exists($file)) {
                    if (@unlink($file)) {
                        error_log("deleted pfp for $user_delete_id $file\n");
                    } else {
                        error_log("failed to delete pfp for $user_delete_id $file");
                    }
                }
            }

            $stmt_sessions = $conn->prepare("DELETE FROM sessions WHERE user = ?");
            if ($stmt_sessions) {
                $stmt_sessions->bind_param("i", $user_delete_id);
                $stmt_sessions->execute();
                $stmt_sessions->close();
            } else {
                error_log($conn->error);
            }

            $stmt_users = $conn->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt_users) {
                $stmt_users->bind_param("i", $user_delete_id);
                if ($stmt_users->execute()) {
                    error_log("successfully deleted user " . $user_delete_id . "\n");
                } else {
                    error_log($stmt_users->error);
                }
                $stmt_users->close();
            } else {
                error_log($conn->error);
            }
        }

        $conn->close();
        $conn2->close();
        $conn3->close();
        return true;
    } else {
        error_log($stmt->error);
        $stmt->close();
        $conn->close();
        $conn2->close();
        $conn3->close();
        return false;
    }
}

/* function delete_inactive_users() {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE deactive IS NOT NULL AND deactive < NOW() - INTERVAL 30 DAY");
    
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        $stmt->close();
        echo $res->fetch_assoc();
        exit;
        return true;
    } else {
        error_log("failed to delete inactive users " . $stmt->error);
        $stmt->close();
        return false;
    }
} */

/* function delete_inactive_users() {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE deactive IS NOT NULL AND deactive < NOW() - INTERVAL 30 DAY");
    
    if ($stmt->execute()) {

        header('Content-Type: application/json');
        header("HTTP/1.0 500 Internal Server Error");

        echo json_encode(['error' => 'To delete your account, please email us at ' . DB_MAIL]);
        exit;

        $id = $token['user'];
        if(!$id) {
            header("HTTP/1.0 500 Internal Server Error");
            echo json_encode(['error' => 'Error fetching userid. It may be plausible you were logged out from our systems or the session API is down.']);
            exit;
        }

        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
        $conn3 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);

        if ($conn->connect_error) {
            header("HTTP/1.0 500 Internal Server Error");
            echo json_encode(['error' => $conn->connect_error]);
            exit;
        }

        $stmt_check = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $stmt_check->store_result();

        if (isset($_COOKIE['token']) && $tokendata->num_rows != 0 && $stmt_check->num_rows != 0) {
            $stmt_check->close();

            $stmt_forum = $conn3->prepare("UPDATE posts, replies SET posts.user = 0, replies.user = 0 WHERE posts.user = ? OR replies.user = ?");
            $stmt_forum->bind_param("ii", $id, $id);
            $stmt_forum->execute();
            $stmt_forum->close();

            $stmt_forum = $conn3->prepare("UPDATE messages SET userid = 0 WHERE userid = ?");
            $stmt_forum->bind_param("i", $id);
            $stmt_forum->execute();
            $stmt_forum->close();

            $stmt_models = $conn2->prepare("DELETE FROM model WHERE user = ?");
            $stmt_models->bind_param("i", $id);
            $stmt_models->execute();
            $stmt_models->close();

            $stmt_comments = $conn2->prepare("DELETE FROM comments WHERE user = ?");
            $stmt_comments->bind_param("i", $id);
            $stmt_comments->execute();
            $stmt_comments->close();

            $stmt_follows = $conn->prepare("DELETE FROM follow WHERE userid = ? OR profileid = ?");
            $stmt_follows->bind_param("ii", $id, $id);
            $stmt_follows->execute();
            $stmt_follows->close();

            $stmt_blocks = $conn->prepare("DELETE FROM user_blocks WHERE userid = ? OR profileid = ?");
            $stmt_blocks->bind_param("ii", $id, $id);
            $stmt_blocks->execute();
            $stmt_blocks->close();

            $stmt_notif = $conn->prepare("DELETE FROM notifications WHERE user = ? OR profile = ?");
            $stmt_notif->bind_param("ii", $id, $id);
            $stmt_notif->execute();
            $stmt_notif->close();

            $stmt_bans = $conn->prepare("DELETE FROM bans WHERE user = ?");
            $stmt_bans->bind_param("i", $id);
            $stmt_bans->execute();
            $stmt_bans->close();

            $stmt_appeals = $conn->prepare("DELETE FROM appeals WHERE user = ?");
            $stmt_appeals->bind_param("i", $id);
            $stmt_appeals->execute();
            $stmt_appeals->close();

            $extensions = ['jpg', 'png', 'jpeg', 'webp', 'gif'];
            foreach ($extensions as $ext) {
                $file = "../acc/users/pfps/$id.$ext";
                if (file_exists($file)) {
                    @unlink($file);
                }
            }

            $stmt_users = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt_users->bind_param("i", $id);
            
            $stmt_sessions = $conn->prepare("DELETE FROM sessions WHERE user = ?");
            $stmt_sessions->bind_param("i", $id);
            $stmt_sessions->execute();
            $stmt_sessions->close();

            $_SESSION = [];
            session_unset();
            session_destroy();
            setcookie(session_name(), '', time() - 3600, '/');
            setcookie('token', '', time() - 3600, '/');

            if ($stmt_users->execute()) {
                header("HTTP/1.0 200 OK");
                setcookie("token", "", time() - 3600, "/");
                echo json_encode(['success' => 'Your account has been permanently deleted from our servers.']);
            } else {
                header("HTTP/1.0 500 Internal Server Error");
                echo json_encode(['error' => 'Something went wrong while deleting your account. ' . $stmt_users->error]);
            }
            $stmt_users->close();
        } else {
            header("HTTP/1.0 404 Not Found");
            setcookie("token", "", time() - 3600, "/");
            $stmt_check->close();
            echo json_encode(['error' => "Hmm, we couldn't find your account."]);
        }
        $conn->close();
        $conn2->close();
    } else {
        echo("failed to delete inactive users " . $stmt->error);
        $stmt->close();
        return false;
    }
} */

function loggedin() {
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

    if (isset($_COOKIE['token'])) {
        $session_stmt = $conn->prepare("SELECT * FROM sessions WHERE id = ?");
        $session_stmt->bind_param("s", $_COOKIE['token']);
        $session_stmt->execute();
        $session_res = $session_stmt->get_result();

        if($session_res->num_rows !== 0) {
            $session = $session_res->fetch_assoc();
            $userid = $session['user'];

            $user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $user_stmt->bind_param("i", $userid);
            $user_stmt->execute();
            $user_res = $user_stmt->get_result();

            if($user_res->num_rows !== 0) {
                $user_data = $user_res->fetch_assoc();

                if(!empty($user_row['deactive'])) {
                    return false;
                }

                $ban_stmt = $conn->prepare("SELECT * FROM bans WHERE user = ? LIMIT 1");
                $ban_stmt->bind_param("i", $userid);
                $ban_stmt->execute();
                $ban_res = $ban_stmt->get_result();

                if ($ban_res->num_rows !== 0) {
                    $ban_data = $ban_res->fetch_assoc();
                    if ($ban_data['end_date'] !== null && $ban_data['end_date'] >= time()) {
                        return false;
                    }
                }
            }

            return true;
        }
    }

    return false;
}

// will remove soon
function isLoggedin() {
    global $tokendata;

    if (isset($_COOKIE['token'])) {
        if ($tokendata && $tokendata->num_rows != 0) {
            if ($_SERVER['PHP_SELF'] === '/acc/login.php' || $_SERVER['PHP_SELF'] === '/acc/register.php' || $_SERVER['PHP_SELF'] === '/index.php') {
                header('Location: /list');
                exit;
            }
        } else {
            setcookie("token", "", time() - 3600, "/");
            if ($_SERVER['PHP_SELF'] != '/acc/login.php' && $_SERVER['PHP_SELF'] != '/acc/register.php' && $_SERVER['PHP_SELF'] != '/index.php') {
                header('Location: /acc/login.php');
                exit;
            }
        }
    } else {
        if ($_SERVER['PHP_SELF'] != '/acc/login.php' && $_SERVER['PHP_SELF'] != '/acc/register.php' && $_SERVER['PHP_SELF'] != '/index.php') {
            header('Location: /acc/login.php');
            exit;
        }
    }
}
?>