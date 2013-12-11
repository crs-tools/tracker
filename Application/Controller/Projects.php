<?php
	
	requires(
		'/Model/Project',
		'/Model/EncodingProfile',
		'/Helper/EncodingProfile'
	);
	
	class Controller_Projects extends Controller_Application {
		
		public $requireAuthorization = true;
		
		public function index() {
			return $this->render('projects/index.tpl');
		}
		
		public function view() {
			$this->profilesForm = $this->form();
			
			if ($this->profilesForm->wasSubmitted() and $this->project->save($this->profilesForm->getValues())) {
				$this->flashNow('Updated encoding profiles');
			}
			
			$this->versions = $this->project
				->EncodingProfileVersion
				->join(['EncodingProfile'])
				->orderBy(EncodingProfile::TABLE . '.name');
			$this->versions->fetch();
			
			$this->versionsLeft = EncodingProfileVersion::findAll(array(
				'EncodingProfile' => array(
					'select' => 'name'
				)
			))
				->select('id, encoding_profile_id, revision, created, description')
				->orderBy('encoding_profile_id, revision DESC'); // TODO: order by encoding_profile_name
			
			$versions = $this->versions->pluck('encoding_profile_id');
			
			if (!empty($versions)) {
				$this->versionsLeft->whereNot([
					'encoding_profile_id' => $versions
				]);
			}
			
			$this->versionsLeft->fetch();
			
			$this->properties = $this->project->Properties;
			return $this->render('projects/view.tpl');
		}
		
		public function create() {
			$this->form();
			
			if ($this->form->wasSubmitted() and ($project = Project::create($this->form->getValues()))) {
				$this->flash('Project created');
				return $this->redirect('projects', 'view', $project);
			}
			
			return $this->render('projects/edit.tpl');
		}
		
		public function edit(array $arguments = array()) {
			$this->form();
			
			if ($this->form->wasSubmitted() and $this->project->save($this->form->getValues())) {
				$this->flash('Project updated');
				return $this->redirect('projects', 'view', $this->project);
			}
			
			return $this->render('projects/edit.tpl');
		}
		
		public function delete(array $arguments = array()) {
			$this->form();
			
			if ($this->form->wasSubmitted() and $this->project->destroy()) {
				$this->flash('Project deleted');
				return $this->redirect('projects', 'index');
			}
			
			return $this->render('projects/delete.tpl');
		}
		
	}
	
?>