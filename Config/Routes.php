<?php return [
	'rpc' => ['XMLRPC_Handler', 'default'],
	
	':project_slug/tickets' => ['tickets', 'index'],
	':project_slug/tickets/search' => ['tickets', 'search'],
	
	':project_slug/ticket/create' => ['tickets', 'create'],
	
	':project_slug/ticket/:id/comment' => ['tickets', 'comment'],
	':project_slug/ticket/:ticket_id/comment/delete/:id' => ['tickets', 'delete_comment'],

	':project_slug/ticket/:id/log/:entry' => ['tickets', 'log'],
	':project_slug/ticket/:id' => ['tickets', 'view'],
	':project_slug/ticket/:id/:action' => ['tickets'],

	':project_slug/tickets/import' => ['import', 'index'],
	':project_slug/tickets/import/rooms' => ['import', 'rooms'],
	':project_slug/tickets/import/review' => ['import', 'review'],
	':project_slug/tickets/import/apply' => ['import', 'apply'],
	
	':project_slug/workers' => ['workers', 'project'],
	':project_slug/services/hold' => ['services', 'hold'],
	':project_slug/services/resume' => ['services', 'resume'],
	
	':project_slug/settings' => ['projects', 'settings'],
	':project_slug/settings/properties' => ['projects', 'properties'],
	':project_slug/settings/encoding/profiles' => ['projects', 'profiles'],
	':project_slug/settings/states' => ['projects', 'states'],
	':project_slug/settings/worker' => ['projects', 'worker'],
	
	'encoding/profiles' => ['encodingprofiles', 'index'],
	'encoding/profile/create' => ['encodingprofiles', 'create'],
	'encoding/profile/:id/versions/compare' => ['encodingprofiles', 'compare'],
	'encoding/profile/:id/versions' => ['encodingprofiles', 'view'],
	'encoding/profile/:id/edit' => ['encodingprofiles', 'edit'],
	'encoding/profile/:id/delete' => ['encodingprofiles', 'delete'],
	
	'workers' => ['workers', 'index'],
	'workers/group/create' => ['workers', 'create_group'],
	'workers/group/:id/edit' => ['workers', 'edit_group'],
	'workers/group/:id/delete' => ['workers', 'delete_group'],
	'workers/group/:id/queue' => ['workers', 'queue'],
	
	'users' => ['user', 'index'],
	'user/create' => ['user', 'create'],
	'user/:id/edit' => ['user', 'edit'],
	'user/:id/delete' => ['user', 'delete'],

	'user/switch/:id' => ['user', 'substitute'],
	'user/exit' => ['user', 'changeback'],
	
	'project/create' => ['projects', 'create'],
	':project_slug/settings/general' => ['projects', 'edit'],
	':project_slug/duplicate' => ['projects', 'duplicate'],
	':project_slug/delete' => ['projects', 'delete'],
	'projects' => ['projects', 'index'],
	
	'api/v1/:project_slug/tickets/fahrplan' => ['API', 'tickets_fahrplan'],
	
	'login' => ['user', 'login'],
	'logout' => ['user', 'logout'],
	'settings' => ['user', 'settings'],
	
	':project_slug?' => ['tickets', 'feed'],
]; ?>
