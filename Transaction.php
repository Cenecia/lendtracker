<?php
  class Transaction
	{
		public function getTransactionTypeId($type)
		{
			$pdo = getPdo();
			$typeid = $pdo->query("SELECT id FROM transactionType WHERE name = '$type'")->fetch(PDO::FETCH_COLUMN);
			return $typeid;
		}
		
		public function viewTransactions()
		{
			if(isset($_POST['username'])) {
				$username = filter_var($_POST["username"], FILTER_SANITIZE_STRING);
				$pdo = getPdo();
				$user = new User;
				$userid = $user->getUserIdByUsername($username);
				if($userid) {
					$token = filter_var($_POST["token"], FILTER_SANITIZE_STRING);
					$sortOrder = $_POST['sortDesc'] == 1 ? "DESC" : "";
					$type = filter_var($_POST["type"], FILTER_SANITIZE_STRING);
					$typeid = $this->getTransactionTypeId($type);
					
					if(Security::checkToken($userid, $token)) {
						$loans = $pdo->query("SELECT 
																		t.id, 
																		tt.name as 'type', 
																		amount, 
																		createDate, 
																		confirmed, 
																		recipientName, 
																		description 
																	FROM transaction t 
																	JOIN transactionType tt ON t.transactionTypeID = tt.id 
																	WHERE userID = $userid
																	AND active = 1
																	AND transactionTypeID = $typeid 
																	ORDER BY createDate $sortOrder;")->fetchAll();
						$error = $pdo->errorInfo();
						if($error[0] != 0){
							echo "There was a problem getting transactions.";
							return;
						}
						$loanPaymentsResults = $pdo->query("SELECT 
																									p.id as 'paymentId', 
																									p.transactionId as 'transactionId', 
																									p.amount as 'paymentAmount', 
																									p.confirmed as 'paymentConfirmed', 
																									p.createDate 
																								FROM payment p 
																								JOIN transaction t ON p.transactionID = t.id 
																								WHERE t.userID = $userid
																								AND t.active = 1 
																								AND p.active = 1 
																								AND transactionTypeID = $typeid 
																								ORDER BY p.createDate;")->fetchAll(PDO::FETCH_ASSOC);
						$error = $pdo->errorInfo();
						if($error[0] != 0){
							echo "There was a problem getting transactions.";
							return;
						}
						foreach($loans as $loan) {
							$data['transactions'][$loan['id']]['loan'] = json_encode($loan);
							$data['transactions'][$loan['id']]['remaining'] = $loan['amount'];
						}
						foreach($loanPaymentsResults as $payment) {
							$data['transactions'][$payment['transactionId']]['payments'][$payment['paymentId']] = json_encode($payment);
							$data['transactions'][$payment['transactionId']]['remaining'] -= $payment['paymentAmount'];
						}
						
						echo json_encode($data);
						return;
					}
				}
			}
			echo "0";
		}
    
		public function simpleLoan(){
			if(isset($_POST['token'])) {
				$username = filter_var($_POST["user"], FILTER_SANITIZE_STRING);
				$pdo = getPdo();
				$user = new User;
				$userid = $user->getUserIdByUsername($username);
				if($userid) {
					$token = filter_var($_POST['token'], FILTER_SANITIZE_STRING);
					if(Security::checkToken($userid, $token)) {
						$lender = $userid;
						$recipient = filter_var($_POST['recipient'], FILTER_SANITIZE_STRING);
						if(strlen($recipient) == 0){
							echo "recipient cannot be blank";
							return;
						}
						$amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);
						$amount = number_format((float)$amount, 2, '.', '');
						if($amount <= 0 || $amount >= 1000000){
							echo "invalid amount";
							return;
						}
						$description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
						$transactionType = filter_var($_POST['transactionType'], FILTER_VALIDATE_INT);
						$stmt = $pdo->prepare('INSERT INTO transaction (transactionTypeID, description, amount, userID, recipientName, confirmed, createDate) VALUES (?,?,?,?,?,?, NOW());');
						$stmt->execute([$transactionType, $description, $amount, $lender, $recipient, 1]);
						$error = $pdo->errorInfo();
						if($error[0] != 0){
							echo "There was a problem saving this loan.";
							return;
						}
						echo "1";
						return;
					}
				}
			}
			echo "0";
		}
    
		public function getTransaction()
		{
			if(isset($_POST['username'])) {
				$username = filter_var($_POST["username"], FILTER_SANITIZE_STRING);
				$pdo = getPdo();
				$user = new User;
				$userid = $user->getUserIdByUsername($username);
				if($userid) {
					$token = filter_var($_POST["token"], FILTER_SANITIZE_STRING);
					
					if(Security::checkToken($userid, $token)) {
						$transactionID = filter_var($_POST['transactionID'], FILTER_VALIDATE_INT);
						$loan = $pdo->query("SELECT id, amount, createDate, confirmed, recipientName, description FROM transaction WHERE userID = $userid AND id = $transactionID;")->fetch(PDO::FETCH_OBJ);
						$error = $pdo->errorInfo();
						if($error[0] != 0){
							echo "There was a problem getting transaction.";
							return;
						}
						echo json_encode($loan);
						return;
					}
				}
			}
			echo "0";
		}
    
		public function getTransactionTypes()
		{
			$pdo = getPdo();
			$transactionTypes = $pdo->query("SELECT * FROM transactionType WHERE `key` = 'inc' OR `key` = 'out';")->fetchAll();
			$error = $pdo->errorInfo();
			if($error[0] != 0){
				echo "There was a problem getting transaction types.";
				return;
			}
			echo json_encode($transactionTypes);
			return;
		}
    
		public function cancelTransaction(){
			if(isset($_POST['username'])) {
				$username = filter_var($_POST["username"], FILTER_SANITIZE_STRING);
				$pdo = getPdo();
				$user = new User;
				$userid = $user->getUserIdByUsername($username);
				if($userid) {
					$token = filter_var($_POST["token"], FILTER_SANITIZE_STRING);
					if(Security::checkToken($userid, $token)) {
						$transactionID = filter_var($_POST['transactionID'], FILTER_VALIDATE_INT);
						$stmt = $pdo->prepare('UPDATE transaction SET active = 0 WHERE id = ? AND userID = ?;');
						$stmt->execute([$transactionID, $userid]);
						$error = $pdo->errorInfo();
						if($error[0] != 0){
							echo "There was a problem cancelling this transaction.";
							return;
						}
						echo "1";
						return;
					}
				}
			}
			echo "0";
		}
  }    