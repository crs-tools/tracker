<?php
	
	requires(
		'Random',
		'/Model/Worker',
		'/Model/WorkerGroup'
	);
	
	class Controller_Workers extends Controller_Application {
		
		public $requireAuthorization = true;
		
		public function index() {
			$this->groups = WorkerGroup::findAll()
				->includes(['Worker']);
			
			return $this->render('workers/index');
		}
		
		public function create_group() {
			$this->form();
			
			$group = new WorkerGroup($this->form->getValues());
			$group['token'] = Random::friendly(32);
			$group['secret'] = Random::friendly(32);
			
			if ($this->form->wasSubmitted() and $group->save()) {
				$this->flash('Worker group created');
				return $this->redirect('workers', 'index');
			}
			
			return $this->render('workers/group/edit');
		}
		
		public function edit_group(array $arguments) {
			$this->group = WorkerGroup::findOrThrow($arguments['id']);
			
			$this->form();
			
			if ($this->form->wasSubmitted()) {
				if ($this->form->getValue('create_secret')) {
					$this->group['secret'] = Random::friendly(32);
				}
				
				if ($this->group->save($this->form->getValues())) {
					if ($this->form->getValue('create_secret')) {
						$this->flashNow('New secret created');
					} else {
						$this->flash('Worker group updated');
						return $this->redirect('workers', 'index');
					}
				}
			}
			
			return $this->render('workers/group/edit');
		}
		
		public function delete_group(array $arguments) {
			WorkerGroup::deleteOrThrow($arguments['id']);
			
			$this->flash('Worker group deleted');
			return $this->redirect('workers', 'index');
		}
		
		public function queue(array $arguments) {
			$this->group = WorkerGroup::findOrThrow($arguments['id']);
			
			// TODO: fetch queue
			
			return $this->render('workers/group/queue');
		}
		
	}
	
?>