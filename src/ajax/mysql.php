<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/constants.php';

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
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $dbname);

        if ($this->conn->connect_error) {
            error_log($this->conn->connect_error);
            exit("Connection to database '$dbname' failed");
        }
    }

    /**
     * Gets an existing instance
     */
    public static function get($dbname, $type = 1) {
        if (!isset(self::$instances[$dbname])) {
            self::$instances[$dbname] = new self($dbname);
        }
        
        /*
        1 = connection
        2 = object
        */
        if($type === 1) {
            return self::$instances[$dbname]->conn;
        } else if($type === 2) {
            return self::$instances[$dbname];
        }
    }

    /* prevent cloning of instance */
    private function __clone() {}
}
?>