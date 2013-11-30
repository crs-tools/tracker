<?php return array(
	'rpc' => array(':controller' => 'XMLRPC_Handler', ':action' => 'default'),
	
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

	':project_slug/workers' => array(':controller' => 'workers', ':action' => 'project'),
	':project_slug/services/hold' => array(':controller' => 'services', ':action' => 'hold'),
	':project_slug/services/resume' => array(':controller' => 'services', ':action' => 'resume'),

	':project_slug/settings' => array(':controller' => 'projects', ':action' => 'view'),
	
	'workers' => array(':controller' => 'workers', ':action' => 'index'),
	'workers/group/create' => array(':controller' => 'workers', ':action' => 'create_group'),
	'workers/group/edit/:id' => array(':controller' => 'workers', ':action' => 'edit_group'),
	'workers/group/delete/:id' => array(':controller' => 'workers', ':action' => 'delete_group'),
	
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
