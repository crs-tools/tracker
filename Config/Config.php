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
	
	requires('Cache/Adapter/APC');
	Cache::setAdapter(new Cache_Adapter_APC());
	
	session_set_cookie_params(0, '/', null, false, true);
	
?>
