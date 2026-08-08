<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/auth.php';

$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

class ScreenNameUtils {
    public function generateRandomScreenName() {
        $words = ['Brick', 'Minifig', 'Stud', 'Build', 'Block', 'Stack', 'Baseplate', 'Roadplate', 'Fanatic', 'Craftsman', 'Awesome', 'Great' , 'Master', 'Creator', 'Modeler'];
        $randomKeys = array_rand($words, 2);
        $randomWord1 = $words[$randomKeys[0]];
        $randomWord2 = $words[$randomKeys[1]];
        $randomNumber = rand(100, 999);

        $randomScreenName = $randomWord1 . $randomWord2 . $randomNumber;
        return $randomScreenName;
    }

    public function check_username_available(string $new) {
        global $current_user;
        global $conn;

        $available = '1';
        $reason = null;
        
        $reserved_names = array(
            'administrator', 
            'admin',
            'susstevedev',
            'evan',
            'the_an0nym',
            'missbricker',
            'gr8brik'
        );
        
        /* I will not add some words as the project is open source and github/contibutors might get mad */
        /* If someone has somehow registered an account with one of these names, report their account or one of their creations */
        $banned_words = array(
            'ZnVjaw',
            'c2hpdA',
            'ZGFtbg',
            'ZnVja2luZw',
            'ZGFtbWl0',
            'bW90aGVyZnVja2Vy',
            'ZmFnZw',
            'bmlnZw'
        );

        if(empty($new) || $new === null) {
            return ['available' => false, 'reason' => 'Please provide a username.'];
        }

        if(loggedin()) {
            if($current_user->verify_token != NULL) {
                return ['available' => false, 'reason' => "Please verify your account to continue this action."];
            }

            if (!empty($current_user->changed)) {
                $remaining = $current_user->changed - time();
                if ($remaining < 86400) {
                    return ['available' => false, 'reason' => "You can change your username " . time_ago($remaining)];
                }
            }

            if(trim((strtolower($new))) == trim((strtolower($current_user->username)))) {
                return ['available' => '0', 'reason' => null];
            }
        }

        if(!ctype_alnum(str_replace(array('-', '_', '.'), '', $new))) {
            return ['available' => false, 'reason' => 'Username cannot contain anything other than A-Z, any number, or dash underscore and dot.'];
        }

        if(strlen($new) > 15) {
            return ['available' => false, 'reason' => 'Username must be at most 15 characters.'];
        }

        if(strlen($new) < 3) {
            return ['available' => false, 'reason' => 'Username must be at least 3 characters.'];
        }

        $isBanned = AccountManager::isBanned($conn, null, $new);
        if($isBanned !== false) {
            return ['available' => 0];
        }

        $result = $conn->query("SELECT * FROM users WHERE username = '$new' AND deactive IS NULL");
        if($result->num_rows != 0 || in_array($new, $reserved_names)) {
            return ['available' => false, 'reason' => 'This username has been taken. Please choose another.'];
        }
        
        foreach($banned_words as $banned_word) {
            if (!$reason && strpos(base64_encode($new), $banned_word) !== false) {
                return ['available' => false, 'reason' => 'This username is unavailable. Please choose another.'];
            }
        }

        return ['available' => true, 'reason' => 'Username is available'];
    }
}

if(isset($_GET['check_username_available'])) {
    $username = urldecode(htmlspecialchars($_GET['username']));
    $utils = new ScreenNameUtils();
    $result = $utils->check_username_available($username);
    header("HTTP/1.0 200 OK");
    echo json_encode($result);
    exit;
}

// new thingy
class AccountSettings {    
    public function username_change($new) {
        if(!loggedin()) {
            header("HTTP/1.0 403 Forbidden");
            return ['error' => 'Not authenticated, please sign in using traditional means', 'code' => '403', 'version' => 'NEW'];
        }

        global $conn;
        global $current_user;
        $id = $current_user->id;

        $utils = new ScreenNameUtils();
        $result = $utils->check_username_available($new);
        if ($result['available'] === '0') {
            header("HTTP/1.0 500 Internal Server Error");
            return ['error' => $result['reason']];
        }

        $changed = time();

        $stmt = $conn->prepare("UPDATE users SET username = ?, changed = ? WHERE id = ?");
        $stmt->bind_param("sss", $new, $changed, $id);
        if ($stmt->execute()) {
            return ['success' => 'Username updated'];
        }
    }

    public function twitter_change($new) {
        if(!loggedin()) {
            header("HTTP/1.0 403 Forbidden");
            return ['error' => 'Not authenticated, please sign in using traditional means', 'code' => '403', 'version' => 'NEW'];
        }

        global $current_user;
        $id = $current_user->id;

        if($current_user->verify_token != NULL) {
            $error_message = "Please verify your account to continue this action.";
            header("HTTP/1.0 500 Internal Server Error");
            return ['error' => $error_message];
        }

        if(strlen($new) > 15) {
            $error_message = "The handle of the linked Twitter account can only be 15 characters.";
            header("HTTP/1.0 500 Internal Server Error");
            return ['error' => $error_message];
        }

        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

        $stmt_2 = $conn->prepare("UPDATE users SET twitter = ? WHERE id = ?");
        $stmt_2->bind_param("ss", $new, $id);
        if ($stmt_2->execute()) {
            return ['success' => 'Your profile has been updated with the new Twitter account.', 'code' => '200', 'version' => 'NEW'];
        }
    }

    public function bsky_change($new) {
        if(!loggedin()) {
            header("HTTP/1.0 403 Forbidden");
            return ['error' => 'Not authenticated, please sign in using traditional means', 'code' => '403', 'version' => 'NEW'];
        }

        global $current_user;
        $id = $current_user->id;

        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

        if($current_user->verify_token != NULL) {
            header("HTTP/1.0 500 Internal Server Error");
            return ['error' => "Please verify your account to continue this action."];
        }

        $stmt_2 = $conn->prepare("UPDATE users SET bsky = ? WHERE id = ?");
        $stmt_2->bind_param("ss", $new, $id);
        if ($stmt_2->execute()) {
            return ['success' => 'Your profile has been updated with the new Bluesky account.', 'code' => '200', 'version' => 'NEW'];
        }
    }

    public function about_change($new) {
        global $current_user;

        if(!loggedin()) {
            header("HTTP/1.0 403 Forbidden");
            return ['error' => 'Not authenticated, please sign in using traditional means', 'code' => '403', 'version' => 'NEW'];
        }

        $id = $current_user->id;

        if($current_user->verify_token != NULL) {
            header("HTTP/1.0 500 Internal Server Error");
            return ['error' => "Please verify your account to continue this action."];
        }

        // used to be 200
        // 1k as of 1/1/2026
        if(strlen($new) > 1000) {
            header("HTTP/1.0 500 Internal Server Error");
            return ['error' => 'About section can only be 1,000 characters.'];
        }

        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        $stmt_2 = $conn->prepare("UPDATE users SET description = ? WHERE id = ?");

        $stmt_2->bind_param("si", $new, $id);
        if ($stmt_2->execute()) {
            return ['success' => 'About section was changed successfully!'];
        } else {
            header("HTTP/1.0 500 Internal Server Error");
            return ['error' => 'Error changing about section.'];
        }

        $stmt->close();
    }

    public function password_change($oldPassword, $newPassword, $confirmPassword) {
        global $current_user;

        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

        if(!loggedin()) {
            header("HTTP/1.0 403 Forbidden");
            return ['error' => 'Not authenticated, please sign in using traditional means.', 'code' => '403', 'version' => 'NEW'];
        }

        $userid = $current_user->id;

        if ($newPassword !== $confirmPassword) {
            header("HTTP/1.0 500 Internal Server Error");
            return ['error' => "New passwords do not match"];
        }

        $stmt = $conn->prepare("SELECT password, salt FROM users WHERE id = ?");
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $stmt->bind_result($storedHash, $salt);
        $stmt->fetch();
        $stmt->close();

        if ($storedHash) {
            if ($storedHash === md5($oldPassword . $salt)) {
                $newSalt = uniqid();
                $newHashedPassword = md5($newPassword . $newSalt);

                $stmt = $conn->prepare("UPDATE users SET password = ?, salt = ? WHERE id = ?");
                $stmt->bind_param("ssi", $newHashedPassword, $newSalt, $userid);

                if ($stmt->execute()) {
                    header("HTTP/1.0 200 OK");
                    return ['success' => 'Congrats! Your password was updated with success!'];
                } else {
                    header("HTTP/1.0 500 Internal Server Error");
                    return ['error' => "Unknown error changing password."];
                }
            } else {
                header("HTTP/1.0 500 Internal Server Error");
                return ['error' => "Incorrect old password."];
            }
        } else {
            header("HTTP/1.0 500 Internal Server Error");
            return ['error' => "Invalid password hash stored in database."];
        }
    }

    public function mail_change($new, $old) {
        global $current_user;
        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

        if(!loggedin()) {
            header("HTTP/1.0 403 Forbidden");
            return ['error' => 'Not authenticated, please sign in using traditional means', 'code' => '403', 'version' => 'NEW'];
        }

        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        $id = $current_user->id;

        $sql = "SELECT email FROM users WHERE id = '$id'";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();

        if($result->num_rows != 0) {
            if($row['email'] === $old) {
                $sql2 = "UPDATE users SET email = '$new' WHERE id = '$id'";

                if ($conn->query($sql2) === TRUE) {
                    header("HTTP/1.0 200 OK");
                    echo json_encode(['success' => 'Email address updated. Please navigate backwards to continue.']);
                    exit;
                } else {
                    $error_message = "Error changing email address.";
                    header("HTTP/1.0 500 Internal Server Error");
                    echo json_encode(['error' => $error_message]);
                    exit;
                }
            } else {
                $error_message = htmlspecialchars($row['email']) . " is not equal to " . htmlspecialchars($old) . ".";
                header("HTTP/1.0 500 Internal Server Error");
                echo json_encode(['error' => $error_message]);
                exit;
            }
        }
        exit;
    }
}

$account_settings = new AccountSettings();

if(isset($_GET['username_change'])) {
    $username = urldecode(htmlspecialchars($_GET['username']));
    $result = $account_settings->username_change($username);
    echo json_encode($result);
    exit;
}

if(isset($_GET['twitter_change'])) {
    $handle = urldecode(htmlspecialchars($_GET['handle']));
    $result = $account_settings->twitter_change($handle);
    echo json_encode($result);
    exit;
}

if(isset($_GET['bsky_change'])) {
    $handle = urldecode(htmlspecialchars($_GET['handle']));
    $result = $account_settings->bsky_change($handle);
    echo json_encode($result);
    exit;
}

if(isset($_GET['about_change'])){
    $new = urldecode(htmlspecialchars($_GET['description'], ENT_NOQUOTES));
    $result = $account_settings->about_change($new);
    echo json_encode($result);
    exit;
}

if(isset($_POST['change'])){
    $new = urldecode($_POST['n_password']);
    $old = urldecode($_POST['o_password']);
    $confirm = urldecode($_POST['c_password']);

    $result = $account_settings->password_change($old, $new, $confirm);
    echo json_encode($result);
    exit;
}

$default_pfp = '/img/no_image.png';
if (isset($_POST['picture'])) {
    $okay = true;

    if ($current_user->verify_token !== NULL) {
        $error = "Please verify your account to upload or edit your profile picture.";
        $okay = false;
    }

    if (empty($_FILES['fileToUpload']['tmp_name'])) {
        $error = 'No file selected.';
        $okay = false;
    }

    if ($okay) {
        $mime = mime_content_type($_FILES["fileToUpload"]["tmp_name"]);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
            $error = "Only JPEG, PNG, GIF, and WEBP files are allowed.";
            $okay = false;
        }

        if ($_FILES["fileToUpload"]["size"] > 5242880 / 2) {
            $error = 'File too large.';
            $okay = false;
        }
    }

    if ($okay) {
        $data = file_get_contents($_FILES["fileToUpload"]["tmp_name"]);
        $image = imagecreatefromstring($data);
        if (!$image) {
            $error = "Invalid image file.";
            $okay = false;
        }
    }

    if ($okay) {
        $db_pfp = '/acc/users/pfps/' . $current_user->id . '.webp';
        $upload = "users/pfps/" . $current_user->id . ".webp";

        if (imagewebp($image, $upload, 50)) {
            $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
            if ($conn->connect_error) {
                http_response_code(500);
                exit(json_encode(['success' => false, 'error' => 'DB connection failure.']));
            }

            $stmt = $conn->prepare("UPDATE users SET picture = ? WHERE id = ?");
            $stmt->bind_param("ss", $db_pfp, $id);
            if ($stmt->execute()) {
                http_response_code(200);
                exit(json_encode(['success' => true, 'message' => 'Profile picture updated.', 'image' => $upload]));
            } else {
                http_response_code(500);
                exit(json_encode(['success' => false, 'error' => 'Could not update profile picture row in the database.']));
            }
        } else {
            http_response_code(500);
            exit(json_encode(['success' => false, 'error' => 'Failed to save image.']));
        }
    } else {
        http_response_code(400);
        exit(json_encode(['success' => false, 'error' => $error]));
    }
}

if (isset($_POST['remove_picture'])) {
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        exit(json_encode(['success' => false, 'error' => 'DB connection failure.']));
    }

    $old_pfp = $_SERVER['DOCUMENT_ROOT'] . $current_user->picture;
    $new_pfp = '/img/no_image.png';

    if (strpos($current_user->picture, '/acc/users/pfps/') !== false && file_exists($old_pfp)) {
        unlink($old_pfp);
    } else {
        http_response_code(400);
        exit(json_encode(['success' => false, 'error' => 'You do not have an uploaded profile image.']));
    }

    $stmt = $conn->prepare("UPDATE users SET picture = ? WHERE id = ?");
    $stmt->bind_param("ss", $new_pfp, $id);

    if ($stmt->execute()) {
        http_response_code(200);
        exit(json_encode(['success' => true, 'message' => 'Profile picture removed.', 'image' => $default_pfp]));
    } else {
        http_response_code(500);
        exit(json_encode(['success' => false, 'error' => 'Error removing profile picture. Please try again later.']));
    }
}

if (isset($_GET['get_notifications'])) {
    header('Content-Type: application/json');

    $conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if ($conn2->connect_error) {
        exit($conn2->connect_error);
    }

    $stmt_2 = $conn2->prepare("SELECT * FROM notifications WHERE user = ? ORDER BY timestamp DESC");
    $stmt_2->bind_param("i", $_SESSION['userid']);
    $stmt_2->execute();
    $result = $stmt_2->get_result();

    $notifications = [];

    while($row = $result->fetch_assoc()) {

        $profile = $row['profile'];

        $stmtUser = $conn2->prepare("SELECT username FROM users WHERE id = ?");
        $stmtUser->bind_param("i", $profile);
        $stmtUser->execute();
        $userRow = $stmtUser->get_result()->fetch_assoc();

        $user = $userRow['username'];
        $post = null;

        if($row['category'] == 1) {
            $url = "/user/" . $profile;
            $post = "followed you";
            $img  = "../acc/users/pfps/" . $profile;

        } elseif ($row['category'] == 2){

            $conn3 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
            $stmt3 = $conn3->prepare("SELECT name, screenshot FROM model WHERE id = ?");
            $stmt3->bind_param("i", $row['content']);
            $stmt3->execute();
            $row3 = $stmt3->get_result()->fetch_assoc();
            $conn3->close();

            if($row3) {
                $img = $row3['screenshot'];
                $url = "/build/" . $row['content'];
                $post = "commented on " . $row3['name'];
            }

        } elseif($row['category'] == 3) {

            $conn3 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME3);
            $stmt3 = $conn3->prepare("SELECT title FROM messages WHERE id = ?");
            $stmt3->bind_param("i", $row['content']);
            $stmt3->execute();
            $row3 = $stmt3->get_result()->fetch_assoc();
            $conn3->close();

            $title = $row3['title'] ?? "[deleted]";
            $img = "/img/com.jpg";
            $url = "/com/" . $row['content'];
            $post = "replied to " . $title;
        }

        if (is_numeric($row['timestamp'])) {
            $time = time_ago(date("Y-m-d H:i:s", $row['timestamp']));
        } else {
            $time = "A long time ago";
        }
        
        if($_SERVER['HTTPS'] != 'off') {
            $proto = "https";
        } else {
            $proto = "http";
        }

        $notifications[] = [
        	"url"  => $proto . "://" . $_SERVER['HTTP_HOST'] . $url,
        	"img"  => $proto . "://" . $_SERVER['HTTP_HOST'] . $img,
            "message" => $user . " " . $post,
        	"time" => $time
       	];
    }

    echo json_encode($notifications);
    exit;
}

if(isset($_GET['clear_notifications'])) {
    header('Content-Type: application/json');
    if(!loggedin()) {
        http_response_code(200);
        exit;
    }

    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    $id = (int)$current_user->id;

    if((int)$current_user->alert != 0) {
        $stmt_2 = $conn->prepare("UPDATE users SET alert = 0 WHERE id = ? LIMIT 1");
        $stmt_2->bind_param("i", $id);

        if ($stmt_2->execute()) {
            http_response_code(200);
            echo json_encode(['success' => 'Cleared inbox notifications. Would you like to reload the web page?']);
            exit;
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error clearing inbox notifications.']);
            exit;
        }
    } else {
        http_response_code(200);
        exit;
    }
}

if (isset($_POST['logout'])) {
    logout();
}

if (isset($_POST['deactive_account'])) {
    if (!loggedin()) {
        exit('User not authenticated!');
    }

    $profile_id = (int)$current_user->id;
    $today = date("Y-m-d H:i:s");

    $stmt = $conn->prepare("UPDATE users SET blog_user_id = NULL, deactive = ? WHERE id = ?");
    $stmt->bind_param("si", $today, $profile_id);

    if ($stmt->execute()) {
        logout();
        header('Location: /index.php');
        exit;
    } else {
        exit($stmt->error);
    }
}
?>