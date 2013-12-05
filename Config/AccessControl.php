<?php
	
	/*
	$_ranges = array(
		'v4' => array(
			array('141.24.40.0', '26'),
			array('141.24.41.0', '24'),
			array('141.24.42.0', '23'),
			array('141.24.44.0', '22'),
			array('141.24.48.0', '21'),
			array('10.28.0.0', '23'),
			array('127.0.0.1', '32')
		),
		'v6' => array(
			array('2001:638:904:ffc0::', '60'),
			array('2001:638:904:ffd2::', '63'),
			array('2001:638:904:ffd4::', '62'),
			array('2001:638:904:ffd8::', '61'),
			array('2001:638:904:ffe0::', '59')
		)
	);
	*/
	
	// Roles
	AccessControl::addRole('restricteduser');
	AccessControl::addRole('user');
	AccessControl::addRole('owner');
	
	AccessControl::addRole('superuser', array('user'));
	AccessControl::addRole('admin', array('user'));
	
	// Everybody
	AccessControl::allow(null, array('user'), array('login'));
	
	/*if ($this->_IPIsInAllowedRange($_SERVER['REMOTE_ADDR'])) {
		AccessControl::allow(null, array('projects'), array('index'));
		AccessControl::allow(null, array('tickets'), array('feed', 'index', 'view'));
	} else {*/
		AccessControl::allow('user', array('projects'), array('index'));
		AccessControl::allow('user', array('tickets'), array('feed', 'index', 'view'));
	// }
	
	// Restricted User
	AccessControl::allow('restricteduser', array('user'), array('login_complete', 'logout', 'settings', 'changeback', 'act_as_substitute'));
	AccessControl::allow('restricteduser', array('tickets'), array('feed', 'comment', 'cut', 'uncut', 'check', 'uncheck', 'handle', 'unhandle', 'log'));
	
	AccessControl::allow('restricteduser', array('services'), array('workers'));
	
	// User
	AccessControl::allow('user', array('user'), array('login_complete', 'logout', 'settings', 'changeback', 'act_as_substitute'));
	
	AccessControl::allow('user', array('tickets'), array('dashboard', 'comment', 'create', 'cut', 'uncut', 'check', 'uncheck', 'fix', 'unfix', 'handle', 'unhandle', 'reset', 'log', 'edit'));
	AccessControl::allow('owner', array('tickets'), array('delete_comment'));
	AccessControl::allow('user', array('export'), array('index', 'wiki', 'podcast', 'feedback'));
	
	AccessControl::allow('user', array('services'), array('workers'));
	
	// Superuser
	AccessControl::allow('superuser', array('encodingprofiles'), array('index', 'create', 'edit'));
	
	// Admin
	AccessControl::allow('admin');
	
	AccessControl::deny('admin', array('user'), array('act_as_substitute'));
	
?>