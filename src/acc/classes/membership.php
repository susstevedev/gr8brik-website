<?php

require 'Mysql.php';

require 'constants.php';

class Membership {
	
    function validate_user($un, $pwd) {
		$mysql = New Mysql();
		$ensure_credentials = $mysql->verify_Username_and_Pass($un, md5($pwd));

        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

        if ($conn->connect_error) {
            echo "Database connection failed. Please check your database configuration.";
            die;
        }

        $sql = "SELECT id FROM users WHERE username = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $_POST['username']);
        $stmt->execute();
        $stmt->bind_result($id);
        $stmt->fetch();
        $stmt->close();
		
		if($ensure_credentials) {
			$_SESSION['status'] = 'authorized';
            $_SESSION['username'] = $id;
			header("location: index.php");
		} else return "Invalid username or password.";
		
	}
	
	function log_User_Out() {
		if(isset($_SESSION['status'])) {
			unset($_SESSION['status']);
			
			if(isset($_COOKIE['session_name()'])) setcookie(session_name(), '', time() - 1000);
			session_destroy();
			header("location: login.php");
		}
	}
	
	function confirm_Member() {
		session_start();
		if($_SESSION['status'] != 'authorized' || $_SESSION['username'] = "") header("location: login.php");
	}
	public function register_User($username, $password, $email) {
    $conn = new mysqli('sql209.infinityfree.com', 'if0_36019408', 'WSdT6MQLXpF1Q', 'if0_36019408_membership');

		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}

		$username = $conn->real_escape_string(htmlspecialchars($username));
				
		$password = md5($password);
		
		$email = $conn->real_escape_string($email);

        $age = date("Y-m-d");
		
		$sql_check = "
			SELECT id FROM users WHERE username = ?
			UNION ALL
			SELECT id FROM users WHERE email = ?
		";
		$stmt = $conn->prepare($sql_check);
		$stmt->bind_param("ss", $username, $email);
		$stmt->execute();
		$stmt->store_result();

		if ($stmt->num_rows > 0) {
			$stmt->close();
			$conn->close();
			return "Username or email is already in use.";
		} else {
			$stmt->close();

			$sql = "INSERT INTO users (username, password, email) VALUES ('$username', '$password', '$email')";
	
			if ($conn->query($sql) === TRUE) {
				return "Registration successful! Username is " . $username . "!";
			} else {
				return "Error: " . $sql . "<br>" . $conn->error;
			}

			$conn->close();
		}

	
	}

}
