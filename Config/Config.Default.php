<?php
	
	error_reporting(-1);
	
	Log::setPath(ROOT . 'Log/application.log');
	Log::setLevel(Log::debug);
	Log::colorize(true);
	
	Database_PostgreSQL::init(
		'__DB_HOST__',
		'__DB_USER__',
		'__DB_PASS__',
		'__DB_NAME__'
	);
	
	requires('Cache/Adapter/APC');
	Cache::setAdapter(new Cache_Adapter_APC());
	
	session_set_cookie_params(0, '/', null, false, true);
	
?>
