<?php
	
	requires(
		'/Controller/Application'
	);
	
	class Controller_User extends Controller_Application {
		
		// public $requireAuth = true;
		
		public function login() {
			$this->form = $this->form();
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
						$this->View->flash('Login successful');

						if (isset($_SESSION['return_to'])) {
							$this->View->redirect($_SESSION['return_to']);
							unset($_SESSION['return_to']);

							return true;
						} else {						
							return $this->View->redirect();
						}
					} else {
						$this->User->logout();
						$this->View->flashNow('You are unable to login with this user');
					}
				} else {
					$this->View->flashNow('Login failed, check name and password', View::flashWarning);
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
			$this->form = $this->form();
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
		
		/*
		public function index() {
			// $this->View->assign('users', $this->User->findAll(array(), '', array('admin', 'user'), 'human, role, name', null, '*, role = ? OR role = ? AS human'));
			$this->View->assign('users', $this->User->findAll(array(), 'role != ?', array('worker'), 'name'));
			$this->View->assign('workers', $this->User->findAll(array(), array('role' => 'worker'), array(), 'name'));
			$this->View->render('user/index.tpl');
		}
		
		public function substitute(array $arguments = array()) {
			if (empty($arguments['id'])) {
				return $this->View->redirect('user', 'index');
			}
			
			if (!$this->User->substitute($arguments['id'])) {
				$this->View->flash('You cannot login in the name of this user');
				return $this->View->redirect('user', 'index');
			}
			
			$this->View->flash('You are now logged in as ' . $this->User->get('name'));
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
		
		public function create() {
			if (Request::isPostRequest() and $this->User->create(Request::getParams())) {
				$this->View->flash('Successfully added user');
				return $this->View->redirect('user', 'index');
			}
			
			$this->View->render('user/edit.tpl');
		}
		
		public function edit(array $arguments = array()) {
			if (Request::isPostRequest() and Request::post('verify_password')) {
				$passwordValid = $this->User->current()->checkPassword(Request::post('verify_password'));
			}
						
			if (empty($arguments) or !$user = $this->User->find($arguments['id'], array())) {
				throw new EntryNotFoundException();
			}
			
			if (Request::isPostRequest()) {
				if (Request::post('password') and !$passwordValid) {
					$this->View->flashNow('Your password is wrong', View::flashWarning);
				} else {
					if ($this->User->save(Request::getParams())) {
						$this->View->flash('User updated');
						return $this->View->redirect('user', 'index');
					}
				}
			}
			
			$this->View->assign('user', $user);
			$this->View->render('user/edit.tpl');
		}
		
		public function delete(array $arguments = array()) {
			if (!empty($arguments)) {
				if ($arguments['id'] != $this->User->get('id')) {
					if ($this->User->delete($arguments['id'])) {
						$this->View->flash('User deleted');
					}
				} else {
					$this->View->flash('You can\'t delete your own user account', View::flashWarning);
				}
			}
			
			$this->View->redirect('user', 'index');
		}
		*/
	}
	
?>
