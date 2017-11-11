<?php
	
	class Controller_EncodingProfiles extends Controller_Application {
		
		public $requireAuth = true;
		
		public function index() {
			$this->View->assign('profiles', $this->EncodingProfile->findAll(array(), array('project_id' => $this->Project->id)));
			$this->View->render('encoders/profiles.tpl');
		}
		
		public function create() {
			if (Request::isPostRequest()) {
				$this->EncodingProfile->project_id = $this->Project->id;
				
				$this->EncodingProfile->name = Request::post('name');
				$this->EncodingProfile->slug = Request::post('slug');
				$this->EncodingProfile->priority = Request::post('priority', Request::float);
				$this->EncodingProfile->approved = Request::post('approved', Request::checkbox);
				
				$this->EncodingProfile->extension = Request::post('extension');
				$this->EncodingProfile->mirror_folder = Request::post('mirror_folder');
				
				$this->EncodingProfile->xml_template = Request::post('xml_template', true);
				
				if ($this->EncodingProfile->save()) {
					$this->View->flash('Encoding profile created');
					return $this->View->redirect('projects', 'view', array('project_slug' => $this->Project->slug));
				}
			}
			
			$this->View->render('encoders/profiles/edit.tpl');
		}
		
		public function edit(array $arguments = array()) {
			if (empty($arguments) or !$profile = $this->EncodingProfile->find($arguments['id'], array('project_id' => $this->Project->id))) {
				throw new EntryNotFoundException();
			}
			
			if (Request::isPostRequest()) {
				$this->EncodingProfile->name = Request::post('name');
				$this->EncodingProfile->slug = Request::post('slug');
				$this->EncodingProfile->priority = Request::post('priority', Request::float);
				$this->EncodingProfile->approved = Request::post('approved', Request::checkbox);
				
				$this->EncodingProfile->extension = Request::post('extension');
				$this->EncodingProfile->mirror_folder = Request::post('mirror_folder');
				
				$this->EncodingProfile->xml_template = Request::post('xml_template', true);
				
				if ($this->EncodingProfile->save()) {
					$this->View->flash('Encoding profile updated');
					return $this->View->redirect('projects', 'view', array('project_slug' => $this->Project->slug));
				}
			}
			
			$this->View->assign('profile', $profile);
			$this->View->render('encoders/profiles/edit.tpl');
		}
		
		public function delete(array $arguments = array()) {
			if (!empty($arguments) and $this->EncodingProfile->delete($arguments['id'], array('project_id' => $this->Project->id))) {
				$this->View->flash('Encoding profile deleted');
			}
 			
			return $this->View->redirect('projects', 'view', array('project_slug' => $this->Project->slug));
		}
		
	}
	
?>
