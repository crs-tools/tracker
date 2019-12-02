<?php
	
	$time = microtime(true);
	
	define('ROOT', realpath(__DIR__ . '/..') . '/');
	define('LIBRARY', ROOT . '../vendor/framework/Library/');
	define('APPLICATION', ROOT . 'Application/');
	
	date_default_timezone_set('Europe/Berlin');
	
	require LIBRARY . 'Application.php';
	
	requires(
		'AccessControl',
		'Controller',
		'Database/PostgreSQL',
		'Log',
		'Router',
		'View',
		'/Controller/Application'
	);
	
	Log::enableErrorHandler();
	
	try {
		require ROOT . 'Config/Config.php';
		require ROOT . 'Config/AccessControl.php';
	
		Router::addRoutes(ROOT . 'Config/Routes.php');
		
		$requested = Controller::run();
		
		$time = microtime(true) - $time;
		Log::info(sprintf(
			'Processed %s::%s in %.4fs (%d reqs/s)',
			$requested['controller'],
			$requested['action'],
			$time, 1 / $time
		));
	} catch (Exception $e) {
		Log::exception($e);
		
		$c = ($e->getCode() > 400)? $e->getCode() : 500;
		echo Controller::renderTemplate(
			$c,
			['exception' => $e],
			null,
			new Response($c)
		);
	}
	
?>