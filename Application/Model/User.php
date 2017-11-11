<?php
	
	class User extends Model_Auth_User {
		
		public $table = 'tbl_user';
		
		public $loginField = 'name';
		public $loginIsEMail = false;
		
		public $accessControlTokenField = 'role';
		public $persistenceTokenField = 'hash';
		
		public $lastRequestField = 'last_seen';
		
		public $passwordHash = 'md5';
		public $temporaryUserSessionModel = 'Model_Auth_Session_Temporary';
		
		public $hasMany = array(
			'ServiceLogEntry' => array('key' => 'user_id'),
			'LogEntry' => array('key' => 'user_id'),
			'Ticket' => array('key' => 'user_id')
		);
		
		public function auth($hash) {
			if ($this->isLoggedIn()) {
				if (!$this->logout()) {
					return false;
				}
			}
			
			if (!$this->findFirst(array(), $this->persistenceTokenField .' = ?', array($hash))) {
				$this->invalidateField($this->persistenceTokenField, 'user not found', 'login');
				$this->clear();
				
				return false;
			}
			
			$this->{$this->lastLoginIPField} = Request::getIP();
			$this->{$this->lastLoginField} = Date::now();			
			$this->updateLastRequest(false);
			
			$this->save();
			
			// set stateless session model
			$this->userSessionModel = $this->temporaryUserSessionModel;
			
			// start session
			$this->UserSession = new $this->userSessionModel;
			$this->updateSession();
			
			return true;
		}
		
		public function substitute($id) {
			if (!$this->isLoggedIn()) {
				return false;
			}
			
			$parent = $this->current()->get('id');
			
			// TODO: allow this only for users, not scripts?
			if (!$this->find($id, array())) {
				return false;
			}
			
			if (!$this->Acl->isAllowed($this->get('role'), 'user', 'act_as_substitute')) {
				$this->current();
				return false;
			}
			
			$this->_data['original_user'] = $parent;
			
			$this->UserSession = new $this->userSessionModel;
			$this->updateSession();
			
			return true;
		}
		
		public function changeback() {
			if (!$parent = $this->UserSession->get('original_user')) {
				return false;
			}
			
			if (!$this->find($parent, array())) {
				// original user got lost in translation, we are locked in
				unset($this->_data['original_user']);
				$this->updateSession();
				
				return false;
			}
			
			$this->UserSession = new $this->userSessionModel;
			$this->updateSession();
			
			return true;
		}
		
		public function isSubstitute() {
			return (bool) $this->UserSession->get('original_user');
		}
		
	}
	
?>