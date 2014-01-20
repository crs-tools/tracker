<?php
	
	requires(
		'/Model/User',
		'/Model/Project'
	);
	
	abstract class Controller_Application extends Controller {
		
		protected $beforeAction = ['setProject' => true];
		
		protected $catch = [
			'NotFound' => 'notFound',
			'ActionNotAllowed' => 'notAllowed'
		];
		
		public function __construct() {
			User::recall();
		}
		
		protected function setProject($action, array $arguments) {
			if (!isset($arguments['project_slug'])) {
				return;
			}
			
			$this->project = Project::findBy(array('slug' => $arguments['project_slug']));
			
			if (!$this->project) {
				return $this->redirect('projects', 'index');
			} else {
				$this->project['project_slug'] = $this->project['slug'];
			}
		}
		
		public function notFound() {
			$this->Response->setCode(404);
			return $this->render('404.tpl');
		}
		
		public function notAllowed() {
			if (!User::isLoggedIn()) {
				$this->flash('You have to login to view this page');
				return $this->redirect('user', 'login');
			}
			
			$this->Response->setCode(403);
			return $this->render('403.tpl');
		}
		
		// TODO: redirectWithReference($default, array('ref1' => […], 'ref2' => …))
		
	}
	
?>
