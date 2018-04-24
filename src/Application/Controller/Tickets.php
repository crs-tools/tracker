<?php
	
	requires(
		'String',
		
		'/Controller/Application',
		
		'/Model/Handle',
		
		'/Model/Import',
		
		'/Model/Ticket',
		'/Model/TicketState',
		'/Model/Comment',
		
		'/Model/EncodingProfile',
		'/Model/EncodingProfileVersion',
		
		'/Helper/EncodingProfile',
		'/Helper/Ticket',
		'/Helper/Time'
	);
	
	class Controller_Tickets extends Controller_Application {
		
		protected $requireAuthorization = true;
		
		private static $searchMapping = [
			'id' => 'id',
			'title' => 'title',
			'assignee' => 'handle_id',
			'type' => 'ticket_type',
			'state' => 'ticket_state',
			'failed' => 'failed',
			'needs_attention' => 'needs_attention',
			'encoding_profile' => 'encoding_profile_version_id',
			'fahrplan_id' => 'fahrplan_id',
			'day' => 'property_fahrplan_day.value',
			'room' => 'property_fahrplan_room.value',
			'language' => 'property_fahrplan_language.value',
			'persons' => 'property_fahrplan_persons.value',
			'optout' => 'property_fahrplan_optout.value',
			'property_name' => 'has_property.name',
			'modified' => 'modified'
		];
		
		private static $searchPropertyFields = [
			'date',
			'day',
			'time',
			'room',
			'language',
			'persons',
			'optout',
			'property_name'
		];
		
		protected $projectReadOnlyAccess = [
			'index' => true,
			'view' => true,
			'search' => true,
			'log' => true,
			'jobfile' => true,
			'feed' => true
		];
		
		public function index() {
			$this->tickets = Ticket::findAll()
				->where(['project_id' => $this->project['id']])
				->join(['Handle'])
				->scoped([
					'with_default_properties',
					'with_encoding_profile_name',
					'order_list'
				]);
			
			$this->form(null, null, Request::METHOD_GET);
			$this->searchForm = $this->form('tickets', 'search');
			
			$this->filter = ((isset($_GET['t']))? $_GET['t'] : null);
			
			if (
				$this->filter !== null or
				isset($_GET['u']) or
				isset($_GET['failed'])
			) {
				$this->tickets
					->distinct()
					->scoped([
						'with_child',
						'with_recording'
					]);
			}
			
			if ($this->filter !== null) {
				switch ($this->filter) {
					case 'recording':
					case 'cutting':
					case 'encoding':
						$this->tickets->scoped(['without_locked']);
					case 'releasing':
					case 'released':
						$this->tickets->scoped(['filter_' . $this->filter]);
						break;
				}
			}
			
			if (isset($_GET['u'])) {
				$this->tickets->scoped([
					'filter_handle' => [$_GET['u']]
				]);
			}
			
			if (isset($_GET['failed'])) {
				$this->tickets->scoped([
					'filter_failed'
				]);
			}
			
			return $this->render('tickets/index', [
				'format' => ['html', 'json']
			]);
		}
		
		public function view(array $arguments) {
			$this->ticket = Ticket::findByOrThrow([
				'id' => $arguments['id'],
				'project_id' => $this->project['id']
			], [], ['Handle']);
			
			$this->commentForm = $this->form('tickets', 'comment', $this->ticket, $this->project);
			
			if ($this->ticket['parent_id'] === null) {
				$this->children = $this->ticket
					->Children
					->scoped([
						'with_encoding_profile_name',
						'with_progress'
					])
					->orderBy('ticket_type, encoding_profile_name')
					->join(['Handle']);
				
				$this->import = $this->ticket->Import;
			} else {
				// TODO: add scope for properties
				// TODO: parent joins handle
				$this->parent = $this->ticket->Parent;
			}
			
			if (isset($_GET['merged'])) {
				$this->properties = $this->ticket
					->MergedProperties;
			} else {
				$this->properties = $this->ticket
					->VirtualProperties;
			}
			
			if (!empty($this->ticket['encoding_profile_version_id'])) {
				$this->profile = $this->ticket->EncodingProfile;
			}
			
			$this->_assignTimeline($this->ticket);
			
			return $this->render('tickets/view');
		}
		
		public function search() {
			$this->searchForm = $this->form();
			
			$this->users = User::findAll()
				->select('id, name')
				->orderBy('name')
				->indexBy('id', 'name')
				->toArray();
			
			// TODO: by worker group, by project...
			$this->workers = Worker::findAll()
				->select('id, name')
				->orderBy('last_seen DESC')
				->indexBy('id', 'name')
				->limit(15)
				->toArray();
			
			$this->assignedWorkers = Worker::findAll()
				->scoped(['assigned' => [$this->project['id']]])
				->select('id, name')
				->orderBy('last_seen DESC')
				->indexBy('id', 'name')
				->toArray();
			
			$states = [];
			
			foreach ($this->project->States as $state) {
				if (!isset($states[$state['ticket_type']])) {
					$states[$state['ticket_type']] = [];
				}
				
				$states[$state['ticket_type']][$state['ticket_type'] . '.' . $state['ticket_state']] =
					$state['ticket_state'];
			}
			
			$this->states = $states;
			
			$this->rooms = TicketProperties::findUniqueValues('Fahrplan.Room', $this->project['id'])
				->select('value')
				->indexBy('value', 'value');
			
			$this->days = TicketProperties::findUniqueValues('Fahrplan.Day', $this->project['id'])
				->select('value::int, \'Day \' || value AS day')
				->indexBy('value', 'day')
				->orderBy('value::int');
			
			$this->languages = $this->project
				->Languages
				->indexBy('language', 'description');
		
			$this->profiles = $this->project
				->EncodingProfileVersion
				->indexBy('id', 'description');
			
			$this->fields = $this->searchForm->getValue('fields');
			$this->operators = $this->searchForm->getValue('operators');
			$this->values = $this->searchForm->getValue('values');
			
			if ($q = $this->searchForm->getValue('q')) {
				// quicksearch-queries consisting only of numbers are interpreted as searches for a fahrplan-id
				if (ctype_digit($q)) {
					$this->fields[] = 'fahrplan_id';
					$this->operators[] = 'is';
					$this->values[] = $q;
				} else {
					$this->fields[] = 'title';
					$this->operators[] = 'contains';
					$this->values[] = $q;
				}
			}
			
			if (!empty($_GET['id'])) {
				$this->fields = ['id'];
				$this->operators = ['is_in'];
				$this->values = [$_GET['id']];
			}
			
			if (($this->searchForm->wasSubmitted() or !empty($_GET['id'])) and
				$this->fields and $this->operators and $this->values and
				count($this->fields) == count($this->operators) and
				count($this->fields) == count($this->values)
			) {
				$this->evaluateSearch($this->fields, $this->operators, $this->values);
			}
			
			return $this->render('tickets/search');
		}
		
		protected function evaluateSearch($fields, $operators, $values) {
			$tickets = Ticket::findAll()
				->where(['project_id' => $this->project['id']])
				->join(['Handle'])
				->distinct()
				->scoped([
					'with_default_properties',
					'with_properties' => [[
						'Fahrplan.Language' => 'fahrplan_language',
						'Fahrplan.Persons' => 'fahrplan_persons',
						'Fahrplan.Recording.Optout' => 'fahrplan_optout'
					]],
					'with_encoding_profile_name',
					'with_progress',
					'with_child',
					'order_list'
				]);
			
			$mainCondition = $subCondition = [];
			$mainParams = $subParams = [];
			
			reset($fields);
			while (list($i, $key) = each($fields)) {
				$condition = '';
				$params = [];
				
				if (empty($operators[$i]) or !isset($values[$i])) {
					continue;
				}
				
				switch ($key) {
					case 'property_name':
						$tickets->scoped(['with_has_property']);
						break;
				}
				
				if (!isset(self::$searchMapping[$key])) {
					continue;
				}
				
				if ($key == 'state') {
					list($type, $state) = explode('.', $values[$i], 2);
					$values[$i] = $state;
					
					$fields[] = 'type';
					$operators[] = 'is';
					$values[] = $type;
				}
				
				switch ($operators[$i]) {
					case 'is':
						$condition = self::$searchMapping[$key] . ' = ?';
						$params[] = $values[$i];
						break;
					case 'is_not':
						$condition = self::$searchMapping[$key] . ' != ?';
						$params[] = $values[$i];
						break;
					case 'is_in':
					case 'is_not_in':
						$parts = explode(',', $values[$i]);
						$condition = self::$searchMapping[$key] . (($operators[$i] == 'is_not_in')? 'NOT ' : '') . ' IN (' . substr(str_repeat('? , ', count($parts)), 0, -3) . ')';
						
						foreach ($parts as $part) {
							$params[] = trim($part);
						}
						break;
					case 'contains':
						$condition = self::$searchMapping[$key] . ' ILIKE ?';
						$params[] = '%' . $values[$i] . '%';
						break;
					case 'begins_with':
						$condition = self::$searchMapping[$key] . ' ILIKE ?';
						$params[] = $values[$i] . '%';
						break;
					case 'ends_with':
						$condition = self::$searchMapping[$key] . ' ILIKE ?';
						$params[] = '%' . $values[$i];
						break;
					default:
						continue 2;
				}
				
				$mainCondition[] = $condition;
				$mainParams = array_merge($mainParams, $params);
				
				// property-fields are joined from the property-table and do not exist as field of the sub-table
				if (!in_array($key, self::$searchPropertyFields)) {
					$subCondition[] = 'child.' . $condition;
					$subParams = array_merge($subParams, $params);
				} else {
					$subCondition[] = $condition;
					$subParams = array_merge($subParams, $params);
				}
			}
			
			$subq = [];
			if (count($mainCondition) > 0) {
				$subq[] = implode(' AND ', $mainCondition);
			}
			
			if (count($subCondition) > 0) {
				$subq[] = implode(' AND ', $subCondition);
			}
			
			if (empty($subq)) {
				return;
			}
			
			$tickets->where(
				'(' . implode(') OR (', $subq) . ')',
				array_merge($mainParams, $subParams)
			);
			
			$this->tickets = $tickets;
		}
		
		public function log(array $arguments) {
			$ticket = Ticket::findByOrThrow([
				'id' => $arguments['id'],
				'project_id' => $this->project['id']
			]);
			
			$log = LogEntry::findByOrThrow([
				'id' => $arguments['entry'],
				'ticket_id' => $ticket['id']
			]);
			
			if (!$this->respondTo('txt')) {
				return Response::error(400);
			}
			
			$this->Response->setContent($log['comment']);
		}
		
		public function jobfile(array $arguments) {
			if (!$this->respondTo('xml')) {
				return Response::error(400);
			}
			
			$ticket = Ticket::findOrThrow(['id' => $arguments['id']], ['Project']);
			
			$properties = $ticket
				->MergedProperties
				->indexBy('name', 'value')
				->toArray();
			
			$this->Response->setContent(
				$ticket->EncodingProfileVersion->getJobfile($properties)
			);
		}
		
		public function feed() {
			$this->log = LogEntry::findAll()
				->join(['Handle', 'Ticket', 'ParentTicket'])
				->scoped([
					'include_in_feed',
					'with_title'
				])
				->where(['tbl_ticket.project_id' => $this->project['id']])
				->orderBy('created DESC, id DESC')
				->limit(100);
			
			if (isset($_GET['before'])) {
				$this->log->where('id < ?', [$_GET['before']]);
			}
			
			if (isset($_GET['after'])) {
				$this->log->where('id > ?', [$_GET['after']]);
			}
						
			$this->stats = [
				'cutting' => Ticket::countByNextState($this->project['id'], 'recording', 'cutting'),
				'checking' => Ticket::countByNextState($this->project['id'], 'encoding', 'checking'),
				'fixing' => Ticket::findAll()->where(['failed' => true, 'project_id' => $this->project['id']])->count()
			];
			$this->progress = Ticket::getTotalProgress($this->project['id']);
			
			return $this->render('tickets/feed', ['format' => ['html', 'json']]);
		}
		
		public function cut(array $arguments) {
			return $this->_action('cut', $arguments);
		}
		
		public function uncut(array $arguments) {
			return $this->_undoAction('cut', $arguments);
		}
		
		public function check(array $arguments) {
			return $this->_action('check', $arguments);
		}
		
		public function uncheck(array $arguments) {
			return $this->_undoAction('check', $arguments);
		}
		
		private function _action($action, array $arguments) {
			$this->ticket = Ticket::findByOrThrow([
				'id' => $arguments['id'],
				'project_id' => $this->project['id']
			], [], ['Handle']);
			
			$this->state = TicketState::getStateByAction($action);
			
			if (
				$this->state === false or
				!$this->ticket->isEligibleAction($action)
			) {
				$this->flash('Ticket is not in the required state to execute the action ' . $action);
				return $this->redirect('tickets', 'view', $this->ticket, $this->project);
			}
			
			if ($this->ticket['ticket_state'] !== $this->state) {
				$this->ticket->save([
					'handle_id' => User::getCurrent()['id'],
					'ticket_state' => $this->state,
					'failed' => false
				]);
				
				$this->ticket->addLogEntry([
					'event' => 'Action.' . $action . '.start',
					'to_state' => $this->state
				]);
			}
			
			$this->action = $action;
			$this->actionForm = $this->form('tickets', $action);
			
			$this->languages = $this->project
				->Languages
				->indexBy('language', 'description');
			
			$this->properties = $this->ticket
				->VirtualProperties;
			
			$this->parentProperties = $this->ticket
				->Parent
				->VirtualProperties;
			
			if ($this->ticket['ticket_type'] !== 'recording') {
				$this->recordingProperties = $this->ticket
					->Parent
					->Source
					->VirtualProperties;
			}
			
			if ($action === 'check') {
				// check if user already cut this ticket
				$lastCut = $this->ticket
					->Parent
					->Source
					->LogEntries
					->select('handle_id')
					->where([
						'event' => 'Action.cut'
					])
					->orderBy('created DESC')
					->first();
				
				$this->sameUser = (
					$lastCut !== null and
					$lastCut['handle_id'] === User::getCurrent()['id']
				);
			}
			
			if ($this->actionForm->wasSubmitted()) {
				if ($this->actionForm->getValue('comment')) {
					if ($this->actionForm->getValue('reset')) {
						$comment = $this->ticket
							->Parent
							->Source
							->addComment(
								$this->actionForm->getValue('comment')
							);
					} else {
						$comment = $this->ticket->addComment(
							$this->actionForm->getValue('comment')
						);
					}
				}
				
				if ($this->actionForm->getValue('appropriate') and
					$this->ticket->save(['handle_id' => User::getCurrent()['id']])) {
					$this->flashNow('This ticket is now assigned to you');
				} elseif ($this->actionForm->getValue('expand')) {
					if ($this->ticket->expandRecording([
						(int) $this->actionForm->getValue('expand_left'),
						(int) $this->actionForm->getValue('expand_right')
					])) {
						return $this->_redirectNextOrView('Expanded timeline');
					}
				} elseif ($this->actionForm->getValue('failed')) {
					if ($this->ticket->save([
						'handle_id' => null,
						'failed' => true
					])) {
						$this->ticket->addLogEntry([
							'comment_id' => (isset($comment))? $comment['id'] : null,
							'event' => 'Action.' . $action . '.failed'
						]);
						
						return $this->_redirectNextOrView('Marked ticket as failed');
					}
				} elseif ($this->actionForm->getValue('reset')) {
					if ($this->ticket->Parent->resetSource($comment)) {
						return $this->_redirectNextOrView('Reset all encoding tasks, source failed');
					}
				} elseif ($this->actionForm->getValue('language') === '') {
					$this->flashNow('You have to choose a language');
				} else {
					$properties = [];
					
					if ($this->actionForm->getValue('language')) {
						$properties[] = [
							'name' => 'Record.Language',
							'value' => $this->actionForm->getValue('language')
						];
					}
					
					if ($this->actionForm->getValue('delay')) {
						$properties[] = [
							'name' => 'Record.AVDelay',
							'value' => millisecondsToDelay($this->actionForm->getValue('delay_by'))
						];
					} else {
						$properties[] = [
							'name' => 'Record.AVDelay',
							'_destroy' => true
						];
					}
					
					$previousState = $this->ticket['ticket_state'];
					
					if ($this->ticket->save([
						'ticket_state' => $this->ticket->queryNextState($this->state),
						'handle_id' => null,
						'failed' => false,
						'properties' => $properties
					])) {
						$this->ticket->addLogEntry([
							'comment_id' => (isset($comment))? $comment['id'] : null,
							'event' => 'Action.' . $action,
							'from_state' => $previousState,
							'to_state' => $this->state
						]);
						
						return $this->_redirectNextOrView('Successfully finished ' . $this->state);
					}
				}
			}
			
			$this->_assignTimeline($this->ticket);
			
			return $this->render('tickets/view');
		}
		
		private function _undoAction($action, array $arguments) {
			$ticket = Ticket::findByOrThrow([
				'id' => $arguments['id'],
				'project_id' => $this->project['id']
			]);
			
			if (!$ticket->isEligibleAction($action)) {
				$this->flash('Ticket is not in the required state to undo the action ' . $action);
				return $this->redirect('tickets', 'view', $ticket, $this->project);
			}
			
			$previousState = $ticket['ticket_state'];
			
			if ($ticket->save([
				'ticket_state' => $ticket->queryPreviousState(
					TicketState::getStateByAction($action)
				),
				'handle_id' => null
			])) {
				$ticket->addLogEntry([
					'event' => 'Action.' . $action . '.abort',
					'from_state' => $previousState,
					'to_state' => $ticket['ticket_state']
				]);
				
				$this->flash('Ticket reset from action ' . $action);
			}
			
			return $this->redirect('tickets', 'view', $ticket, $this->project);
		}
		
		private function _redirectNextOrView($flash) {
			if ($this->actionForm->getValue('jump')) {
				$next = $this->ticket->findNextForAction($this->state);
				
				if ($next !== null) {
					$this->flash($flash . ', jumped to next ticket');
					return $this->redirect('tickets', $this->action, $next, $this->project, ['?jump']);
				} else {
					$this->flash($flash . ', no tickets left to ' . $this->action);
				}
			} else {
				$this->flash($flash);
			}
			
			return $this->redirect('tickets', 'view', $this->ticket, $this->project);
		}
		
		/*
		public function reset($arguments = array()) {
			if (empty($arguments['id']) or !$ticket = $this->Ticket->find($arguments['id'], array(), array('project_id' => $this->Project->id))) {
				throw new EntryNotFoundException();
			}
			
			if ($this->Ticket->resetEncodingTask($ticket['id'])) {
				$this->flash('Encoding task resetted');
			}
			
			$this->_redirectWithReferer($ticket);
		}*/
		
		public function comment(array $arguments) {
			$ticket = Ticket::findByOrThrow([
				'id' => $arguments['id'],
				'project_id' => $this->project['id']
			]);
			
			$this->form();
			
			if ($this->form->wasSubmitted()) {
				$ticket->needsAttention($this->form->getValue('needs_attention'));
				
				if ($ticket->addComment($this->form->getValue('text'))) {
					$this->flash('Comment created');
				}
			}
			
			return $this->redirect('tickets', 'view', $ticket, $this->project);
		}
		
		public function delete_comment(array $arguments) {
			$ticket = Ticket::findByOrThrow([
				'id' => $arguments['ticket_id'],
				'project_id' => $this->project['id']
			]);
			
			$comment = Comment::findAll()->where([
				'id' => $arguments['id'],
				'ticket_id' => $ticket['id']
			]);
			
			if (!User::isAllowed('tickets', 'delete_comment')) {
				$comment->where(['handle_id' => User::getCurrent()['id']]);
			}
			
			if (!($comment = $comment->first())) {
				throw new EntryNotFoundException();
			}
			
			if ($comment->destroy()) {
				$this->flash('Comment deleted');
			}
			
			return $this->redirect('tickets', 'view', $ticket, $this->project);
		}
		
		public function create() {
			$this->form();
			
			$this->_assignSelectValues('meta');
			
			if ($this->form->wasSubmitted()) {
				$values = $this->form->getValues();
				
				$fahrplanId = (isset($values['fahrplan_id']))?
					$values['fahrplan_id'] : '';
				
				// Internal fahrplan id is mandatory, must be unique and can't be changed later
				// TODO: move to Model/Validation
				if (empty($fahrplanId) or
					Ticket::exists([
						'project_id' => $this->project['id'],
						'fahrplan_id' => $fahrplanId
					])
				) {
					$this->flashNow('Please fill in a uniqe Fahrplan ID');
					return $this->render('tickets/edit');
				}
				
				$values['project_slug'] = $this->project['project_slug'];
				$values['project_id'] = $this->project['id'];
				$values['ticket_type'] = 'meta';
				
				if($ticket = Ticket::create($values)) {
					$this->flash('Ticket created');
					
					if ($this->form->getValue('comment')) {
						$ticket->addComment($this->form->getValue('comment'));
					}
					
					$ticket->needsAttention(
						$this->form->getValue('group_needs_attention')
					);
					
					Ticket::createMissingRecordingTickets(
						$this->project['id']
					);
					
					Ticket::createMissingEncodingTickets(
						$this->project['id']
					);
					
					return $this->redirect('tickets', 'view', $ticket);
				}
			}
			
			return $this->render('tickets/edit');
		}
		
		public function edit(array $arguments) {
			$this->ticket = Ticket::findByOrThrow([
				'id' => $arguments['id'],
				'project_id' => $this->project['id']
			], [], ['Handle']);
			
			$this->form();
			
			if ($this->form->wasSubmitted()) {
				try {
					if (!$this->ticket->saveOrThrow(
							$this->form->getValues(),
							[Ticket::FIELD_MODIFIED =>
								$this->form->getValue('last_modified')]
					)) {
						$this->flashViewNow('tickets/edit/_flash', self::FLASH_WARNING);
					} else {
						if ($this->form->getValue('comment')) {
							$this->ticket->addComment($this->form->getValue('comment'));
						}
						
						$this->ticket->needsAttention(
							$this->form->getValue('group_needs_attention')
						);
						
						$this->flash('Ticket updated');
						return $this->redirect('tickets', 'view', $this->ticket, $this->project);
					}
				} catch (ModelException $e) {
					$this->flashNow('An error occurred while saving ticket', self::FLASH_WARNING);
				}
			}
			
			$this->_assignSelectValues($this->ticket['ticket_type']);
			
			return $this->render('tickets/edit');
		}
		
		public function edit_multiple(array $arguments) {
			$tickets = explode(',', $arguments['tickets']);
			
			if (empty($tickets)) {
				throw new EntryNotFoundException();
			}
			
			$this->tickets = Ticket::findAll()
				->select('id, ticket_type')
				->where([
					'id' => $tickets,
					'project_id' => $this->project['id']
				])
				// load entries here to avoid extra query for pluck later
				->load();
			
			$rows = $this->tickets->getRows();
			
			if ($rows <= 0) {
				throw new EntryNotFoundException();
			}
			
			if ($rows === 1) {
				return $this->redirect(
					'tickets',
					'edit',
					$this->tickets->first(),
					$this->project
				);
			}
			
			if (count(array_unique($this->tickets->pluck('ticket_type'))) > 1) {
				$this->flash(
					'Cannot edit multiple tickets with different types at the same time.'
				);
				return $this->redirect('tickets', 'index', $this->project);
			}
			
			$this->form();
			
			$this->ticketType = $this->tickets->first()['ticket_type'];
			$this->properties = TicketProperties::findAll()
				->select('name, \'\' AS value')
				->distinct()
				->where([
					'ticket_id' => $tickets
				]);
			$this->_assignSelectValues($this->ticketType);
			
			if ($this->form->wasSubmitted()) {
				$values = $this->form->getValues();
				$count = 0;
				
				foreach($this->tickets as $ticket) {
					if ($ticket->save($values)) {
						$count++;
					}
				}
				
				$this->flash(
					$count . ' Ticket' . (($count !== 1)? 's' : '') .
						' updated'
				);
				return $this->redirect('tickets', 'index', $this->project);
			}
			
			return $this->render('tickets/edit_multiple');
		}
		
		private function _assignSelectValues($ticketType) {
			$this->states = $this->project
				->States
				->where([
					'ticket_type' => $ticketType
				])
				->indexBy('ticket_state', 'ticket_state')
				->toArray();
			
			$this->users = User::findAll()
				->select('id, name')
				->orderBy('name')
				->indexBy('id', 'name')
				->toArray();
			
			if (!isset($this->ticket)) {
				return;
			}
			
			if (
				$this->ticket['handle_id'] !== null and
				!isset($this->users[$this->ticket['handle_id']]) and
				isset($this->ticket['handle_name'])
			) {
				$this->users = [
					$this->ticket['handle_id'] =>
						'(' . $this->ticket['handle_name'] . ')'
				] + $this->users;
			}
			
			if (empty($this->ticket['encoding_profile_version_id'])) {
				return;
			}
			
			$this->profile = EncodingProfileVersion::findAll(
				['EncodingProfile' => ['select' => 'id, name']]
			)
				->where([
					'id' => $this->ticket['encoding_profile_version_id']
				])
				->select('revision, description')
				->first();
		}
		
		private function _assignTimeline($ticket) {
			if ($ticket['parent_id'] === null) {
				$this->comments = $ticket->Comments;
			} else {
				$this->comments = $ticket->Parent->Comments;
			}
			
			$this->comments
				->join(['User'])
				->orderBy('created DESC');
			
			$this->log = $this->ticket
				->LogEntries
				->join(['Handle'])
				->orderBy('created DESC');
		}
		
		public function duplicate(array $arguments) {
			$this->ticket = Ticket::findBy([
				'id' => $arguments['id'],
				'project_id' => $this->project['id']
			]);
			
			$this->form();
			
			if ($this->form->wasSubmitted()) {
				$ticket = $this->ticket->duplicate();
				
				// We don't need a unique title here, titles are less important for tickets since we have the fahrplan id
				$ticket['title'] = 'Duplicate of ' . $ticket['title'];
				
				// Copy properties
				$ticket['properties'] = $this->ticket
					->Properties
					->toArray();
				
				if ($ticket->save($this->form->getValues())) {
					if (empty($this->form->getValue('duplicate_recording_ticket'))) {
						Ticket::createMissingRecordingTickets(
							$this->project['id']
						);
					} else {
						$sourceTicket = $this->ticket
							->Source
							->duplicate();
						
						$sourceTicket->save([
							'parent_id' => $ticket['id'],
							'fahrplan_id' => $ticket['fahrplan_id']
						]);
					}
					
					Ticket::createMissingEncodingTickets(
						$this->project['id']
					);
					
					$this->flash('Ticket duplicated');
					return $this->redirect(
						'tickets', 'view', $ticket, $this->project
					);
				}
			}
			
			$this->states = $this->project
				->States
				->where([
					'ticket_type' => $this->ticket['ticket_type']
				]);
			
			return $this->render('tickets/duplicate');
		}
		
		public function delete(array $arguments) {
			$ticket = Ticket::findByOrThrow([
				'id' => $arguments['id'],
				'project_id' => $this->project['id']
			]);
			
			if ($ticket->destroy()) {
				$this->flash('Ticket deleted');
			}
			
			return $this->redirect('tickets', 'index', $this->project);
		}
		
		/*
		private function _redirectWithReferer(array $ticket) {
			if ($referer = Request::get('ref')) {
				if ($this->View->isValidReferer($referer)) {
					return $this->View->redirect('tickets', 'index', array('project_slug' => $this->Project->slug, '?t=' . $referer));
				}
				
				return $this->View->redirect('tickets', 'index', array('project_slug' => $this->Project->slug));
			}
			
			return $this->View->redirect('tickets', 'view', $ticket + array('project_slug' => $this->Project->slug));
		}
		*/
	}
	
?>
