<?php
  class User
  {
		public function tryLogin(){
			if(isset($_POST['username'])) {
				$username = filter_var($_POST["username"], FILTER_SANITIZE_STRING);
				$pdo = getPdo();
				$userid = $pdo->query("SELECT id FROM user WHERE username = '$username'")->fetch(PDO::FETCH_COLUMN);
				if($userid) {
					$password = filter_var($_POST["password"], FILTER_SANITIZE_STRING);
					if(Security::checkLogin($userid, $password)) {
						echo Security::newToken($userid, $password);
						return;
					}
				}
			}
			echo 0;
			return;
		}
		
		public function register()
		{
			if(isset($_POST['username'])) {
				$username = filter_var($_POST["username"], FILTER_VALIDATE_EMAIL);
				if(!$username){
					echo "Invalid Email Address";
					return;
				}
				$pdo = getPdo();
				$userid = $pdo->query("SELECT id FROM user WHERE username = '$username'")->fetch(PDO::FETCH_COLUMN);
				if($userid){
					echo "Username already exists";
					return;
				}
				if(strlen($_POST['password']) < 8){
					echo "Password too short";
					return;
				}
				elseif(strlen($_POST['password']) > 32){
					echo "Password too long";
					return;
				}
				$pwsalt = password_hash(Security::randomStr(), PASSWORD_DEFAULT);
				$password = password_hash($pwsalt.$_POST['password'], PASSWORD_DEFAULT);
				$stmt = $pdo->prepare('INSERT INTO user (username, password, pwsalt) VALUES (?,?,?)');
				$stmt->execute([$username, $password, $pwsalt]);
				$error = $pdo->errorInfo();
				if($error[0] != 0){
					echo "There was a problem registering.";
					return;
				}
				echo "1";
				return;
			}
			echo "0";
		}
		
		public function tokenResetPassword(){
			if(isset($_POST['username'])) {
				$username = filter_var($_POST["username"], FILTER_VALIDATE_EMAIL);
				$pdo = getPdo();
				$userid = $pdo->query("SELECT id FROM user WHERE username = '$username'")->fetch(PDO::FETCH_COLUMN);
				if($userid) {
					$randomPassword = Security::randomStr();
					$pwsalt = password_hash(Security::randomStr(), PASSWORD_DEFAULT);
					$newPassword = password_hash($pwsalt.$randomPassword, PASSWORD_DEFAULT);
					$stmt = $pdo->prepare('UPDATE user SET password = ?, pwsalt = ? WHERE id = ?;');
					$stmt->execute([$newPassword, $pwsalt, $userid]);
					$error = $pdo->errorInfo();
					if($error[0] != 0){
						echo "There was a problem restting password.";
						return;
					}
					$emailSubj = "Lendtracker Password Reset";
					$msg = "Your password for Lendtracker was reset. Your new password is $randomPassword.";
					Security::sendEmail($username, $emailSubj, $msg);
					echo "1";
					return;
				}
			}
			echo "Old password was incorrect. Please retry.";
		}
		
		public function resetPassword()
		{
			if(isset($_POST['username'])) {
				$username = filter_var($_POST["username"], FILTER_SANITIZE_STRING);
				$pdo = getPdo();
				$userid = $pdo->query("SELECT id FROM user WHERE username = '$username'")->fetch(PDO::FETCH_COLUMN);
				if($userid) {
					$oldPassword = filter_var($_POST["oldPassword"], FILTER_SANITIZE_STRING);
					if(Security::checkLogin($userid, $oldPassword)) {
						if(strlen($_POST['newPassword']) < 8){
							echo "Password too short";
							return;
						}
						elseif(strlen($_POST['newPassword']) > 32){
							echo "Password too long";
							return;
						}
						$pwsalt = password_hash(Security::randomStr(), PASSWORD_DEFAULT);
						$newPassword = password_hash($pwsalt.$_POST['newPassword'], PASSWORD_DEFAULT);
						$stmt = $pdo->prepare('UPDATE user SET password = ?, pwsalt = ? WHERE id = ?;');
						$stmt->execute([$newPassword, $pwsalt, $userid]);
						$error = $pdo->errorInfo();
						if($error[0] != 0){
							echo "There was a problem restting password.";
							return;
						}
						echo "1";
						return;
					}
				}
			}
			echo "Old password was incorrect. Please retry.";
		}
		
		public function getUserIdByUsername($username){
			$pdo = getPdo();
			$userid = $pdo->query("SELECT id FROM user WHERE username = '$username'")->fetch(PDO::FETCH_COLUMN);
			return $userid;
		}
  }