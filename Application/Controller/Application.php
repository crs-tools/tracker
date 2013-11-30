<?php
	
	requires(
		'/Model/User',
		'/Model/Project'
	);
	
	abstract class Controller_Application extends Controller {
		
		protected $beforeAction = array('setProject' => true);
		
		public function __construct() {
			User::recall();
		}
		
		protected function setProject($action, array $arguments) {
			if (isset($arguments['project_slug'])) {
				$this->project = Project::findBy(array('slug' => $arguments['project_slug']));
				$this->project['project_slug'] = $this->project['slug'];
				
				if (!$this->project) {
					return $this->redirect('projects', 'index');
				}
			}
			
			$this->projects = Project::findAll()->indexBy('slug');
		}
		
		// TODO: redirectWithReference($default, array('ref1' => […], 'ref2' => …))
		
	}
	
?>
