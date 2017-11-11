<?php
	
	class Controller_Application extends Controller {
		
		public function __construct($action, $arguments) {
			if (isset($arguments['project_slug'])) {
				if ($project = $this->Project->setCurrent($arguments['project_slug'])) {
					$project['project_slug'] = $project['slug'];
					$this->View->assign('project', $project);
				} else {
					if (!$this->User->isAllowed('projects', 'index')) {
						return $this->View->redirect('user', 'login');
					} else {
						return $this->View->redirect('projects', 'index');;
					}
				}
			} else {
				$this->View->assign('project', array());
			}
			
			$this->View->assign('projects', $this->Project->findAllIndexedBySlug());
		}
		
	}
	
?>
