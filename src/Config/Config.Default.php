<?php
	
	Log::setPath(ROOT . '../log/application.log');
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

	// Use settings like these to enable external auth mechanisms
	// like OIDC or SAML, but don't forget to exclude RPC URLs
	// from access control
	#User::setExternalUserHeader('REMOTE_USER');
	#User::setExternalUserHeader('HTTP_X_USER');
?>
