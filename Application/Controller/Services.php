<?php
	
	class Controller_Services extends Controller_Application {
		
		public $requireAuth = true;
		
		public function workers() {
			if (Request::get('t')) {
				$this->View->assign('service', $this->State->getService(Request::get('t')));
			}
			
			$this->View->assign('workers', $this->User->findAll(array('Ticket' => array('limit' => 1)/*, 'ServiceLogEntry'*/), 'role = ? AND last_seen IS NOT NULL AND AGE(last_seen) < ?', array('worker', '1 week'), 'name'));
			$this->View->render('services/workers.tpl');
		}
		
		public function hold() {
			if (Request::isPostRequest()) {
				$this->Config->RPC['hold_services'] = true;
				return $this->View->redirect('services', 'workers', array('project_slug' => $this->Project->slug));
			}
			
			$this->View->render('services/hold.tpl');
		}
		
		public function resume() {
			$this->Config->RPC['hold_services'] = false;
			return $this->View->redirect('services', 'workers', array('project_slug' => $this->Project->slug));
		}
	}
	
?>
