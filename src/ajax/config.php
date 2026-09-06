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
require_once 'ipban.php';
require_once 'what_browser.php';

class SessHandler implements SessionHandlerInterface {
    private ?mysqli $db = null;
    private string $dbServer;
    private string $dbUser;
    private string $dbPassword;
    private string $dbName;
    private mixed $userId = null;

    public function __construct(string $server, string $user, string $password, string $name) {
        $this->dbServer = $server;
        $this->dbUser = $user;
        $this->dbPassword = $password;
        $this->dbName = $name;
    }

    public function user(mixed $user): void {
        $this->userId = $user ?? null;
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
        $id = hash('sha256', $id);
        $stmt = $this->db->query("SELECT data FROM php_sessions WHERE id = '$id' AND active = 1");
        $row = $stmt->fetch_assoc();

        if($row) {
            if($row['data']) {
                return $row['data'];
            }
        }

        return '';
    }

    public function write($id, $data):bool {
        $id = hash('sha256', $id);
        $ip = $_SERVER['REMOTE_ADDR'];
        $useragent = UA; //UA is defined in what_browser.php
        $userid = $this->userId;
        $timestamp = time();
        $stmt = $this->db->query("
            INSERT INTO php_sessions (id, data, timestamp, ip, ua, userid, active) 
            VALUES ('$id', '$data', '$timestamp', '$ip', '$useragent', '$userid', 1)
            ON DUPLICATE KEY UPDATE
                data = IF(active = 1, VALUES(data), data),
                timestamp = IF(active = 1, VALUES(timestamp), timestamp),
                ip = IF(active = 1, VALUES(ip), ip),
                ua = IF(active = 1, VALUES(ua), ua),
                userid = IF(active = 1, VALUES(userid), userid)
        ");
        return $stmt;
    }

    public function destroy($id):bool {
        $id = hash('sha256', $id);
        $stmt = $this->db->query("DELETE FROM php_sessions WHERE id = '$id' AND active = 1");
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

$_SESSION['requests'] = array_filter($_SESSION['requests'] ?? [], function ($timestamp) {
    return $timestamp > time() - 80;
});

$_SESSION['requests'][] = time();
$requests = count($_SESSION['requests']);

if ($requests >= 50) {
    if ($requests >= 80) {
        $db = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

        if (!$db->connect_error) {
            $ipbano = new IpBans($db);
            $ban = $ipbano->getBan();
            $ipbano->displayBan($ban);
        }

        if (!$db->connect_error) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $date = date("Y-m-d H:i:s");
            $until = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $reason = "IP banned for one hour due to spam.";

            $block = $db->prepare("INSERT INTO ip_bans (ip, ban_at, ban_until, reason) VALUES (?, ?, ?, ?)");
            $block->bind_param("ssss", $ip, $date, $until, $reason);
            $block->execute();
            $block->close();
            $db->close();
        }
    }

    $oldest = min($_SESSION['requests']);
    $remaining = max(1, 80 - (time() - $oldest)); 
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $json = (stripos($contentType, 'application/json') !== false) || (stripos($accept, 'application/json') !== false);

    http_response_code(429);
    header("Retry-After: " . $remaining);
    $message = 'You are sending too many requests. Please wait ' . $remaining . ' seconds. All: ' . $requests;

    if (!$json) {
        echo $message;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => $message, 'message' => $message, 'success' => false, 'code' => '429']);
    }
    exit;
}

if (!isset($_SESSION['csrf']) || !isset($_SESSION['csrf_last_updated']) || $_SESSION['csrf_last_updated'] - time() >= 5) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_last_updated'] = time();
}

define('csrf', $_SESSION['csrf']);

$db = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
if ($db->connect_error) {
    exit($db->connect_error);
}
$ipbano = new IpBans($db);
$ban = $ipbano->getBan();
$ipbano->displayBan($ban);

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