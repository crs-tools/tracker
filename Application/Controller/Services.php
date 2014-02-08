<?php
	
	class Controller_Services extends Controller_Application {
		
		public $requireAuth = true;
		
		public function hold() {
			if (Request::isPostRequest()) {
				$this->Config->RPC['hold_services'] = true;
				return $this->View->redirect('services', 'workers', array('project_slug' => $this->Project->slug));
			}
			
			$this->View->render('services/hold');
		}
		
		public function resume() {
			$this->Config->RPC['hold_services'] = false;
			return $this->View->redirect('services', 'workers', array('project_slug' => $this->Project->slug));
		}
	}
	
?>
