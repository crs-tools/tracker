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

		private static $externalUser = false;
		private static $externalUserHeader = '';
		private static $externalAuthInProgress = false;
		
		public $hasAndBelongsToMany = [
			'Project' => [
				'foreign_key' => ['project_id'],
				'self_key' => ['user_id'],
				'via' => 'tbl_user_project_restrictions',
				'select' => 'tbl_project.id'
			]
		];
		
		public $acceptNestedEntriesFor = [
			'Project' => true
		];
		
		public static function isLoggedIn() {
			if (parent::isLoggedIn())
				return true;

			if (static::$externalAuthInProgress)
				return false;

			$external = @$_SERVER[static::$externalUserHeader];
			if (static::$externalUserHeader == '' || $external == '')
				return false;

			// try auto-login by external mechanism
			static::$externalAuthInProgress = true;
			$result = parent::login($external, '', false);
			static::$externalAuthInProgress = false;
			return $result;
		}

		public static function isRestricted() {
			if (!parent::isLoggedIn()) {
				return false;
			}
			
			return static::$Session->get()['restrict_project_access'];
		}

		public static function setExternalUserHeader($headerName) {
			if (static::$externalUserHeader == '') {
				static::$externalUserHeader = $headerName;
			}
		}

		public function verifyPassword($password) {
			if (static::$externalUserHeader != ''
				&& $_SERVER[self::$externalUserHeader] === $this[static::FIELD_USER]) {
				$this->unsetRememberCookie();
				return static::$externalUser = true;
			}

			// allow fallback to database credentials even with external auth enabled
			return password_verify($password, $this[static::FIELD_PASSWORD]);
		}

		public function shouldRehashPassword() {
			return (static::$externalAuthInProgress)
				? false
				: password_needs_rehash($this[static::FIELD_PASSWORD], PASSWORD_DEFAULT);
		}
		
	}
	
?>
