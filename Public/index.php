<?php
	
	$time = microtime(true);
	
	define('ROOT', realpath(dirname(__FILE__)). DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
	define('LIBRARY', ROOT . 'Library' . DIRECTORY_SEPARATOR);
	define('APPLICATION', ROOT . 'Application' . DIRECTORY_SEPARATOR);
	
	date_default_timezone_set('Europe/Berlin');
	
	require(LIBRARY. 'Application.php');
	$app = Application::init();
	
	Application::load('Config');
	
	$app->Config->setPath(ROOT . 'Config/');
	
	Log::setPath($app->Config->environment['log']['path']);
	Log::setLevel($app->Config->environment['log']['level']);
	Log::colorize($app->Config->environment['log']['colorize']);
	
	Application::displayErrors($app->Config->environment['errors']['display']);
	Log::setErrorLogging(true);
	
	Application::loadAndInit('Uri');
	require(LIBRARY . 'Request.php');
	
	Application::load('Cache_APC');
	$app->Cache->setNamespace($app->Config->environment['cache']['prefix']);
	
	Application::load('Database_PostgreSQL');
	
	$app->Config->load('Routes');
	
	try {
		$app->Database->connect($app->Config->environment['database']['host'], $app->Config->environment['database']['user'], $app->Config->environment['database']['password'], $app->Config->environment['database']['name']);
	} catch (DatabaseException $exception) {
		Log::handleException($exception);
		require(LIBRARY . 'View.php');
		die(View::error(500));
	}
	
	$app->Config->load('Acl');
	
	// fast forward to RPC
	if (Uri::getSegment(1) == 'rpc' or Uri::getSegment(2) == 'rpc') {
		Controller_XMLRPC::load();
	} else {
		Application::loadAndInit('MimeType');
		Request::init();
		Request::setDomainBoundary(3);
		
		Application::load('User');
		
		require(APPLICATION . 'Helper.php');
		Application::load('View_PHP_Helper');
		$app->View->setTemplatePath(APPLICATION . 'View/');
		$app->View->setLayout('default.tpl');
		$app->View->contentType('text/html');
		
		try {
			Controller::load();
		} catch (NotFoundException $exception) {
			$app->View->setHeaderResponseCode(404);		
			$app->View->render('404.tpl');
		} catch (ActionNotAllowedException $exception) {
			if ($app->User->isLoggedIn()) {
				$app->View->setHeaderResponseCode(403);
				$app->View->render('403.tpl');
			} else {
				$app->View->flash('You have to login to view this page', View::flashWarning);
				$app->View->redirect('user', 'login');
			}
		} catch (Exception $exception) {
			Log::handleException($exception);
			//$app->View->includeTemplate('500.tpl');
			die(View::error(500));
		}
	}
	
	$time = microtime(true) - $time;
	Log::info(sprintf('Processed %s::%s for %s in %.4fs (%d reqs/sec) (View: %d%%, DB: %d%%)', Controller::getController(), Controller::getAction(), Request::getIP(), $time, 1 / $time, (Log::getTimer('View') / $time) * 100, (Log::getTimer('Database') / $time) * 100));
	
?>