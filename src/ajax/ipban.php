<?php
// ip ban system
// checks for blacklisted ip address

class IpBans {
    public mysqli $db;
    public string $soft_ver;
    public string $app_name;
    public ?string $repo_name = null;
    public ?string $repo_url; 
    public string $piko_url;
    public string $ip;

    /** 
     * Sets variables for function
     * 
     * @param mysqli $db - Database
     * @param string $soft_ver - Set software version
     * @param string $app_name - Set software app name
     * @param string $repo_name - Git repo name (formatted as "username/reponame"). Can be null
     * @param string $repo_url - Webpage for the git repo (eg github). Can also be null
     * @param string $piko_url - PicoCSS url
    */
    public function __construct(mysqli $db) {
        $this->db = $db;
        $this->soft_ver = 'alpha 0.1.3 (09/05/26)';
        $this->app_name = 'Gr8Brik';
        $this->repo_name = 'susstevedev/ip-ban-system-php';
        $this->repo_url = 'https://github.com/susstevedev/ip-ban-system-php';
        $this->piko_url = 'https://cdn.jsdelivr.net/npm/@picocss/pico@latest/css/pico.min.css';
        $this->ip = $_SERVER['REMOTE_ADDR'];
    }

    public function getBan() {
        $ip = $this->ip;

        if (!isset($_SESSION['country_code']) || !isset($_SESSION['region'])) {
            $geo_url = 'https://get.geojs.io/v1/ip/geo/' . $ip . '.json';
            $geo_response = @file_get_contents($geo_url);

            if ($geo_response !== false) {
                $geo_data = json_decode($geo_response);
                $_SESSION['country_code'] = isset($geo_data->country_code) ? $geo_data->country_code : 'UNKNOWN';
                $_SESSION['region'] = isset($geo_data->region) ? $geo_data->region : 'UNKNOWN';
            } else {
                $_SESSION['country_code'] = 'UNKNOWN';
                $_SESSION['region'] = 'UNKNOWN';
            }
        }

        $restricted_countries = ['GB', 'AU', 'USA'];
        $restricted_states = ['Connecticut', 'Florida', 'Idaho', 'Louisiana', 'Mississippi', 'Nebraska', 'Tennessee', 'Utah'];

        if (in_array($_SESSION['country_code'], $restricted_countries, true) || in_array($_SESSION['region'], $restricted_states, true)) {
            return [
                'id' => 0,
                'ban_at' => date("Y-m-d H:i:s"),
                'ban_until' => 'Permanent',
                'reason' => 'Users from a province with "age verification" laws are not allowed to use our services.'
            ];
        }

        $stmt = $this->db->prepare("SELECT id, ban_at, ban_until, reason FROM ip_bans WHERE ip = ? AND ban_until > NOW() LIMIT 1");
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $ban_at, $ban_until, $reason);
            $stmt->fetch();

            return [
                'id' => $id ?? 0,
                'ban_until' => $ban_until ?? '?',
                'ban_at' => $ban_at ?? '?',
                'reason' => $reason ?? '?',
            ];
        } else {
            return false;
        }
    }

    public function displayBan($ban) {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $json = (stripos($contentType, 'application/json') !== false) || (stripos($accept, 'application/json') !== false);
        $repotxt = "<p><small><a href='" . $this->repo_url ?? null . "'>" . $this->repo_name ?? null . "</a> " . $this->soft_ver . ".</small></p>";

        if($ban !== false) {
            if(!$json) {
                http_response_code(403);
                echo "<!DOCTYPE html><html><head><title>IP address banned - " . $this->app_name . "</title><link rel='stylesheet' href='" . $this->piko_url . "'></head>";
                echo "<body><center><div id='root'><br /><h1>Your IP address has been banned!</h1>";
                echo "<b>" . $ban['reason'] . "</b>";
                echo "<p>Banned at <b>" . date("F j, Y, g:i a", strtotime($ban['ban_at'])) . "</b>, until <b>" . date("F j, Y, g:i a", strtotime($ban['ban_until'])) . "</b>.</p>";
                echo "<p>To get unbanned, you will have to contact <b><a href='mailto:" . DB_MAIL . "'>" . DB_MAIL . "</a></b> and provide the reason you got banned along with why you should be unbanned.</p>";
                echo "<p>Additionally, you can ask for your account to be deleted.</p>";
                echo isset($this->repo_url) && isset($this->repo_name) ? $repotxt : '';
                echo "</div></center></body></html>";
            } else {
                header('Content-Type: application/json');
                echo json_encode(['error' => $ban['reason'], 'message' => $ban['reason'], 'success' => false, 'code' => '429']);
            }
            exit;
        }
    }
}
?>