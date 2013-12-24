<?php
	
	$time = microtime(true);
	
	define('ROOT', realpath(dirname(__FILE__)). DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
	define('LIBRARY', ROOT . 'Library' . DIRECTORY_SEPARATOR);
	define('APPLICATION', ROOT . 'Application' . DIRECTORY_SEPARATOR);
	
	date_default_timezone_set('Europe/Berlin');
	
	require LIBRARY. 'Application.php';
	
	// TOOD: move some to Controller_Application
	requires(
		'Log',
		'Router',
		'Controller',
		'View',
		'Form',
		'Database/PostgreSQL',
		'AccessControl',
		'/Controller/Application'
	);
	
	require ROOT . 'Config/Config.php';
	require ROOT . 'Config/AccessControl.php';
	
	Router::addRoutes(ROOT . 'Config/Routes.php');
	View::setTemplateDirectory(APPLICATION . 'View/'); // TODO: default?
	
	try {
		$requested = Controller::runWithRequest();
	} catch (NotFoundException $exception) {
		// TODO: init Controller_Application?
		Controller::renderTemplate('404.tpl', array(), null, new Response(404));
	} catch (ActionNotAllowedException $exception) {
		// if (User::isLoggedIn()) {
			Controller::renderTemplate('403.tpl', array(), null, new Response(403));
		// } else {
			/*
			$app->View->flash('You have to login to view this page', View::flashWarning);
			$app->View->redirect('user', 'login');
			*/
		// }
	} catch (Exception $exception) {
		var_dump($exception);
	}
	
	$time = microtime(true) - $time;
	Log::info(sprintf(
		'Processed %s::%s in %.4fs (%d reqs/sec) (View: %d%%, DB: %d%%)',
		$requested['controller'],
		$requested['action'],
		$time, 1 / $time,
		(Log::getTimer('View') / $time) * 100,
		(Log::getTimer('Database') / $time) * 100
	));
	
?>