<?php
// ip ban system
// checks for blacklisted ip address

class IpBans {
    public mysqli $db;
    public string $css_url;
    public string $ip;

    /** 
     * Sets variables for function
     * 
     * @param mysqli $db - Database
     * @param string $css_url - Style sheet url
    */
    public function __construct(mysqli $db) {
        $this->db = $db;
        $this->css_url = 'https://cdn.jsdelivr.net/npm/purecss@latest/build/pure-min.css';
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

        if($ban !== false) {
            if(!stripos($contentType, 'application/json') !== false || stripos($accept, 'application/json') !== false) {
                http_response_code(403);
				?>
                <!DOCTYPE html>
				<html>
					<head>
						<title>Your Ip address has been banned</title>
						<link rel='stylesheet' href='<?php echo $this->css_url ?>'>
					</head>
					<body>
						<center>
							<div>
								<h2>Your Ip address has been banned</h2>
								<p><?php echo $ban['reason'] ?></p>
								<p>Banned at <b><?php echo date("F j, Y, g:i a", strtotime($ban['ban_at'])) ?></b>, until <b><?php echo date("F j, Y, g:i a", strtotime($ban['ban_until'])) ?></b>.</p>
							</div>
							<div>
								<p>To get unbanned, you will have to contact <b><a href='mailto:<?php echo DB_MAIL ?>'><?php echo DB_MAIL ?></a></b> and provide the reason you got banned along with why you should be unbanned.</p>
								<p>Additionally, you can ask for your account to be deleted.</p>
							</div>
							<div>
								<h2>Client details</h2>
								<p>IP: <?php echo $_SERVER['REMOTE_ADDR'] ?></p>
								<p>Device: <?php echo $_SERVER['HTTP_USER_AGENT'] ?></p>
								<p>UserID: <?php echo $_SESSION['userid'] ?? 'not set' ?></p>
							</div>
						</center>
					</body>
				</html>
				<?php
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false]);
            }
            exit;
        }
    }
}
?>