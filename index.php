<?php 

	require_once 'App.php';

	$key = filter_var($_REQUEST["key"], FILTER_SANITIZE_STRING);

	if(isset($_REQUEST["action"]) && $key == "sX216917yESxF6n") {

		$app = new App;

		$action = filter_var($_REQUEST["action"], FILTER_SANITIZE_STRING);

		switch($action) {
			case "tryLogin":
				$app->tryLogin();
				break;
			case "resetPassword":
				$app->resetPassword();
				break;
			case "simpleLoan":
				$app->simpleLoan();
				break;
			case "simplePayment":
				$app->simplePayment();
				break;
			case "cancelTransaction":
				$app->cancelTransaction();
				break;
			case "loginApp":
				$app->loginApp();
				break;
			case "register":
				$app->register();
				break;
			case "viewTransactions":
				$app->viewTransactions();
				break;
			case "confirmTransaction":
				$app->confirmTransaction();
				break;
			case "confirmPayment":
				$app->confirmPayment();
				break;
			case "createLoan":
				$app->createLoan();
				break;
			case "createPayment":
				$app->createPayment();
				break;
			case "addContact":
				$app->addContact();
				break;
			case "reloadAll":
				$app->reloadAll();
				break;
			case "getTransaction":
				$app->getTransaction();
				break;
			case "getPayment":
				$app->getPayment();
				break;
			case "updatePayment":
				$app->updatePayment();
				break;
			case "cancelPayment":
				$app->cancelPayment();
				break;
			case "getTransactionTypes":
				$app->getTransactionTypes();
				break;
			default:
				break;
		}
	}
?>