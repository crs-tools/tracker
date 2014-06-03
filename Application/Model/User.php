<?php
	
	requires(
		'Model/Authentication/User',
		'/Model/Worker'
	);
	
	class User extends Model_Authentication_User {
		
		const TABLE = 'tbl_user';
		
		const FIELD_USER = 'name';
		const FIELD_ACL_TOKEN = 'role';
		const FIELD_PERSISTENCE_TOKEN = 'persistence_token';
		const FIELD_LAST_REQUEST = 'last_seen';
		const FIELD_LAST_LOGIN = 'last_login';
		
		/*public $hasMany = array(
			'ServiceLogEntry' => array('key' => 'user_id'),
			'LogEntry' => array('key' => 'user_id'),
			'Ticket' => array('key' => 'user_id')
		);
		*/
	}
	
?>