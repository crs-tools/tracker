<?php
	
	requires(
		'String',
		
		'/Model/Project',
		
		'/Model/EncodingProfile',
		'/Helper/EncodingProfile',
		
		'/Model/TicketState',
		'/Model/ProjectTicketState',
		
		'/Model/WorkerGroup'
	);
	
	class Controller_Projects extends Controller_Application {
		
		public $requireAuthorization = true;
		
		public function index() {			
			$this->projects = Project::findAll();
			return $this->render('projects/index');
		}
		
		public function view() {
			// Properties
			$this->properties = $this->project->Properties;
			
			// Encoding Profiles
			$this->profilesForm = $this->form();
			
			if ($this->profilesForm->wasSubmitted() and $this->project->save($this->profilesForm->getValues())) {
				$this->flashNow('Updated encoding profiles');
			}
			
			$this->versions = $this->project
				->EncodingProfileVersion
				->join(['EncodingProfile'])
				->orderBy(EncodingProfile::TABLE . '.name');
			$this->versions->fetchAll();
			
			$this->versionsLeft = EncodingProfileVersion::findAll()
				->join([
					'EncodingProfile' => [
						'select' => 'name'
					]
				])
				->select('id, encoding_profile_id, revision, created, description')
				->orderBy('encoding_profile_id, revision DESC'); // TODO: order by encoding_profile_name
			
			$versions = $this->versions->pluck('encoding_profile_id');
			
			if (!empty($versions)) {
				$this->versionsLeft->whereNot([
					'encoding_profile_id' => $versions
				]);
			}
			
			// States
			$this->stateForm = $this->form();
			
			$this->states = TicketState::findAll()
				->join(['ProjectTicketState' => [
					'where' => ['project_id' => $this->project['id']]
				]])
				->select('ticket_type, ticket_state, service_executable')
				->orderBy('ticket_type, sort');

			if ($this->stateForm->wasSubmitted() and $this->project->save($this->stateForm->getValues())) {
				// TODO: move to Model?
				Cache::invalidateNamespace('project.' . $this->project['id'] . '.states');
				$this->flashNow('Updated enabled states');
			}
			
			// Worker Groups
			$this->workerGroupForm = $this->form();
			
			if ($this->workerGroupForm->wasSubmitted() and $this->project->save($this->workerGroupForm->getValues())) {
				$this->flashNow('Updated project worker group assignment');
			}
			
			$this->workerGroups = WorkerGroup::findAll()
				->select('id, title');
			$this->workerGroupAssignment = $this->project
				->WorkerGroup
				->select(WorkerGroup::TABLE . '.id')
				->indexBy('id')
				->toArray();
			
			return $this->render('projects/view');
		}
		
		public function create() {
			$this->form();
			
			if ($this->form->wasSubmitted() and ($project = Project::create($this->form->getValues()))) {
				ProjectTicketState::createAll($project['id']);
				
				$this->flash('Project created');
				return $this->redirect('projects', 'view', $project);
			}
			
			return $this->render('projects/edit');
		}
		
		// duplicate a project with all its associated informations but without the tickets
		public function duplicate() {
			// create an array with all information of the selected project
			$project = $this->project->toArray();

			// unset the old project id -- this will force a creation instead of a save
			unset($project['id']);

			// copy languages
			$project['Languages'] = $this->project->Languages->toArray();

			// copy encoder-profile, ticket-state and worker-group association
			$project['EncodingProfileVersion'] = $this
				->project
				->EncodingProfileVersion
				->except(['fields'])
				->select('id AS encoding_profile_version_id')
				->toArray();

			$project['States'] = $this
				->project
				->States
				->select('ticket_type, ticket_state, service_executable')
				->toArray();

			$project['WorkerGroup'] = $this
				->project
				->WorkerGroup
				->except(['fields'])
				->select('id AS worker_group_id')
				->toArray();

			// read project slug
			$slug = $this->project['slug'];

			// select a duplication-counter (avoids dulicate-key-errors when pressing the clone-button twice without renaming first)
			for ($try = 0; $try < 100; $try++) {
				// construct the new slug
				$newSlug = 'duplicate-'.($try ? "$try-" : '').'of-'.$slug;

				// try to find a project with this slug
				$existingProject = Project::findAll()->where(['slug' => $newSlug])->exists();

				// nothing found? we'll use that.
				if(!$existingProject)
					break;
			}

			// warn of too many duplicates
			if($existingProject)
			{
				$this->flashNow("You can't have more then $try duplicates of a project. Rename one of them first.");
				return $this->index();
			}

			// modify project title & slug
			$project['title'] = 'Duplicate '.($try ? "$try " : '').'of '.$this->project['title'];
			$project['slug'] = $newSlug;
			$project['project_slug'] = $newSlug;

			// copy properties with modified Meta.Acronym
			$properties = $this->project->Properties->indexBy('name')->toArray();
			if(isset($properties['Meta.Acronym']))
			{
				$properties['Meta.Acronym'] = [
					'name' => 'Meta.Acronym',
					'value' => 'duplicate-'.($try ? "$try-" : '').'of-'.$properties['Meta.Acronym']['value']
				];
			}
			$project['properties'] = $properties;

			// create that new project
			if ($newProject = Project::create($project)) {
				$this->flashNow('Project duplicated');
			}

			// assign for editing
			$this->project = $newProject;

			// open post-clone edit-form
			$this->form();
			return $this->render('projects/edit.tpl');
		}

		public function edit(array $arguments) {
			$this->form();
			
			if ($this->form->wasSubmitted() and $this->project->save($this->form->getValues())) {
				$this->flash('Project updated');
				return $this->redirect('projects', 'view', $this->project);
			}
			
			return $this->render('projects/edit');
		}
		
		public function delete(array $arguments) {
			$this->form();
			
			if ($this->form->wasSubmitted() and $this->project->destroy()) {
				$this->flash('Project deleted');
				return $this->redirect('projects', 'index');
			}
			
			return $this->render('projects/delete');
		}
		
	}
	
?>