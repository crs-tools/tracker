<?php
	
	requires(
		'/Model/Project'
	);
	
	class Controller_Projects extends Controller_Application {
		
		public $requireAuthorization = true;
		
		public function index() {
			return $this->render('projects/index.tpl');
		}
		
		public function view() {
			return $this->render('projects/view.tpl');
		}
		
		public function create() {
			$this->form = $this->form();
			
			if ($this->form->wasSubmitted() and ($project = Project::create($this->form->getValues()))) {
				$this->flash('Project created');
				// TODO: redirect to project settings (view)?
				return $this->redirect('projects', 'index');
			}
			
			return $this->render('projects/edit.tpl');
		}
		
		public function edit(array $arguments = array()) {
			if (!$this->project = Project::find($arguments['id'])) {
				throw new EntryNotFoundException();
			}
			
			//'projects', 'edit', $project + ((Request::get('ref') == 'index')? array('?ref=index') : array()),
			$this->form = $this->form();
			
			if ($this->form->wasSubmitted() and $this->project->save($this->form->getValues())) {
				$this->flash('Project updated');
				
				// if (isset($_GET['ref']) and $_GET['ref'] == 'index') {						
					return $this->redirect('projects', 'index');
				// } else {
					// return $this->redirect('tickets', 'index', array('project_slug' => $this->project['slug']));
				// }
			}
			
			return $this->render('projects/edit.tpl');
		}
		
		public function delete(array $arguments = array()) {
			if (!$this->project = Project::find($arguments['id'])) {
				throw new EntryNotFoundException();
			}
			
			$this->form = $this->form();
			
			if ($this->form->wasSubmitted() and $this->project->destroy()) {
				$this->flash('Project deleted');
				return $this->redirect('projects', 'index');
			}
			
			return $this->render('projects/delete.tpl');
		}
		
	}
	
?>