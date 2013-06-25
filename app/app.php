<?php

// Bootstrap
require __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

$app->error(function (Exception $e, $code) {
	if ($code == 404) {
		return '404 - Not Found! // ' . $e->getMessage();
	} else {
		return 'Something went wrong // ' . $e->getMessage();
	}
});

// Mount our ControllerProviders
$app->mount('/', new Ikdoeict\Provider\Controller\MainController());
