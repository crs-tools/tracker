<?php
	
	class Controller_Projects extends Controller_Application {
		
		public $requireAuth = true;
		
		public function index() {
			$this->View->render('projects/index.tpl');
		}
		
		public function view() {
			$this->View->assign('properties', Model::groupByField($this->ProjectProperties->findByObjectWithRoot($this->Project->id), 'root'));
			$this->View->assign('profiles', $this->EncodingProfile->findAll(array(), array('project_id' => $this->Project->id)));
			$this->View->render('projects/view.tpl');
		}
		
		public function create() {
			if (Request::isPostRequest() and $this->Project->create(Request::getParams())) {
				$this->ProjectProperties->update($this->Project->id, Request::post('property_name'), Request::post('property_value'));
									
				$this->View->flash('Project created');
				return $this->View->redirect('projects', 'index');
			}
			
			$this->View->render('projects/edit.tpl');
		}
		
		public function edit(array $arguments = array()) {
			if (empty($arguments) or !$project = $this->Project->find($arguments['id'], array())) {
				throw new EntryNotFoundException();
			}
			
			if (Request::isPostRequest() and $this->Project->save(Request::getParams())) {
				$this->ProjectLanguages->update($project['id'], Request::post('language_name'), Request::post('language_value'), Request::post('languages'));
				$this->ProjectProperties->update($project['id'], Request::post('property_name'), Request::post('property_value'), Request::post('properties'));
				
				$this->View->flash('Project updated');
				
				if (Request::get('ref') == 'index') {						
					return $this->View->redirect('projects', 'index');
				} else {
					return $this->View->redirect('tickets', 'index', array('project_slug' => $this->Project->slug));
				}
			}
			
			$this->View->assign('project', $project);
			$this->View->assign('languages', $this->ProjectLanguages->findByObject($project['id']));
			$this->View->assign('properties', $this->ProjectProperties->findByObject($project['id']));
			$this->View->render('projects/edit.tpl');
		}
		
		public function delete(array $arguments = array()) {
			if (empty($arguments) or !$project = $this->Project->find($arguments['id'], array())) {
				throw new EntryNotFoundException();
			}
			
			if (Request::isPostRequest()) {
				if ($this->Project->delete($project['id'])) {
					$this->View->flash('Project deleted');
					return $this->View->redirect('projects', 'index');
				}
			}
			
			$this->View->assign('project', $project);
			$this->View->render('projects/delete.tpl');
		}
		
	}
	
?>
