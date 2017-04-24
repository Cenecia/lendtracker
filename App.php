<?php

  require_once 'Security.php';
	require_once 'config.php';

  class App
  {
		public function register()
		{
			if(isset($_POST['username'])) {
				$username = filter_var($_POST["username"], FILTER_SANITIZE_STRING);
				$pwsalt = password_hash($_SERVER['HTTP_USER_AGENT'], PASSWORD_DEFAULT);
				$password = password_hash($pwsalt.$_POST['password'], PASSWORD_DEFAULT);

				$pdo = getPdo();
				$stmt = $pdo->prepare('INSERT INTO user (username, password, pwsalt) VALUES (?,?,?)');
				$stmt->execute([$username, $password, $pwsalt]);

				$data['message'] = "account created successfully";

				echo json_encode($data);
			}
		}
		
		public function reloadAll()
		{
			if(isset($_REQUEST['token'])) {
				$userid = filter_var($_REQUEST["user"], FILTER_VALIDATE_INT);
				$token = filter_var($_REQUEST['token'], FILTER_SANITIZE_STRING);
        
				if(Security::checkToken($userid, $token)) {
					$pdo = getPdo();
					$loans = $pdo->query("SELECT id as 'transactionId', amount as 'initialAmount', createDate as 'lendDate', confirmed as 'transactionConfirmed', createDate as 'transactionDate', description FROM transaction WHERE userID = $userid;")->fetchAll();
					$loanPaymentsResults = $pdo->query("SELECT p.id as 'paymentId', p.transactionId as 'transactionId', p.amount as 'paymentAmount', p.confirmed as 'paymentConfirmed', p.createDate as 'paymentDate' FROM payment p JOIN transaction t ON p.transactionID = t.id WHERE t.userID = $userid;")->fetchAll(PDO::FETCH_ASSOC);

					foreach($loans as $loan) {
						$data['transactions'][$loan['transactionId']]['loan'] = json_encode($loan);
						$data['transactions'][$loan['transactionId']]['remaining'] = $loan['initialAmount'];
						$data['transactions'][$loan['transactionId']]['paid'] = 0;
					}

					foreach($loanPaymentsResults as $payment) {
						$data['transactions'][$payment['transactionId']]['payments'][$payment['paymentId']] = json_encode($payment);
						$data['transactions'][$payment['transactionId']]['remaining'] -= $payment['paymentAmount'];
						$data['transactions'][$payment['transactionId']]['paid'] += $payment['paymentAmount'];
					}

					$payments = $pdo->query("SELECT id as 'transactionId', amount as 'initialAmount', confirmed as 'transactionConfirmed', createDate as 'transactionDate' FROM transaction WHERE otherUserID = $userid;")->fetchAll();
					$paymentPaymentsResults = $pdo->query("SELECT p.id as 'paymentId', p.transactionId as 'transactionId', p.amount as 'paymentAmount', p.confirmed as 'paymentConfirmed', p.createDate as 'paymentDate' FROM payment p JOIN transaction t ON p.transactionID = t.id WHERE t.otherUserID = $userid;")->fetchAll(PDO::FETCH_ASSOC);

					foreach($payments as $pay) {
						$data['payments'][$pay['transactionId']]['loan'] = json_encode($pay);
						$data['payments'][$pay['transactionId']]['remaining'] = $pay['initialAmount'];
						$data['payments'][$pay['transactionId']]['paid'] = 0;
					}

					foreach($paymentPaymentsResults as $payPay) {
						$data['payments'][$payPay['transactionId']]['payments'][$payPay['paymentId']] = json_encode($payPay);
						$data['payments'][$payPay['transactionId']]['remaining'] -= $payPay['paymentAmount'];
						$data['payments'][$payPay['transactionId']]['paid'] += $payPay['paymentAmount'];
					}

					$contacts = $pdo->query("SELECT u.id, u.username, uc.accepted FROM userContact uc JOIN user u ON uc.contactUserID = u.id WHERE uc.userID = $userid;")->fetchAll(PDO::FETCH_ASSOC);

					$data['contacts'] = json_encode($contacts);
					$data['message'] = "success";
				}
			}
			echo json_encode($data);
		}
		
		public function loginApp()
		{
			if(isset($_POST['username'])) {
				$username = filter_var($_POST["username"], FILTER_SANITIZE_STRING);
				$pdo = getPdo();
				$userid = $pdo->query("SELECT id FROM user WHERE username = '$username'")->fetchAll(PDO::FETCH_COLUMN);
				if(isset($userid[0]) && $userid[0] > 0) {
					$user = $userid[0];
					$password = filter_var($_POST["password"], FILTER_SANITIZE_STRING);

					if(Security::checkLogin($user, $password)) {
						
						$loans = $pdo->query("SELECT id as 'transactionId', amount as 'initialAmount', confirmed as 'transactionConfirmed', createDate as 'transactionDate', description FROM transaction WHERE userID = $user;")->fetchAll();
						$loanPaymentsResults = $pdo->query("SELECT p.id as 'paymentId', p.transactionId as 'transactionId', p.amount as 'paymentAmount', p.confirmed as 'paymentConfirmed', p.createDate as 'paymentDate' FROM payment p JOIN transaction t ON p.transactionID = t.id WHERE t.userID = $user;")->fetchAll(PDO::FETCH_ASSOC);

						foreach($loans as $loan) {
							$data['transactions'][$loan['transactionId']]['loan'] = json_encode($loan);
							$data['transactions'][$loan['transactionId']]['remaining'] = $loan['initialAmount'];
							$data['transactions'][$loan['transactionId']]['paid'] = 0;
						}

						foreach($loanPaymentsResults as $payment) {
							$data['transactions'][$payment['transactionId']]['payments'][$payment['paymentId']] = json_encode($payment);
							$data['transactions'][$payment['transactionId']]['remaining'] -= $payment['paymentAmount'];
							$data['transactions'][$payment['transactionId']]['paid'] += $payment['paymentAmount'];
						}

						$payments = $pdo->query("SELECT id as 'transactionId', amount as 'initialAmount', confirmed as 'transactionConfirmed', createDate as 'transactionDate' FROM transaction WHERE otherUserID = $user;")->fetchAll();
						$paymentPaymentsResults = $pdo->query("SELECT p.id as 'paymentId', p.transactionId as 'transactionId', p.amount as 'paymentAmount', p.confirmed as 'paymentConfirmed', p.createDate as 'paymentDate' FROM payment p JOIN transaction t ON p.transactionID = t.id WHERE t.otherUserID = $user;")->fetchAll(PDO::FETCH_ASSOC);

						foreach($payments as $pay) {
							$data['payments'][$pay['transactionId']]['loan'] = json_encode($pay);
							$data['payments'][$pay['transactionId']]['remaining'] = $pay['initialAmount'];
							$data['payments'][$pay['transactionId']]['paid'] = 0;
						}

						foreach($paymentPaymentsResults as $payPay) {
							$data['payments'][$payPay['transactionId']]['payments'][$payPay['paymentId']] = json_encode($payPay);
							$data['payments'][$payPay['transactionId']]['remaining'] -= $payPay['paymentAmount'];
							$data['payments'][$payPay['transactionId']]['paid'] += $payPay['paymentAmount'];
						}

						$contacts = $pdo->query("SELECT u.id, u.username, uc.accepted FROM userContact uc JOIN user u ON uc.contactUserID = u.id WHERE uc.userID = $user;")->fetchAll(PDO::FETCH_ASSOC);

						$data['contacts'] = json_encode($contacts);
						$data['token'] = Security::newToken($user, $password);
						$data['message'] = "success";
						$data['userid'] = $user;
					} else {
						$data['message'] = "fail";
					}
				} else {
					$data['message'] = "fail";
				}
			}

			echo json_encode($data);
		}

		public function viewTransactions()
		{
			if(isset($_POST['username'])) {
				$username = filter_var($_POST["username"], FILTER_SANITIZE_STRING);
				$pdo = getPdo();
				$userid = $pdo->query("SELECT id FROM user WHERE username = '$username'")->fetchAll(PDO::FETCH_COLUMN);
				if($userid[0] > 0) {
					$user = $userid[0];
					$password = filter_var($_POST["password"], FILTER_SANITIZE_STRING);
					
					if(Security::checkLogin($user, $password)) {
						$loans = $pdo->query("SELECT id as 'transactionId', amount as 'initialAmount', createDate as 'lendDate', confirmed as 'transactionConfirmed' FROM transaction WHERE userID = $user;")->fetchAll();
						$loanPaymentsResults = $pdo->query("SELECT p.id as 'paymentId', p.transactionId as 'transactionId', p.amount as 'paymentAmount', p.confirmed as 'paymentConfirmed' FROM payment p JOIN transaction t ON p.transactionID = t.id WHERE t.userID = $user;")->fetchAll(PDO::FETCH_ASSOC);

						foreach($loans as $loan) {
							$data['transactions'][$loan['transactionId']]['loan'] = json_encode($loan);
						}

						foreach($loanPaymentsResults as $payment) {
							$data['transactions'][$payment['transactionId']]['payments'][$payment['paymentId']] = json_encode($payment);
						}

						$payments = $pdo->query("SELECT id as 'transactionId', amount as 'initialAmount', createDate as 'lendDate', confirmed as 'transactionConfirmed' FROM transaction WHERE otherUserID = $user;")->fetchAll();
						$paymentPaymentsResults = $pdo->query("SELECT p.id as 'paymentId', p.transactionId as 'transactionId', p.amount as 'paymentAmount', p.confirmed as 'paymentConfirmed' FROM payment p JOIN transaction t ON p.transactionID = t.id WHERE t.otherUserID = $user;")->fetchAll(PDO::FETCH_ASSOC);

						foreach($payments as $pay) {
							$data['payments'][$pay['transactionId']]['loan'] = json_encode($pay);
						}

						foreach($paymentPaymentsResults as $payPay) {
							$data['payments'][$payPay['transactionId']]['payments'][$payPay['paymentId']] = json_encode($payPay);
						}

						$data['message'] = "success";
					}
					else {
						$data['message'] = "wrong key";
					}

				} else {
					$data['message'] = 'View transactions:';
				}

				echo json_encode($data);
			}
		}
    
		public function confirmTransaction()
		{
			if(isset($_POST['token'])) {
				$userid = filter_var($_POST["user"], FILTER_VALIDATE_INT);
				$token = filter_var($_POST['token'], FILTER_SANITIZE_STRING);
        
				if(Security::checkToken($userid, $token)) {
					$pdo = getPdo();
					if($userid > 0) {
						$transaction = filter_var($_POST['transaction'], FILTER_VALIDATE_INT);
						$type = filter_var($_POST["type"], FILTER_SANITIZE_STRING);
						if($type == "l") {
							$stmt = $pdo->query("SELECT * FROM transaction WHERE id = $transaction AND otherUserID = $userid AND confirmed = 0;");
							if($stmt->rowCount() > 0) {
								$stmt = $pdo->prepare("UPDATE transaction SET confirmed = 1 WHERE id = ?;");
								$stmt->execute([$transaction]);

								$data['message'] = "transaction confirmed";
							}
							else {
								$data['message'] = "invalid transaction id";
							}
						}
						else if($type == "p"){
							$stmt = $pdo->query("SELECT p.id FROM payment p JOIN transaction t ON p.transactionID = t.id WHERE p.id = $transaction AND t.userID = $userid AND p.confirmed = 0;");
							if($stmt->rowCount() > 0) {
								$stmt = $pdo->prepare("UPDATE payment SET confirmed = 1 WHERE id = ?;");
								$stmt->execute([$transaction]);

								$data['message'] = "payment confirmed";
							}
							else {
								$data['message'] = "invalid transaction id";
							}
						} 
						else {
							$data['message'] = 'invalid type';
						}
					}
					else {
						$data["message"] = "fail 1";
					}
				} else {
					$data["message"] = "token fail";
				}

			}
			else {
				$data["message"] = "fail 2";
			}

			echo $data['message'];
		}
    
		public function createLoan()
		{
			if(isset($_POST['token'])) {
				$userid = filter_var($_POST["user"], FILTER_VALIDATE_INT);
				$token = filter_var($_POST['token'], FILTER_SANITIZE_STRING);
        
				if(Security::checkToken($userid, $token)) {
					$lender = $userid;
					$borrower = filter_var($_POST['borrower'], FILTER_VALIDATE_INT);
					$pdo = getPdo();
					//$borrowerid = $pdo->query("SELECT id FROM user WHERE username = '$borrower'")->fetchAll(PDO::FETCH_COLUMN);
					//if($borrowerid[0] > 0) {
					$amount = filter_var($_POST['amount'], FILTER_VALIDATE_INT);
					$description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
					$stmt = $pdo->prepare('INSERT INTO transaction (transactionTypeID, description, amount, userID, otherUserID, createDate) VALUES (?,?,?,?,?, NOW());');
					$stmt->execute([1,$description, $amount, $lender, $borrower]);
					$data['message'] = "loan added";
					//}
					//else {
						//$data['message'] = "invalid borrower";
					//}
				}
				else {
					$data["message"] = "bad token";
				}
			}
			else {
				$data["message"] = "bad token";
			}
		}
    
		public function createPayment()
		{
			if(isset($_POST['token'])) {
				$userid = filter_var($_POST["user"], FILTER_VALIDATE_INT);
        
				if(Security::checkToken($userid, $token)) {
					$amount = filter_var($_POST['amount'], FILTER_VALIDATE_INT);
					$transaction = filter_var($_POST['transaction'], FILTER_VALIDATE_INT);
					$stmt = $pdo->query("SELECT * FROM transaction WHERE id = $transaction AND otherUserID = $userid AND confirmed = 1;");
					if($stmt->rowCount() > 0) {
						$stmt = $pdo->prepare('INSERT INTO payment (transactionID, amount, createDate) VALUES (?,?, NOW());');
						$stmt->execute([$transaction, $amount]);
					}
					$data['message'] = "payment added";
				}
				else {
					$data['message'] = "bad token";
				}
			}
			else {
				$data['message'] = "bad token";
			}
		}
		
		public function addContact()
		{
			if(isset($_POST['token'])) {
				$userid = filter_var($_POST["user"], FILTER_VALIDATE_INT);
				$token = filter_var($_POST['token'], FILTER_SANITIZE_STRING);
        
				if(Security::checkToken($userid, $token)) {
					$contactUser = filter_var($_POST['contact'], FILTER_VALIDATE_INT);
					$pdo = getPdo();
					$stmt = $pdo->query("SELECT * FROM userContact WHERE userID = $userid AND contactUserID = $contactUser;");
					if($stmt->rowCount() == 0) {
						$stmt = $pdo->prepare('INSERT INTO userContact (userID, contactUserID, accepted) VALUES (?,?,0);');
						$stmt->execute([$userid, $contactUser]);
					}
					$data['message'] = "contact added";
				}
				else {
					$data['message'] = "bad token";
				}
			}
			else {
				$data['message'] = "bad token";
			}
		}
		
		public function contactList()
		{
			if(isset($_POST['token'])) {
				$userid = filter_var($_POST["user"], FILTER_VALIDATE_INT);
				$token = filter_var($_POST['token'], FILTER_SANITIZE_STRING);
        
				if(Security::checkToken($userid, $token)) {
					$pdo = getPdo();
					$results = $pdo->query("SELECT username, accepted FROM userContact uc JOIN user u ON u.contactUserID = u.id WHERE userID = $userid;")->fetchAll(PDO::FETCH_ASSOC);

					$data['contacts'] = json_encode($results);
					$data['message'] = "contact added";
				}
				else {
					$data['message'] = "bad token";
				}
			}
			else {
				$data['message'] = "bad token";
			}			
		}
	}