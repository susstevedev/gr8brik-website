<?php

require 'Mysql.php';

class Membership {
	
    function validate_user($un, $pwd) {
		$mysql = New Mysql();
		$ensure_credentials = $mysql->verify_Username_and_Pass($un, md5($pwd));

        // Check if 'Remember Me' is checked
            if (isset($_POST['rememberMe'])) {
                // Set session cookie parameters to expire in 100 years
                $lifetime = 100 * 365 * 24 * 60 * 60; // 100 years in seconds
                session_set_cookie_params([
                    'lifetime' => $lifetime,
                    'path' => '/',
                    'domain' => '.gr8brik.rf.gd', // Set to your domain if needed
                    'secure' => true, // Set to true if using HTTPS
                    'httponly' => false,
                    'samesite' => 'Lax' // Adjust as needed
                ]);
            }

        $conn = new mysqli('sql209.infinityfree.com', 'if0_36019408', 'WSdT6MQLXpF1Q', 'if0_36019408_membership');

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
		} else return "Please enter a correct username and password";
		
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

		$username = $conn->real_escape_string($username);
        
        $handle = preg_replace('/[^A-Za-z]/', '', $username . "_");
				
		$password = md5($password);
		
		$email = $conn->real_escape_string($email);
		
		$day = $_POST['day'];
		
		$month = $_POST['month'];
		
		$year = $_POST['year'];
		
		$birthday = mktime(0,0,0,$month,$day,$year);
		
		$difference = time() - $birthday;
		
		$age = floor($difference / 31556926);
		
		// Check for existing username
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
			return "<b>Username or email already exists in database</b>"; // Inform user about duplicate
			die;
		} else {
			$stmt->close(); // Close check statement

			$sql = "INSERT INTO users (username, password, email, age, handle) VALUES ('$username', '$password', '$email', '$age', '$handle')";
	
			if ($conn->query($sql) === TRUE) {
				return "Registration successful!";
			} else {
				return "Error: " . $sql . "<br>" . $conn->error;
			}

			$conn->close();
		}

	
	}

}
