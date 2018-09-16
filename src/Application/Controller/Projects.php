<?php
	
	requires(
		'String',
		
		'/Model/Project',
		'/Model/Ticket',
		
		'/Model/EncodingProfile',
		'/Helper/EncodingProfile',
		
		'/Model/TicketState',
		'/Model/ProjectTicketState',
		
		'/Model/WorkerGroup',
		'/Model/ProjectWorkerGroupFilter',
		
		'/Helper/Time'
	);
	
	class Controller_Projects extends Controller_Application {
		
		public $requireAuthorization = true;
		
		public function index() {
			$this->projects = Project::findAll()
				->orderBy('read_only, created DESC');
			
			if (User::isRestricted()) {
				$this->projects->scoped(['filter_restricted' => [User::getCurrent()['id']]]);
			}
			
			return $this->render('projects/index');
		}
		
		public function settings() {
			$this->properties = $this->project->Properties;
			
			$this->stats = [
				'count' => Ticket::findAll()
					->select('ticket_state, COUNT(*) AS row_count')
					->where([
						'project_id' => $this->project['id'],
						'ticket_type' => 'meta'
					])
					->groupBy('ticket_state')
					->indexBy('ticket_state', 'row_count'),
				'duration' => Ticket::getRecordingDurationByProject(
					$this->project['id']
				)
			];
			$this->encodingProfileCount = $this->project
				->EncodingProfileVersion
				->count();
			
			return $this->render('projects/settings');
		}
		
		public function properties() {
			$this->form();
			
			if ($this->form->wasSubmitted() and
				$this->project->save($this->form->getValues())) {
				$this->flash('Properties updated');
				return $this->redirect('projects', 'properties', $this->project);
			}
			
			$this->properties = $this->project->Properties;
			return $this->render('projects/settings/properties');
		}
		
		public function profiles() {
			// Encoding Profiles
			$this->profilesForm = $this->form();
			
			if ($this->profilesForm->wasSubmitted()) {
				$values = $this->profilesForm->getValues();
				
				if (!empty($values['remove'])) {
					$this->project->removeEncodingProfileVersion(
						$values['remove']
					);
				} elseif (!empty($values['add']) and
					is_array($values['add']) and
					!empty($values['add']['encoding_profile_version_id'])) {
					$this->project->save([
						'EncodingProfileVersion' => [
							$values['add']
						]
					]);
					
					Ticket::createMissingEncodingTickets(
						$this->project['id']
					);
				} elseif (!empty($values['versions']) and
					is_array($values['versions'])) {
					foreach ($values['versions'] as $version) {
						if (empty($version[0]) or empty($version[1]) or
							$version[0] === $version[1]) {
							continue;
						}
						
						$this->project->updateEncodingProfileVersion(
							$version[0],
							$version[1]
						);
					}
				}
				
				if (!empty($values['priority'])) {
					foreach ($values['priority'] as $id => $priority) {
						$this->project->updateEncodingProfilePriority(
							$id,
							$priority
						);
					}
				}
				
				if (!empty($values['auto_create']) and
					is_array($values['auto_create'])) {
					foreach ($values['auto_create'] as $id => $autoCreate) {
						if (empty($autoCreate['1']) && !$autoCreate['0'] or
							$autoCreate['1'] === $autoCreate['0']) {
							continue;
						}
						
						$this->project->updateEncodingProfileAutoCreate(
							$id,
							$autoCreate['1']
						);
					}
					
					Ticket::createMissingEncodingTickets(
						$this->project['id']
					);
				}
			}
			
			$this->versions = $this->project
				->EncodingProfileVersion
				->join(['EncodingProfile'])
				->orderBy(EncodingProfile::TABLE . '.name')
				->load();
			
			$this->versionsLeft = EncodingProfileVersion::findAll()
				->join([
					'EncodingProfile' => [
						'select' => 'name'
					]
				])
				->select(
					'id, encoding_profile_id, revision, created, description'
				)
				->orderBy(EncodingProfile::TABLE . '.name, revision DESC');
			
			$versions = $this->versions->pluck('encoding_profile_id');
			
			if (!empty($versions)) {
				$this->versionsLeft->whereNot([
					'encoding_profile_id' => $versions
				]);
			}
			
			$this->encodingProfilesLeft = (clone $this->versionsLeft)
				->indexBy('encoding_profile_id', 'name')
				->toArray();
			
			return $this->render('projects/settings/profiles');
		}
		
		public function states() {
			// States
			$this->stateForm = $this->form();
			
			$this->states = TicketState::findAll()
				->join(['ProjectTicketState' => [
					'where' => ['project_id' => $this->project['id']]
				]])
				->select('ticket_type, ticket_state, service_executable')
				->orderBy('ticket_type, sort');

			$this->encodingStates = $this->project
				->States
				->where([
					'ticket_type' => 'encoding'
				])
				->indexBy('ticket_state', 'ticket_state')
				->toArray();

			if ($this->stateForm->wasSubmitted()) {
				if ($this->project->save($this->stateForm->getValues())) {
					$this->project->updateTicketStates();
					$this->flashNow('States updated');
				} else {
					$this->flashNow(
						'Failed to update states, ensure no tickets remain' .
						'in affected states'
					);
				}
			}
			
			return $this->render('projects/settings/states');
		}
		
		public function worker() {
			// Worker Groups
			$this->workerGroupForm = $this->form();
			
			if ($this->workerGroupForm->wasSubmitted() and
				$this->project->save($this->workerGroupForm->getValues())) {
				$this->flashNow('Worker group assignment updated');
			}
			
			$this->workerGroups = WorkerGroup::findAll()
				->select('id, title, paused')
				->scoped([
					'worker_group_filter_count' => [$this->project]
				])
				->orderBy('paused, title');
			$this->workerGroupAssignment = $this->project
				->WorkerGroup
				->select(WorkerGroup::TABLE . '.id')
				->indexBy('id')
				->toArray();
			
			return $this->render('projects/settings/worker');
		}
		
		public function edit_filter(array $arguments) {
			$this->workerGroup = WorkerGroup::findOrThrow($arguments['id']);
			$this->workerGroupFilter = ProjectWorkerGroupFilter::findAll()
				->where([
					'project_id' => $this->project['id'],
					'worker_group_id' => $this->workerGroup['id']
				]);
			
			$this->project['worker_group_id'] = $this->workerGroup['id'];
			
			$this->form();
			
			if ($this->form->wasSubmitted()) {
				// TODO: capture DuplicateKeyException, flash
				$this->project->save($this->form->getValues());
			}
			
			return $this->render('projects/settings/filter/edit');
		}
		
		public function create() {
			$this->form();
			
			if ($this->form->wasSubmitted() and
				($project = Project::create($this->form->getValues()))) {
				ProjectTicketState::createAll($project['id']);
				
				$this->flash('Project created');
				return $this->redirect('projects', 'settings', [
					'project_slug' => $project['slug']
				]);
			}
			
			return $this->render('projects/edit');
		}

		public function edit(array $arguments) {
			$this->form();
			
			if ($this->form->wasSubmitted() and
				$this->project->save($this->form->getValues())) {
				$this->flash('Project updated');
				$this->project['project_slug'] = $this->project['slug'];
				return $this->redirect('projects', 'settings', $this->project);
			}
			
			return $this->render('projects/edit');
		}
		
		/*
			Duplicate project, copy languages, associated encoding profiles,
			selected states and worker groups
		*/
		public function duplicate() {
			$project = $this->project->duplicate(true);
			
			// Copy associated entries
			
			$project['Languages'] = $this->project
				->Languages
				->toArray();
			
			$project['EncodingProfileVersion'] = $this->project
				->EncodingProfileVersion
				->except(['fields'])
				->select('id AS encoding_profile_version_id')
				->toArray();
			
			$project['States'] = $this->project
				->States
				->select('ticket_type, ticket_state, service_executable')
				->toArray();
			
			$project['WorkerGroup'] = $this->project
				->WorkerGroup
				->except(['fields'])
				->select('id AS worker_group_id')
				->toArray();
			
			// Ensure unique slug
			$i = 0;
			
			// TODO: Move to Model?
			do {
				$i++;
				$slug = 'duplicate-' . (($i > 1)? ($i . '-') : '') .
					'of-' . $project['slug'];
			} while (Project::exists(['slug' => $slug]));
			
			$project['title'] = 'Duplicate ' . (($i > 1)? ($i . ' ') : '') .
				'of ' . $project['title'];
			$project['slug'] = $project['project_slug'] = $slug;
			
			// Copy properties
			
			$properties = $this->project
				->Properties
				->indexBy('name')
				->toArray();
			
			$project['properties'] = $properties;
			
			if (!$project->save()) {
				return $this->redirect('projects', 'settings', $this->project);
			}
			
			$project->updateTicketStates();
			
			$this->flash('Project duplicated');
			return $this->redirect('projects', 'edit', $project);
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
