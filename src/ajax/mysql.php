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

/**
 * Reusable CRUD class
 */
class mysqli_crud {
    public mysqli $db;

    public function __construct($dbname) {
        $this->db = Database::get($dbname);
    }

    /**
     * Function to bind prepared statement object parameters
     */
    private function bind_params($stmt, $params) {
        $types = "";

        if (!empty($params)) {
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= "i";
                } elseif (is_float($param)) {
                    $types .= "d";
                } else {
                    $types .= "s";
                }
            }
            $stmt->bind_param($types, ...$params);
        }
    }

    /**
     * Insert data into a table
    **/
    public function create($table, $data) {
        $columns = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), "?"));
        $stmt = $this->db->prepare("insert into `$table` ($columns) values ($placeholders)");

        if ($stmt) {
            $this->bind_params($stmt, array_values($data));
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }

        return false;
    }

    /**
     * Select one row from a table
    **/
    public function readone($table, $columns = "*", $where = "", $params = []) {
        $sql = "select $columns from `$table`";

        if (!empty($where)) {
            $sql .= " where $where";
        }

        $stmt = $this->db->prepare($sql);

        if ($stmt) {
            $this->bind_params($stmt, $params);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc() ?? false;
            $stmt->close();
            return $data;
        }

        return false;
    }

    /**
     * Select multiple rows from a table
    **/
    public function readall($table, $columns = "*", $where = "", $params = [], $order = "", $limit = null, $offset = null) {
        $sql = "select $columns from `$table`";

        if (!empty($where)) {
            $sql .= " where $where";
        }

        if (!empty($order)) {
            $sql .= " order by $order";
        }

        if (!empty($limit)) {
            $sql .= " limit $limit";
        }

        if (!empty($offset)) {
            $sql .= " offset $offset";
        }

        $stmt = $this->db->prepare($sql);

        if ($stmt) {
            $this->bind_params($stmt, $params);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?? false;
            $stmt->close();
            return $data;
        }

        return false;
    }

    /**
     * Modify data in a table
    **/
    public function update($table, $data, $where, $where_params = []) {
        $segments = [];
        foreach (array_keys($data) as $column) {
            $segments[] = "`$column` = ?";
        }

        $set_string = implode(", ", $segments);
        $sql = "update `$table` set $set_string where $where";
        $stmt = $this->db->prepare($sql);

        if ($stmt) {
            $all_params = array_merge(array_values($data), $where_params);
            $this->bind_params($stmt, $all_params);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
        return false;
    }

    /**
     * Remove data from a table
    **/
    public function delete($table, $where, $params = []) {
        $sql = "delete from `$table` where $where";
        $stmt = $this->db->prepare($sql);

        if ($stmt) {
            $this->bind_params($stmt, $params);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
        return false;
    }
}
?>