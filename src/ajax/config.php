<?php
ob_start();

// constants
require_once 'constants.php';

/* session_name('loginid');
ini_set('session_save_path', $_SERVER['DOCUMENT_ROOT'] . '/acc/users/sessions/');
session_start(); */

function sess_open($save_path, $session_name) {
    global $sess_db;
    $sess_db = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if ($sess_db->connect_error) {
        echo "Session DB connection failed: " . $sess_db->connect_error;
        return false;
    }
    return true;
}

function sess_close() {
    global $sess_db;
    return $sess_db->close();
}

function sess_read($id) {
    global $sess_db;
    $stmt = $sess_db->prepare("SELECT data FROM php_sessions WHERE id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($data);
    $stmt->fetch();
    return $data ?? '';
}

function sess_write($id, $data) {
    global $sess_db;
    $time = time();
    $ip = $_SERVER['REMOTE_ADDR'];

    $stmt = $sess_db->prepare("REPLACE INTO php_sessions (id, data, timestamp, ip) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $id, $data, $time, $ip);
    return $stmt->execute();
}

function sess_destroy($id) {
    global $sess_db;
    $stmt = $sess_db->prepare("DELETE FROM php_sessions WHERE id = ?");
    $stmt->bind_param("s", $id);
    return $stmt->execute();
}

function sess_gc($maxlifetime) {
    global $sess_db;
    $old = time() - $maxlifetime;
    $stmt = $sess_db->prepare("DELETE FROM php_sessions WHERE timestamp < ?");
    $stmt->bind_param("i", $old);
    return $stmt->execute();
}

session_set_save_handler(
    'sess_open',
    'sess_close',
    'sess_read',
    'sess_write',
    'sess_destroy',
    'sess_gc'
);

register_shutdown_function('session_write_close');
ini_set('session.gc_maxlifetime', 1000);
ini_set('session.gc_probability', 5);
session_start();

if (!isset($_SESSION['requests'])) {
    $_SESSION['requests'] = [];
}

$_SESSION['requests'] = array_filter($_SESSION['requests'], function ($timestamp) {
    return $timestamp > time() - 60;
});

if (count($_SESSION['requests']) >= 60) {
    $oldest = min($_SESSION['requests']);
    $remaining = 60 - (time() - $oldest);
    $requests = $_SESSION['requests'];

    http_response_code(429);
    header("Retry-After: " . $remaining);
    $error = 'You are sending too many requests. Please wait ' .  $remaining . ' seconds.';
    if(isset($_GET['ajax'])) {
        echo json_encode(['error' => $error, 'requests' => $requests]);
    } else {
        echo 'Error: ' . $error;
    }
    exit;
}

$_SESSION['requests'][] = time();

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(128));
}

define('csrf', $_SESSION['csrf']);

// ip ban system
// checks for blacklisted ip address
function check_blacklisted_ip() {
    $soft_ver = 'alpha 0.1.2 (06/20/26)';
    $app_name = 'Gr8Brik';
    $repo_name = 'susstevedev/ip-ban-system-php';
    $repo_url = 'https://github.com/susstevedev/ip-ban-system-php';
    $piko_url = 'https://cdn.jsdelivr.net/npm/@picocss/pico@latest/css/pico.min.css';
    
    $db = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if ($db->connect_error) {
        exit($db->connect_error);
    }
    
    $ip = $_SERVER['REMOTE_ADDR'];
    
    if (!isset($_SESSION['country_code']) || !isset($_SESSION['region'])) {
        $geo_url = 'https://get.geojs.io/v1/ip/geo/' . $ip . '.json';
        $geo_response = @file_get_contents($geo_url);

        if ($geo_response !== false) {
            $geo_data = json_decode($geo_response);
            $_SESSION['country_code'] = isset($geo_data->country_code) ? $geo_data->country_code : 'UNKNOWN';
            $_SESSION['region'] = isset($geo_data->region) ? $geo_data->region : 'UNKNOWN';
        } else {
            $_SESSION['country_code'] = 'UNKNOWN';
        }
    }

    $geo_banned = false;
    $restricted_countries = ['GB', 'AU'];
	$restricted_states = [
    	'Connecticut', 'Florida', 'Idaho', 'Louisiana', 
    	'Mississippi', 'Nebraska', 'Tennessee', 'Utah'
	];

	if (in_array($_SESSION['country_code'], $restricted_countries, true) || in_array($_SESSION['region'], $restricted_states, true)) {
        $geo_banned = true;
        $ban_until = '[unknown]';
        $ban_at = '[unknown]';
        $reason = 'Users from a province with "age verification" laws are not allowed to use our services.';
    } else {
        $stmt = $db->prepare("SELECT id, ban_at, ban_until, reason FROM ip_bans WHERE ip = ?");
        $stmt->bind_param("s", $ip);
        $stmt->execute();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $ban_at, $ban_until, $reason);
            $stmt->fetch();
            $geo_banned = true;
        }
        $stmt->close();
    }
    
    if(isset($id) && $ban_until > date("Y-m-d H:i:s") || $geo_banned === true) {
        http_response_code(403);
        echo "<html><head><title>IP address banned - " . $app_name . "</title><link rel='stylesheet' href='" . $piko_url . "'></head>";
        echo "<body><center><div id='root'><br /><h1>Your IP address has been banned!</h1>";
        echo "<b>" . $reason . "</b>";
        echo "<p>Banned at <b>" . $ban_at . "</b>, until <b>" . $ban_until . "</b>.</p>";
        echo "<p>To get unbanned, you will have to contact <b><a href='mailto:" . DB_MAIL . "'>" . DB_MAIL . "</a></b> and provide the reason you got banned along with why you should be unbanned.</p>";
        echo "<p>Additionally, you can ask for your account to be deleted.</p>";
        echo "<p><small><a href='" . $repo_url . "'>" . $repo_name . "</a> " . $soft_ver . ".</small></p>";
        echo "</div></center></body></html>";
        exit;
    }
}
check_blacklisted_ip();

if (isset($_GET['get_csrf_token'])) {
    echo json_encode(['csrf_token' => $_SESSION['csrf']]);
    exit;
}
?>