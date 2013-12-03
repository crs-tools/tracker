<?php
	
	requires(
		'/Model/EncodingProfile'
	);
	
	class Controller_EncodingProfiles extends Controller_Application {
		
		public $requireAuthorization = true;
		
		public function index() {
			$this->profiles = EncodingProfile::findAll(array());
			return $this->render('encoding/profiles.tpl');
		}
		
		public function create() {
			$this->form = $this->form();
			
			//$this->flash('Encoding profile created');
			
			return $this->render('encoding/profiles/edit.tpl');
		}
		
		public function edit(array $arguments) {
			
			//$this->flash('Encoding profile updated');
			
			return $this->render('encoding/profiles/edit.tpl');
		}
		
		public function delete(array $arguments) {
			/*
			if (!empty($arguments) and $this->EncodingProfile->delete($arguments['id'], array('project_id' => $this->Project->id))) {
				$this->flash('Encoding profile deleted');
			}
 			
			return $this->View->redirect('projects', 'view', array('project_slug' => $this->Project->slug));
			*/
		}
		
	}
	
?>
