<?php
ob_start();

// TEMPORARY
header("Access-Control-Allow-Origin: http://localhost");
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit(0);
}

//dev only
error_reporting(E_ALL);
ini_set('display_errors', '1');

// constants
require_once 'constants.php';
require_once 'what_browser.php';

class SessHandler implements SessionHandlerInterface {
    private ?mysqli $db = null;
    private string $dbServer;
    private string $dbUser;
    private string $dbPassword;
    private string $dbName;
    private ?int $userId = null;

    public function __construct(string $server, string $user, string $password, string $name) {
        $this->dbServer = $server;
        $this->dbUser = $user;
        $this->dbPassword = $password;
        $this->dbName = $name;
    }

    public function user(?int $user): void {
        $this->userId = $user;
    }

    public function open($save_path, $session_name):bool {
        $this->db = new mysqli($this->dbServer, $this->dbUser, $this->dbPassword, $this->dbName);
        if ($this->db->connect_error) {
            return false;
        }
        return true;
    }

    public function close():bool {
        if ($this->db) {
            $this->db->close();
            return true;
        }
        return false;
    }

    public function read($id):string {
        $stmt = $this->db->query("SELECT data FROM php_sessions WHERE id = '$id'");
        $row = $stmt->fetch_assoc();

        if($row) {
            if($row['data']) {
                return $row['data'];
            }
        }

        return '';
    }

    public function write($id, $data):bool {
        $time = time();
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $useragent = UA ?? null; //UA is defined in what_browser.php
        $userid = $this->userId;
        $stmt = $this->db->query("REPLACE INTO php_sessions (id, data, timestamp, ip, ua, userid) VALUES ('$id', '$data', '$time', '$ip', '$useragent', '$userid')");
        return $stmt;
    }

    public function destroy($id):bool {
        $stmt = $this->db->query("DELETE FROM php_sessions WHERE id = '$id'");
        return $stmt;
    }

    public function gc($maxlifetime):int {
        $old = time() - $maxlifetime;
        $stmt = $this->db->query("DELETE FROM php_sessions WHERE timestamp < '$old'");
        $rows = $stmt ? $this->db->affected_rows : false;
        return $rows;
    }
}

$handler = new SessHandler(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
session_set_save_handler($handler, true);

ini_set('session.gc_maxlifetime', 172800);
ini_set('session.cookie_lifetime', 172800);
ini_set('session.gc_probability', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['userid'])) {
    $handler->user($_SESSION['userid']);
}

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
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

    $isJson = (stripos($contentType, 'application/json') !== false) || (stripos($accept, 'application/json') !== false);

    http_response_code(429);
    header("Retry-After: " . $remaining);
    if (!$isJson) {
        echo 'You are sending too many requests. Please wait ' .  $remaining . ' seconds.';
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'You are sending too many requests. Please wait ' .  $remaining . ' seconds.']);
    }
    exit;
}

$_SESSION['requests'][] = time();

if (!isset($_SESSION['csrf']) || !isset($_SESSION['csrf_last_updated']) || $_SESSION['csrf_last_updated'] - time() >= 5) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_last_updated'] = time();
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
    
    if(isset($id) && isset($ban_until) && $ban_until > date("Y-m-d H:i:s") || $geo_banned === true) {
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

class Cookie {
    public static function controls() {
        if (isset($_COOKIE['cookieControlPrefs'])) {
            $saved_prefs = json_decode(stripslashes($_COOKIE['cookieControlPrefs']), true);
            if (is_array($saved_prefs)) {
                return $saved_prefs;
            } else {
                setcookie("cookieControlPrefs", "", time() - 3600, "/");
                unset($_COOKIE["cookieControlPrefs"]);
                return [];
            }
        } else {
            return [];
        }
    }

    public static function allow_analytics() {
        $cookie = Cookie::controls();

        if(in_array('analytics', $cookie)) {
            if (!isset($_SESSION['last_analytics'])) {
                $_SESSION['last_analytics'] = time();
                return true;
            }

            /*if($_SESSION['last_analytics'] && time() - $_SESSION['last_analytics'] < 3600) { //every hour
                return false;
            }*/

            return true;
        } else {
            return false;
        }
    }

    public static function analytics_user(mixed $db, int $id, int $me, ?string $content = 'No string hast been provided! Sorcery!') {
        if(!loggedin()) {
            return false;
        }

        if(!Cookie::allow_analytics()) {
            return false;
        }

        $stmt = $db->prepare("INSERT INTO analytics (my_user, their_user, content_string, type) VALUES (?, ?, ?, 'user')");
        $stmt->bind_param("iis", $me, $id, $content);
        $stmt->execute();
        return $db->insert_id ?? true;
    }

    public static function analytics_creation(mixed $db, int $id, int $me, ?string $content = 'No string hast been provided! Sorcery!') {
        if(!loggedin()) {
            return false;
        }

        if(!Cookie::allow_analytics()) {
            return false;
        }

        $stmt = $db->prepare("INSERT INTO analytics (my_user, their_user, content_string, type) VALUES (?, ?, ?, 'creation')");
        $stmt->bind_param("iis", $me, $id, $content);
        $stmt->execute();
        return $db->insert_id ?? true;
    }

    public static function analytics_forum(mixed $db, int $id, int $me, ?string $content = 'No string hast been provided! Sorcery!') {
        if(!loggedin()) {
            return false;
        }

        if(!Cookie::allow_analytics()) {
            return false;
        }

        $stmt = $db->prepare("INSERT INTO analytics (my_user, their_user, content_string, type) VALUES (?, ?, ?, 'forum')");
        $stmt->bind_param("iis", $me, $id, $content);
        $stmt->execute();
        return $db->insert_id ?? true;
    }

    public static function del_old_analytics(mixed $db, ?int $days = 30, ?int $lasttime = 43200) {
        if ($days <= 0) {
            return false;
        }

        if (isset($_SESSION['last_analytics_cleanup']) && (time() - $_SESSION['last_analytics_cleanup'] < $lasttime)) { //default is 12 hours
            return false;
        }

        $_SESSION['last_analytics_cleanup'] = time();

        $stmt = $db->prepare("DELETE FROM analytics WHERE time < NOW() - INTERVAL ? DAY");
        $stmt->bind_param("i", $days);

        return $stmt->execute();
    }
}

if(!in_array('site-prefs', Cookie::controls())) {
    setcookie("mode", "", time() - 3600, "/");
    unset($_COOKIE["mode"]);
}

if (isset($_GET['get_csrf_token'])) {
    echo json_encode(['csrf_token' => $_SESSION['csrf']]);
    exit;
}
?>