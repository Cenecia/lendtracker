<?php 

	require_once 'App.php';

	$key = filter_var($_REQUEST["key"], FILTER_SANITIZE_STRING);

	if(isset($_REQUEST["action"]) && $key == "sX216917yESxF6n") {

		$app = new App;

		$action = filter_var($_REQUEST["action"], FILTER_SANITIZE_STRING);

		require_once 'config.php';

		switch($action) {
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
			default:
				break;
		}
	}
?>