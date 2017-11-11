<?php
	
	class RoutesConfig extends ConfigSettings {
		
		public function __construct() {
			// RPC Routes
			Router::addRoute('rpc/:uid/:hostname', array(':controller' => 'XMLRPC_Handler', ':action' => 'default'));
			Router::addRoute('rpc/:uid/:hostname/:project_slug', array(':controller' => 'XMLRPC_ProjectHandler', ':action' => 'default'));
			
			// Webapp Routes
			Router::addRoute(':project_slug/dashboard', array(':controller' => 'dashboard', ':action' => 'index'));
			Router::addRoute(':project_slug/dashboard/:action', array(':controller' => 'dashboard', ':type' => 'application/json'));
			
			Router::addRoute(':project_slug/tickets', array(':controller' => 'tickets', ':action' => 'index'));
			
			Router::addRoute(':project_slug/ticket/create', array(':controller' => 'tickets', ':action' => 'create'));
			
			Router::addRoute(':project_slug/ticket/:id/comment', array(':controller' => 'tickets', ':action' => 'comment'));
			Router::addRoute(':project_slug/ticket/:ticket_id/comment/delete/:id', array(':controller' => 'tickets', ':action' => 'delete_comment'));
			
			Router::addRoute(':project_slug/ticket/:id/log/:entry', array(':controller' => 'tickets', ':action' => 'log', ':type' => 'text/plain'));
			Router::addRoute(':project_slug/ticket/:id', array(':controller' => 'tickets', ':action' => 'view'));
			Router::addRoute(':project_slug/ticket/:action/:id', array(':controller' => 'tickets'));
			
			Router::addRoute(':project_slug/tickets/import', array(':controller' => 'import', ':action' => 'index'));
			Router::addRoute(':project_slug/tickets/import/review', array(':controller' => 'import', ':action' => 'review'));
			Router::addRoute(':project_slug/tickets/import/apply', array(':controller' => 'import', ':action' => 'apply'));
			Router::addRoute(':project_slug/tickets/export', array(':controller' => 'export', ':action' => 'index'));
			Router::addRoute(':project_slug/tickets/export/wiki', array(':controller' => 'export', ':action' => 'wiki'));
			Router::addRoute(':project_slug/tickets/export/podcast/:profile_slug', array(':controller' => 'export', ':action' => 'podcast'));
			Router::addRoute(':project_slug/tickets/export/feedback', array(':controller' => 'export', ':action' => 'feedback'));
			
			Router::addRoute(':project_slug/encoding/profiles', array(':controller' => 'encodingprofiles', ':action' => 'index'));
			Router::addRoute(':project_slug/encoding/profile/create', array(':controller' => 'encodingprofiles', ':action' => 'create'));
			Router::addRoute(':project_slug/encoding/profile/import', array(':controller' => 'encodingprofiles', ':action' => 'import'));
			Router::addRoute(':project_slug/encoding/profile/edit/:id', array(':controller' => 'encodingprofiles', ':action' => 'edit'));
			Router::addRoute(':project_slug/encoding/profile/delete/:id', array(':controller' => 'encodingprofiles', ':action' => 'delete'));
			
			Router::addRoute(':project_slug/services/worker/:id/halt', array(':controller' => 'services', ':action' => 'halt'));
			Router::addRoute(':project_slug/services/worker/:id/command', array(':controller' => 'services', ':action' => 'command'));
			
			Router::addRoute(':project_slug/services/workers', array(':controller' => 'services', ':action' => 'workers'));
			Router::addRoute(':project_slug/services/hold', array(':controller' => 'services', ':action' => 'hold'));
			Router::addRoute(':project_slug/services/resume', array(':controller' => 'services', ':action' => 'resume'));
			
			Router::addRoute(':project_slug/settings', array(':controller' => 'projects', ':action' => 'view'));
			
			Router::addRoute('users', array(':controller' => 'user', ':action' => 'index'));
			Router::addRoute('user/create', array(':controller' => 'user', ':action' => 'create'));
			Router::addRoute('user/edit/:id', array(':controller' => 'user', ':action' => 'edit'));
			Router::addRoute('user/delete/:id', array(':controller' => 'user', ':action' => 'delete'));
			
			Router::addRoute('user/switch/:id', array(':controller' => 'user', ':action' => 'substitute'));
			Router::addRoute('user/exit', array(':controller' => 'user', ':action' => 'changeback'));
			
			Router::addRoute('project/create', array(':controller' => 'projects', ':action' => 'create'));
			Router::addRoute('project/edit/:id', array(':controller' => 'projects', ':action' => 'edit'));
			Router::addRoute('project/delete/:id', array(':controller' => 'projects', ':action' => 'delete'));
			Router::addRoute('projects', array(':controller' => 'projects', ':action' => 'index'));
			
			Router::addRoute('login', array(':controller' => 'user', ':action' => 'login'));
			Router::addRoute('logout', array(':controller' => 'user', ':action' => 'logout'));
			Router::addRoute('settings', array(':controller' => 'user', ':action' => 'settings'));
			
			Router::addRoute(':project_slug', array(':controller' => 'tickets', ':action' => 'feed'));
		}
		
	}
	
?>
