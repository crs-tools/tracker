<?php
	
	Log::setPath(ROOT . 'Log/application.log');
	Log::setLevel(Log::INFO);
	Log::colorize(true);
	
	Database_PostgreSQL::init(
		'__DB_HOST__',
		'__DB_USER__',
		'__DB_PASS__',
		'__DB_NAME__'
	);

	// TODO: make timezone a user setting or get it from browser 
	Database::$Instance->query(
		'SET timezone = ' . Database::$Instance->quote(date_default_timezone_get())
	);

	requires('Cache/Adapter/APC');
	Cache::setAdapter(new Cache_Adapter_APC());
	
	session_set_cookie_params(0, '/', null, false, true);
	
	libxml_disable_entity_loader(true);
	
?>