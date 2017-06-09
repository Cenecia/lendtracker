<?php
  class Payment
	{
		private function getTransactionTypeKey($transactionID, $userid)
		{
			$pdo = getPdo();
			$key = $pdo->query("SELECT `key` 
													FROM transaction t 
													JOIN transactionType tt ON t.transactionTypeID = tt.id 
													WHERE t.id = $transactionID 
													AND t.userID = $userid;")->fetchAll(PDO::FETCH_COLUMN);
			return $key[0];
		}
		
		public function getPayment()
		{
			if(isset($_POST['username'])) {
				$username = filter_var($_POST["username"], FILTER_SANITIZE_STRING);
				$pdo = getPdo();
				$userid = $pdo->query("SELECT id FROM user WHERE username = '$username'")->fetchAll(PDO::FETCH_COLUMN);
				if($userid[0] > 0) {
					$user = $userid[0];
					$token = filter_var($_POST["token"], FILTER_SANITIZE_STRING);
					
					if(Security::checkToken($user, $token)) {
						$paymentID = filter_var($_POST['paymentID'], FILTER_VALIDATE_INT);
						$payment = $pdo->query("SELECT t.id as transactionID, t.amount as transactionAmount, p.amount as paymentAmount, p.createDate, t.description FROM transaction t JOIN payment p ON p.transactionID = t.id WHERE t.userID = $user AND p.id = $paymentID;")->fetchAll();
						$error = $pdo->errorInfo();
						if($error[0] != 0){
							echo "There was a problem getting data.";
							return;
						}
						echo json_encode($payment);
						return;
					}
				}
			}
			echo "0";
		}
    
		public function updatePayment()
		{
			if(isset($_POST['username'])) {
				$username = filter_var($_POST["username"], FILTER_SANITIZE_STRING);
				$pdo = getPdo();
				$userid = $pdo->query("SELECT id FROM user WHERE username = '$username'")->fetchAll(PDO::FETCH_COLUMN);
				if($userid[0] > 0) {
					$user = $userid[0];
					$token = filter_var($_POST["token"], FILTER_SANITIZE_STRING);
					if(Security::checkToken($user, $token)) {
						$paymentID = filter_var($_POST['paymentID'], FILTER_VALIDATE_INT);
						$count = $pdo->query("SELECT COUNT(p.id) FROM payment p JOIN transaction t ON p.transactionID = t.id WHERE p.id = $paymentID AND t.userID = $user;")->fetchAll(PDO::FETCH_COLUMN);
						if($count > 0){
							$amount = filter_var($_POST['amount'], FILTER_VALIDATE_INT);
							if($amount <= 0){
								echo "invalid amount";
								return;
							}
							$paymentDate = date("Y-m-d", $_POST['date']);
							$originalPaymentAmt = $pdo->query("SELECT p.amount FROM payment p JOIN transaction t ON p.transactionID = t.id WHERE p.id = $paymentID AND userID = $user;")->fetchAll(PDO::FETCH_COLUMN);
							$transactionID = $pdo->query("SELECT p.transactionID FROM payment p JOIN transaction t ON p.transactionID = t.id WHERE p.id = $paymentID AND userID = $user;")->fetchAll(PDO::FETCH_COLUMN);
							if($this->getTransactionTypeKey($transactionID[0], $user) == 'com'){
								echo 'this loan is completed';
								return;
							}
							$remaining = $pdo->query("SELECT t.amount - SUM(p.amount) as 'total' FROM transaction t JOIN payment p ON p.transactionID = t.id WHERE t.id = $transactionID[0] AND userID = $user AND p.active = 1;")->fetchAll(PDO::FETCH_COLUMN);
							$balance = $remaining[0] - $amount + $originalPaymentAmt[0];
							if($balance < 0){
								echo "Payment is more than remaining amount.";
								return;
							}
 							$stmt = $pdo->prepare('UPDATE payment SET amount = ?, createDate = ? WHERE id = ?;');
 							$stmt->execute([$amount, $paymentDate, $paymentID]);
							$error = $pdo->errorInfo();
							if($error[0] != 0){
								echo "There was a problem saving payment.";
								return;
							}
							if($balance == 0){
								$completedID = $this->getTransactionTypeId("Completed");
								$stmt = $pdo->prepare('UPDATE transaction SET transactionTypeID = ? WHERE id = ?;');
								$stmt->execute([$completedID, $transaction]);
							}
							echo "1";
							return;
						}
					}
				}
			}
			echo "0";
		}
    
		public function simplePayment(){
			if(isset($_POST['token'])) {
				$username = filter_var($_POST["username"], FILTER_SANITIZE_STRING);
				$pdo = getPdo();
				$userid = $pdo->query("SELECT id FROM user WHERE username = '$username'")->fetchAll(PDO::FETCH_COLUMN);
				if($userid[0] > 0) {
					$user = $userid[0];
					$token = filter_var($_POST["token"], FILTER_SANITIZE_STRING);
					if(Security::checkToken($user, $token)) {
						$amount = filter_var($_POST['amount'], FILTER_VALIDATE_INT);
						if($amount <= 0){
							echo "invalid amount";
							return;
						}
						$transaction = filter_var($_POST['transactionID'], FILTER_VALIDATE_INT);
						if($this->getTransactionTypeKey($transaction, $user) == 'com'){
							echo 'this loan is completed';
							return;
						}
						$paymentDate = date("Y-m-d", $_POST['date']);
						$remaining = $pdo->query("SELECT t.amount - SUM(p.amount) as 'total' FROM transaction t JOIN payment p ON p.transactionID = t.id WHERE t.id = $transaction AND userID = $user AND p.active = 1;")->fetchAll(PDO::FETCH_COLUMN);
						if(!$remaining[0]){
							$remaining = $pdo->query("SELECT amount FROM transaction WHERE id = $transaction AND userID = $user;")->fetchAll(PDO::FETCH_COLUMN);
						}
						$balance = $remaining[0] - $amount;
						if($balance < 0){
							echo "Payment is more than remaining amount.";
							return;
						}
						$error = $pdo->errorInfo();
						if($error[0] != 0){
							echo "There was a problem saving this payment.";
							return;
						}
						$stmt = $pdo->prepare('INSERT INTO payment (transactionID, amount, createDate) VALUES (?,?,?);');
						$stmt->execute([$transaction, $amount, $paymentDate]);
						$error = $pdo->errorInfo();
						if($error[0] != 0){
							echo "There was a problem saving this payment.";
							return;
						}
						if($balance == 0){
							$completedID = $this->getTransactionTypeId("Completed");
							$stmt = $pdo->prepare('UPDATE transaction SET transactionTypeID = ? WHERE id = ?;');
							$stmt->execute([$completedID, $transaction]);
						}
						echo "1";
						return;
					}
				}
			}
			echo "0";
		}
		
		public function cancelPayment(){
			if(isset($_POST['username'])) {
				$username = filter_var($_POST["username"], FILTER_SANITIZE_STRING);
				$pdo = getPdo();
				$userid = $pdo->query("SELECT id FROM user WHERE username = '$username'")->fetchAll(PDO::FETCH_COLUMN);
				if($userid[0] > 0) {
					$user = $userid[0];
					$token = filter_var($_POST["token"], FILTER_SANITIZE_STRING);
					if(Security::checkToken($user, $token)) {
						$paymentID = filter_var($_POST['paymentID'], FILTER_VALIDATE_INT);
						$count = $pdo->query("SELECT COUNT(p.id) 
																	FROM payment p 
																	JOIN transaction t ON p.transactionID = t.id 
																	JOIN transactionType tt ON t.transactionTypeID = tt.id
																	WHERE p.id = $paymentID 
																	AND t.userID = $user
																	AND tt.key <> 'com';")->fetchAll(PDO::FETCH_COLUMN);
						if($count > 0){
							$stmt = $pdo->prepare('UPDATE payment SET active = 0 WHERE id = ?;');
							$stmt->execute([$paymentID]);
							$error = $pdo->errorInfo();
							if($error[0] != 0){
								echo "There was a problem cancelling this payment.";
								return;
							}
							echo "1";
							return;
						}
					}
				}
			}
			echo "0";
		}
  }