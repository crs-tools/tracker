<?php
	
	// Roles
	AccessControl::addRole('read only');
	AccessControl::addRole('restricted', ['read only']);
	AccessControl::addRole('user', ['restricted']);
	AccessControl::addRole('owner');
	
	AccessControl::addRole('superuser', ['user']);
	AccessControl::addRole('admin', ['user']);
	
	// Everybody
	AccessControl::allow(null, ['user'], ['login']);
	
	// Read only
	AccessControl::allow('read only', ['projects'], ['index']);
	AccessControl::allow('read only', ['user'], [
		'settings', 'logout', 'changeback', 'act_as_substitute'
	]);
	
	AccessControl::allow(
		'read only',
		['tickets'],
		['feed', 'index', 'view', 'log', 'search']
	);
	
	// Restricted
	AccessControl::allow(
		'restricted',
		['tickets'],
		['comment', 'cut', 'uncut', 'check', 'uncheck']
	);
	
	// TODO: owner currently doesn't work with controllers
	// AccessControl::allow('owner', ['tickets'], ['delete_comment']);
	
	// User
	AccessControl::allow('user', ['tickets'], ['edit']);
	
	// Superuser
	AccessControl::allow('superuser', ['projects'], ['view', 'edit']);
	AccessControl::allow('superuser', ['tickets'], ['jobfile']);
	AccessControl::allow('superuser', ['encodingprofiles']);
	AccessControl::allow('superuser', ['workers'], ['index']);
	
	// Admin
	AccessControl::allow('admin');
	
	AccessControl::deny('admin', ['user'], ['act_as_substitute']);
	
?>