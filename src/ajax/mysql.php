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
    public $conn;

    /**
     * Creates and sets database connection
    */
    private function __construct($dbname) {
		try {
			$this->conn = new mysqli($this->host, $this->user, $this->pass, $dbname);
            $this->conn->set_charset("utf8mb4");
		} catch(Exception $e) {
            error_log($e->getMessage());
            ErrorRegistry::ErrorPage(ErrorRegistry::get('db', 'conn_fail', [$dbname]), $e->getMessage());
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