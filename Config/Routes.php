<?php return array(
	// RPC Routes
	'rpc' => array(':controller' => 'XMLRPC_Handler', ':action' => 'default'),
	//'rpc/:uid/:hostname' => array(':controller' => 'XMLRPC_Handler', ':action' => 'default'),
	//'rpc/:uid/:hostname/:project_slug' => array(':controller' => 'XMLRPC_ProjectHandler', ':action' => 'default'),
	
	// Webapp Routes
	':project_slug/dashboard' => array(':controller' => 'dashboard', ':action' => 'index'),
	':project_slug/dashboard/:action' => array(':controller' => 'dashboard', ':type' => 'application/json'),

	':project_slug/tickets' => array(':controller' => 'tickets', ':action' => 'index'),

	':project_slug/ticket/create' => array(':controller' => 'tickets', ':action' => 'create'),

	':project_slug/ticket/:id/comment' => array(':controller' => 'tickets', ':action' => 'comment'),
	':project_slug/ticket/:ticket_id/comment/delete/:id' => array(':controller' => 'tickets', ':action' => 'delete_comment'),

	':project_slug/ticket/:id/log/:entry' => array(':controller' => 'tickets', ':action' => 'log', ':type' => 'text/plain'),
	':project_slug/ticket/:id' => array(':controller' => 'tickets', ':action' => 'view'),
	':project_slug/ticket/:action/:id' => array(':controller' => 'tickets'),

	':project_slug/tickets/import' => array(':controller' => 'import', ':action' => 'index'),
	':project_slug/tickets/import/review' => array(':controller' => 'import', ':action' => 'review'),
	':project_slug/tickets/import/apply' => array(':controller' => 'import', ':action' => 'apply'),
	':project_slug/tickets/export' => array(':controller' => 'export', ':action' => 'index'),
	':project_slug/tickets/export/wiki' => array(':controller' => 'export', ':action' => 'wiki'),
	':project_slug/tickets/export/podcast/:profile_slug' => array(':controller' => 'export', ':action' => 'podcast'),
	':project_slug/tickets/export/feedback' => array(':controller' => 'export', ':action' => 'feedback'),

	':project_slug/encoding/profiles' => array(':controller' => 'encodingprofiles', ':action' => 'index'),
	':project_slug/encoding/profile/create' => array(':controller' => 'encodingprofiles', ':action' => 'create'),
	':project_slug/encoding/profile/import' => array(':controller' => 'encodingprofiles', ':action' => 'import'),
	':project_slug/encoding/profile/edit/:id' => array(':controller' => 'encodingprofiles', ':action' => 'edit'),
	':project_slug/encoding/profile/delete/:id' => array(':controller' => 'encodingprofiles', ':action' => 'delete'),

	':project_slug/services/worker/:id/halt' => array(':controller' => 'services', ':action' => 'halt'),
	':project_slug/services/worker/:id/command' => array(':controller' => 'services', ':action' => 'command'),

	':project_slug/services/workers' => array(':controller' => 'services', ':action' => 'workers'),
	':project_slug/services/hold' => array(':controller' => 'services', ':action' => 'hold'),
	':project_slug/services/resume' => array(':controller' => 'services', ':action' => 'resume'),

	':project_slug/settings' => array(':controller' => 'projects', ':action' => 'view'),

	'users' => array(':controller' => 'user', ':action' => 'index'),
	'user/create' => array(':controller' => 'user', ':action' => 'create'),
	'user/edit/:id' => array(':controller' => 'user', ':action' => 'edit'),
	'user/delete/:id' => array(':controller' => 'user', ':action' => 'delete'),

	'user/switch/:id' => array(':controller' => 'user', ':action' => 'substitute'),
	'user/exit' => array(':controller' => 'user', ':action' => 'changeback'),

	'project/create' => array(':controller' => 'projects', ':action' => 'create'),
	'project/edit/:id' => array(':controller' => 'projects', ':action' => 'edit'),
	'project/delete/:id' => array(':controller' => 'projects', ':action' => 'delete'),
	'projects' => array(':controller' => 'projects', ':action' => 'index'),

	'login' => array(':controller' => 'user', ':action' => 'login'),
	'logout' => array(':controller' => 'user', ':action' => 'logout'),
	'settings' => array(':controller' => 'user', ':action' => 'settings'),

	':project_slug' => array(':controller' => 'tickets', ':action' => 'feed')
); ?>
