<?php
	
	error_reporting(-1);
	
	Log::setPath(ROOT . 'Log/application.log');
	Log::setLevel(Log::debug);
	Log::colorize(true);
	
	Database_PostgreSQL::init(
		'localhost',
		'c3tt',
		'',
		'c3tt3'
	);
	
	session_set_cookie_params(0, '/', null, false, true);
	
/*
return array(
  'log' => array(
    'path' => ROOT . 'Log/application.log',
    'level' => Log::info,
    'colorize' => false,
  ),
  'errors' => array(
    'display' => true,
    'log' => true,
  ),
  'view' => array(
    'compress' => false,
  ),
  'database' => array(
    'host' => 'localhost',
    'user' => '',
    'password' => '',
    'name' => '',
  ),
  'cache' => array(
    'prefix' => 'fem.tracker',
  )
);
*/
?>
