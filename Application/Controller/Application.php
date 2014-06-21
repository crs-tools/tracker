<?php
	
	requires(
		'Form',
		
		'/Model/User',
		'/Model/Project'
	);
	
	abstract class Controller_Application extends Controller {
		
		protected $beforeAction = [
			'addHeaders' => true,
			'setProject' => true
		];
		
		protected $catch = [
			'NotFound' => 'notFound',
			'ActionNotAllowed' => 'notAllowed'
		];
		
		protected $projectReadOnlyAccess = null;
		
		public function __construct() {
			User::recall();
		}
		
		protected function addHeaders() {
			$this->Response->addHeader(
				'Content-Security-Policy',
				'default-src \'self\'; font-src \'none\'; frame-src \'none\';' .
					'object-src \'none\'; style-src \'self\''
			);
			$this->Response->addHeader('X-Content-Type-Options', 'nosniff');
			$this->Response->addHeader('X-Frame-Options', 'DENY');
		}
		
		protected function setProject($action, array $arguments) {
			if (!isset($arguments['project_slug'])) {
				return;
			}
			
			if (empty($arguments['project_slug'])) {
				$this->keepFlash();
				return $this->redirect('projects', 'index');
			}
			
			$this->project = Project::findByOrThrow([
				'slug' => $arguments['project_slug']
			]);
			
			$this->project['project_slug'] = $this->project['slug'];
			
			if ($this->project['read_only'] and
				$this->projectReadOnlyAccess !== null and
				empty($this->projectReadOnlyAccess[$action])) {
				$this->flash('You can\'t alter tickets in this project because it\'s read only');
				return $this->redirect('tickets', 'index', $this->project);
			}
		}
		
		public function notFound() {
			return $this->render('404', ['responseCode' => 404]);
		}
		
		public function notAllowed() {
			if (!User::isLoggedIn()) {
				$_SESSION['return_to'] = $this->Request->getURI();
				
				$this->flash('You have to log in to view this page');
				return $this->redirect('user', 'login');
			}
			
			return $this->render('403', ['responseCode' => 403]);
		}
		
		// TODO: redirectWithReference($default, array('ref1' => […], 'ref2' => …))
		
	}
	
?>
