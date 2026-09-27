<?php

require_once 'constants.php';

class Mysql {
	private $conn;
	
	function __construct() {
		$this->conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME) or die('There was a problem connecting to the database');
	}
	
    function verify_Username_and_Pass($un, $pwd) {
		
		$query = "SELECT * FROM users WHERE username = ? AND password = ?";
		
		if($stmt = $this->conn->prepare($query)) {
			$stmt->bind_param('ss', $un, $pwd);
			$stmt->execute();
			
			
			if($stmt->fetch()) {
				$stmt->close();
				return true;
			}
		
		}
		
	}

	function verify_user_id($id, $un) {
		
		$query = "SELECT * FROM users WHERE id = ?";
		
		if($stmt = $this->conn->prepare($query)) {
			$stmt->bind_param('ss', $id);
			$stmt->execute();
			
			
			if($stmt->fetch()) {
				$stmt->close();
				return true;
			}
		
		}
		
	}

}