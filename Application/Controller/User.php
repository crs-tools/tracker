<?php
	
	requires(
		'/Controller/Application'
	);
	
	class Controller_User extends Controller_Application {
		
		public $requireAuthorization = true;
		
		public function login() {
			if (User::isLoggedIn()) {
				return $this->redirect();
			}
			
			$this->form();
			
			if ($this->form->wasSubmitted()) {
				if (User::login(
					$this->form->getValue('user'),
					$this->form->getValue('password'),
					$this->form->getValue('remember')
				)) {
					$this->flash('Login successful');
					
					
					if (isset($_SESSION['return_to'])) {
						$this->redirect($_SESSION['return_to']);
						unset($_SESSION['return_to']);
						
						return $this->Response;
					}
					
					return $this->redirect();
				} else {
					$this->flashNow('Login failed, check user and password', self::FLASH_WARNING);
				}
			}
			
			return $this->render('user/login');
		}
		
		public function logout() {
			if (User::isLoggedIn() and User::logout()) {
				$this->flash('Goodbye');
			}
			
			return $this->redirect();
		}
		
		public function settings() {
			$this->form();
			$user = User::getCurrent();
			
			if ($this->form->wasSubmitted()) {
				if (!$user->verifyPassword($this->form->getValue('current_password'))) {
					$this->flashNow('Wrong current password');
				// TODO: via validation!
				} elseif ($this->form->getValue('password') and
					$this->form->getValue('password') == $this->form->getValue('password_confirmation') and
					$user->save($this->form->getValues())) {
					$this->flashNow('Password changed successfully');
				}
			}
			
			return $this->render('user/settings');
		}
		
		
		public function index() {
			$this->users = User::findAll()->orderBy('name');
			return $this->render('user/index');
		}
		
		public function substitute(array $arguments) {
			if (!$user = User::find($arguments['id'])) {
				throw new EntryNotFoundException();
			}
			
			if (!AccessControl::isAllowed($user['role'], 'user', 'act_as_substitute')) {
				$this->flash('You are not allowed to login in the name of this user');
				return $this->redirect('user', 'index');
			}
			
			if (!$user->substitute()) {
				$this->flash('You cannot login in the name of this user');
				return $this->redirect('user', 'index');
			}
			
			$this->flash('You are now logged in as ' . User::getCurrent()['name']);
			return $this->redirect('projects', 'index');
		}
		
		public function changeback() {
			if (!User::isSubstitute()) {
				return $this->redirect();
			}
			
			if (!User::getCurrent()->changeback()) {
				$this->Flash('Failed to return to previous user.');
				return $this->redirect();
			}
			
			$this->flash('You are now logged in as ' . User::getCurrent()['name']);
			return $this->redirect('user', 'index');
		}
		
		public function create() {
			$this->form();
			
			if ($this->form->wasSubmitted() and User::create($this->form->getValues())) {
				$this->flash('Successfully added user');
				return $this->redirect('user', 'index');
			}
			
			return $this->render('user/edit');
		}
		
		public function edit(array $arguments) {
			if (!$this->user = User::find($arguments['id'])) {
				throw new EntryNotFoundException();
			}
			
			$this->form();
			
			if ($this->form->wasSubmitted()) {
				if ($this->form->getValue('password') and
					!User::getCurrent()->verifyPassword($this->form->getValue('user_password'))) {
					$this->flashNow('Your entered a wrong password');
				} elseif ($this->user->save($this->form->getValues())) {
					$this->flash('User ' . $this->user['name'] . ' updated');
					return $this->redirect('user', 'index');
				}
			}
			
			return $this->render('user/edit');
		}
		
		public function delete(array $arguments) {
			if (!$user = User::find($arguments['id'])) {
				throw new EntryNotFoundException();
			}
			
			$name = $user['name'];
			
			if ($user->isCurrent()) {
				$this->flash('You can\'t delete your own user account');
			} elseif ($user->destroy()) {
				$this->flash('User ' . $name . ' deleted');
			}
			
			return $this->redirect('user', 'index');
		}
	}
	
?>
