<?php
	
	requires(
		'Model/Authentication/User',
		'Model/Authentication/Substitution',
		'/Model/Worker'
	);
	
	class User extends Model_Authentication_User {
		
		use Model_Authentication_Substitution;
		
		const TABLE = 'tbl_user';
		
		const FIELD_USER = 'name';
		const FIELD_ACL_TOKEN = 'role';
		
		const FIELD_PERSISTENCE_TOKEN = 'persistence_token';
		const FIELD_LAST_REQUEST = 'last_seen';
		const FIELD_LAST_LOGIN = 'last_login';
		
		const FIELD_ACTIVE = false;
		const FIELD_LOGIN_COUNT = false;
		
	}
	
?>