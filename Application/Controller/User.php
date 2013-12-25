<?php
	
	requires(
		'/Controller/Application'
	);
	
	class Controller_User extends Controller_Application {
		
		public $requireAuthorization = true;
		
		public function login() {
			$this->form();
			if ($this->form->wasSubmitted()) {
				if (User::login($this->form->getValue('user'), $this->form->getValue('password'))) {
					$this->flash('Login successful');
					
					// TODO: return to last page
					return $this->redirect();
				} else {
					$this->flashNow('Login failed, check user and password', self::FLASH_WARNING);
				}
			}
			
			/*
			if ($this->User->isLoggedIn()) {
				return $this->View->redirect();
			}
			
			if ($referer = Request::getLocalReferer() and $referer != 'login') {
				$_SESSION['return_to'] = Request::getLocalReferer();
			}
			
			if (Request::isPostRequest()) {
				if ($this->User->login(Request::post('user'), Request::post('password'), Request::post('remember', Request::checkbox))) {
					if (User::isAllowed('user', 'login_complete')) {
						$this->flash('Login successful');

						if (isset($_SESSION['return_to'])) {
							$this->View->redirect($_SESSION['return_to']);
							unset($_SESSION['return_to']);

							return true;
						} else {						
							return $this->View->redirect();
						}
					} else {
						$this->User->logout();
						$this->flashNow('You are unable to login with this user');
					}
				} else {
					$this->flashNow('Login failed, check name and password', self::FLASH_WARNING);
				}
			}
			*/
			return $this->render('user/login.tpl');
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
			
			return $this->render('user/settings.tpl');
		}
		
		
		public function index() {
			$this->users = User::findAll()->orderBy('name');
			return $this->render('user/index.tpl');
		}
		/*
		public function substitute(array $arguments) {
			if (empty($arguments['id'])) {
				return $this->View->redirect('user', 'index');
			}
			
			if (!$this->User->substitute($arguments['id'])) {
				$this->flash('You cannot login in the name of this user');
				return $this->View->redirect('user', 'index');
			}
			
			$this->flash('You are now logged in as ' . $this->User->get('name'));
			return $this->View->redirect('projects', 'index');
		}
		
		public function changeback() {
			if (!$this->User->changeback()) {
				throw new ActionNotAllowedException();
			}
			
			// TODO: redirect back to last URL?
			if (Request::getReferer(true)) {
				return $this->View->redirect(Request::getReferer(true));
			} else {
				return $this->View->redirect('user', 'index');
			}
		}
		*/
		public function create() {
			$this->form();
			
			if ($this->form->wasSubmitted() and User::create($this->form->getValues())) {
				$this->flash('Successfully added user');
				return $this->redirect('user', 'index');
			}
			
			return $this->render('user/edit.tpl');
		}
		
		public function edit(array $arguments) {
			if (!$this->user = User::find($arguments['id'], array())) {
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
			
			return $this->render('user/edit.tpl');
		}
		
		public function delete(array $arguments) {
			if (!$user = User::find($arguments['id'], array())) {
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
