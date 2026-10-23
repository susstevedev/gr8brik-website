<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/errors.php';

/**
 * Manages database instances
*/
class Database {
    private static $instances = [];
    private $host = DB_SERVER;
    private $user = DB_USER;
    private $pass = DB_PASSWORD;
	private string $css_url = 'https://cdn.jsdelivr.net/npm/purecss@latest/build/pure-min.css';
    public $conn;

    /**
     * Creates and sets database connection
    */
    private function __construct($dbname) {
		try {
			$this->conn = new mysqli($this->host, $this->user, $this->pass, $dbname);
		} catch(Exception $e) {
            error_log($e->getMessage());
			?>
				<!DOCTYPE html>
				<html>
					<head>
						<title><?php echo ErrorRegistry::get('db', 'conn_fail', [$dbname]) ?></title>
						<link rel="stylesheet" href='<?php echo $this->css_url ?>'>
					</head>
					<body>
						<center>
							<div>
								<h2><?php echo ErrorRegistry::get('db', 'conn_fail', [$dbname]) ?></h2>
								<p><?php echo $e->getMessage() ?>
								<p><a href="" class="pure-button">Retry</a></p>
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
			exit;
		}
    }

    /**
     * Gets an existing instance
     */
    public static function get($dbname, $object = false, $clone = false) {
        if (!isset(self::$instances[$dbname])) {
            self::$instances[$dbname] = new self($dbname);
        }

        if($clone) {
            $db = new self($dbname);
        } else {
            $db = self::$instances[$dbname];
        }

        if(!$object) {
            $db = $db->conn;
        }

        return $db;
    }

    /* prevent cloning of instance */
    private function __clone() {}
}
?>