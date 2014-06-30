<?php
	
	requires(
		'String',
		
		'/Controller/Application',
		
		'/Model/Handle',
		
		'/Model/Ticket',
		'/Model/TicketState',
		'/Model/Comment',
		
		'/Model/EncodingProfile',
		'/Model/EncodingProfileVersion',
		
		'/Helper/EncodingProfile',
		'/Helper/Log',
		'/Helper/Ticket',
		'/Helper/Time'
	);
	
	class Controller_Tickets extends Controller_Application {
		
		protected $requireAuthorization = true;
		
		private static $searchMapping = [
			'title' => 'title',
			'assignee' => 'handle_id',
			'type' => 'ticket_type',
			'state' => 'ticket_state',
			'encoding_profile' => 'encoding_profile_version_id',
			'fahrplan_id' => 'fahrplan_id',
			'date' => 'fahrplan_date_join.value',
			'day' => 'fahrplan_day_join.value',
			'time' => 'fahrplan_start_join.value',
			'room' => 'fahrplan_room_join.value',
			'modified' => 'modified'
		];
		
		private static $searchPropertyFields = [
			'date',
			'day',
			'time',
			'room'
		];
		
		protected $projectReadOnlyAccess = [
			'index' => true,
			'view' => true,
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
					'with_progress',
					'order_list'
				]);
			
			$this->form(null, null, Request::METHOD_GET);
			$this->searchForm = $this->form('tickets', 'search');
			
			$this->filter = ((isset($_GET['t']))? $_GET['t'] : null);
			
			if ($this->filter !== null or isset($_GET['u'])) {
				$this->tickets
					->distinct()
					->scoped([
						'with_child',
						'with_recording',
						'without_locked'
					]);
			}
			
			if ($this->filter !== null) {
				switch ($this->filter) {
					case 'recording':
					case 'cutting':
					case 'encoding':
					case 'releasing':
					case 'released':
						$this->tickets->scoped(['filter_' . $this->filter]);
						break;
				}
			}
			
			if (isset($_GET['u'])) {
				$this->tickets->scoped([
					'filter_handle' => ['handle' => $_GET['u']]
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
			
			$this->commentForm = $this->form('tickets', 'comment', $this->project, $this->ticket);
			
			if (!empty($this->ticket['parent_id'])) {
				// TODO: add scopes for properties and progress
				// TODO: parent joins handle
				$this->parent = $this->ticket->Parent;
			} else {
				$this->children = $this->ticket
					->Children
					->scoped([
						'with_encoding_profile_name',
						'with_progress'
					])
					->orderBy('ticket_type, title')
					->join(['Handle']);
			}
			
			$this->properties = $this->ticket->Properties;
			
			if (!empty($this->ticket['encoding_profile_version_id'])) {
				$this->profile = EncodingProfileVersion::findAll([
					'EncodingProfile' => ['select' => 'id, name']
				])
					->where(['id' => $this->ticket['encoding_profile_version_id']])
					->select('revision, description')
					->first();
			}
			
			$this->comments = $this->ticket
				->Comments
				->join(['User'])
				->orderBy('created DESC');
			$this->log = $this->ticket
				->LogEntries
				->join(['Handle'])
				->orderBy('created DESC');
			
			return $this->render('tickets/view');
		}
		
		public function search() {
			$this->form('tickets', 'index');
			$this->searchForm = $this->form('tickets', 'search');
			
			$this->users = User::findAll()
					->select('id, name')
					->indexBy('id', 'name')
					->toArray();
			
			$types = ['meta', 'recording', 'encoding'];
			$this->types = array_combine($types, $types);
			
			$states = [];
			foreach(TicketState::findAll() as $state) {
					$states[$state['ticket_type']][$state['ticket_type'].'.'.$state['ticket_state']] = $state['ticket_state'];
			}
			$this->states = $states;
			
			$rooms = $days = [];
			$tickets = Ticket::findAll()
				->where([
					'project_id' => $this->project['id'],
					'ticket_type' => 'meta',
				]);
			
			foreach ($tickets as $ticket) {
				$properties = $ticket->Properties
					->indexBy('name', 'value');
				
				if(isset($properties['Fahrplan.Room']))
					$rooms[$properties['Fahrplan.Room']] = $properties['Fahrplan.Room'];
				
				if(isset($properties['Fahrplan.Day']))
					$days[$properties['Fahrplan.Day']] = 'Day '.$properties['Fahrplan.Day'];
			}
			$this->rooms = $rooms;
			$this->days = $days;
			
			$this->profiles = $this->project
					->EncodingProfileVersion
					->indexBy('id', 'description')
					->toArray();
			
			// list ticket without type-filter // TODO: hide filter bar?
			$this->filter = 'search';
			
			$this->fields = $this->searchForm->getValue('fields');
			$this->operators = $this->searchForm->getValue('operators');
			$this->values = $this->searchForm->getValue('values');
			
			if($q = $this->searchForm->getValue('q')) {
				// quicksearch-queries consisting only of numbers are interpreted as searches for a fahrplan-id
				if(is_numeric($q)) {
					$this->fields[] = 'fahrplan_id';
					$this->operators[] = 'is';
					$this->values[] = $q;
				}
				else
				{
					$this->fields[] = 'title';
					$this->operators[] = 'contains';
					$this->values[] = $q;
				}
			}
			
			if($this->searchForm->wasSubmitted() && $this->fields && $this->operators && $this->values && count($this->fields) == count($this->operators) && count($this->fields) == count($this->values)) {
				$this->evaluateSearch($this->fields, $this->operators, $this->values);
			}
			
			return $this->render('tickets/index');
		}
		
		protected function evaluateSearch($fields, $operators, $values) {
			$tickets = Ticket::findAll()
				->where(['project_id' => $this->project['id']])
				->join(['Handle'])
				->distinct()
				->scoped([
					'with_default_properties',
					'with_encoding_profile_name',
					'with_progress',
					'order_list'
				]);
			
			if ($query = $this->searchForm->getValue('q')) {
				if (ctype_digit($query)) {
					$tickets->where(array('fahrplan_id' => (int)$query));
				} else {
					$tickets->where('title ILIKE ?', array('%' . $query . '%'));
				}
			}
			

			/*
			 * this join results in a query like this:
			 *	 SELECT
			 *		main.id AS main_id,
			 *		main.parent_id AS main_parent_id,
			 *		main.ticket_type AS main_ticket_type,
			 *		main.title AS main_title,
			 *
			 *		sub.id AS sub_id,
			 *		sub.parent_id AS sub_parent_id,
			 *		sub.ticket_type AS sub_ticket_type,
			 *		sub.title AS sub_title
			 *
			 *	FROM tbl_ticket main
			 *	LEFT JOIN tbl_ticket sub ON sub.parent_id = main.id
			 *
			 *	WHERE main.fahrplan_id = 38
			 *	AND main.project_id = 8;
			 *
			 *
			 * which will result in a result-set like this:
			 *
			 *  main_id | main_parent_id | main_ticket_type |              main_title               | sub_id | sub_parent_id | sub_ticket_type |               sub_title               
			 * ---------+----------------+------------------+---------------------------------------+--------+---------------+-----------------+---------------------------------------
			 *     1225 |                | meta             | Opengeofiction                        |   1267 |          1225 | encoding        | Opengeofiction (H.264-MP4 from DV HQ)
			 *     1225 |                | meta             | Opengeofiction                        |   1268 |          1225 | encoding        | Opengeofiction (WebM from DV)
			 *     1225 |                | meta             | Opengeofiction                        |   1364 |          1225 | recording       | Opengeofiction (Recording)
			 *     1364 |           1225 | recording        | Opengeofiction (Recording)            |        |               |                 | 
			 *     1267 |           1225 | encoding         | Opengeofiction (H.264-MP4 from DV HQ) |        |               |                 | 
			 *     1268 |           1225 | encoding         | Opengeofiction (WebM from DV)         |        |               |                 | 
			 *
			 * when we test the main- and the sub-fields, psql will return all matching subtickets and their main-tickets with a condition like
			 *
			 * AND (
			 *         "main"."handle_id" = ? AND
			 *         "main"."ticket_type"
			 *     ) OR (
			 *         "sub".handle_id = ? AND
			 *         "sub".ticket_type = ?
			 *     )
			 * )
			 */

			$tickets->join(['tbl_ticket', 'tbl_ticket_subticket'], 'parent_id = tbl_ticket.id', array(), null, 'LEFT');
			
			$mainCondition = $subCondition = [];
			$mainParams = $subParams = [];
			
			reset($fields);
			while (list($i, $key) = each($fields)) {
				$condition = '';
				$params = [];
				
				if (empty($operators[$i]) or empty($values[$i])) {
					continue;
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
				
				// property-fields are joined in from the property-table and does not exist as field of the sub-table
				if (!in_array($key, self::$searchPropertyFields)) {
					$subCondition[] = 'tbl_ticket_subticket.' . $condition;
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
			
			$tickets->where(
				'('.implode(' OR ', $subq).')',
				array_merge($mainParams, $subParams)
			);
			
			$this->tickets = $tickets;
			
			// Mass Edit
			//if (Request::isPostRequest() and Request::post('edit')) {
			//	return $this->View->redirect('tickets', 'edit', array('project_slug' => $this->Project->slug, 'id' => implode(Model::indexByField($tickets,'id', 'id'), ',')));
			//}
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
			
			// TODO: cleanup
			requires('/Controller/XMLRPC/Handler');
			
			$handler = new Controller_XMLRPC_Handler();
			$this->Response->setContent($handler->getJobfile($arguments['id']));
		}
		
		public function feed() {
			$this->log = LogEntry::findAll()
				->join(['Handle', 'Ticket'])
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
			
			if (!$this->ticket->isEligibleAction($action)) {
				$this->flash('Ticket is not in the required state to execute the action ' . $action);
				return $this->redirect('tickets', 'view', $this->ticket, $this->project);
			}
			
			$state = TicketState::getStateByAction($action);
			
			if ($state === false) {
				$this->flash('Ticket is not in the required state to execute this action');
			}
			
		    if ($this->ticket['ticket_state'] != $state) {
				$this->ticket->save([
					'handle_id' => User::getCurrent()['id'],
					'ticket_state' => $state,
					'failed' => false
				]);
   			}
			
			$this->action = $action;
			$this->actionForm = $this->form('tickets', $action);
			
			$this->languages = $this->project
				->Languages
				->indexBy('language', 'description');
			
			$this->properties = $this->ticket
				->Properties
				->indexBy('name', 'value');
			
			$this->parentProperties = $this->ticket
				->Parent
				->Properties
				->indexBy('name', 'value');
			
			$this->recordingProperties = $this->ticket
				->Parent
				->Children
				->where(['ticket_type' => 'recording'])
				->first()
				->Properties
				->indexBy('name', 'value');
			
			if ($this->actionForm->wasSubmitted()) {
				if ($this->actionForm->getValue('comment')) {
					$comment = Comment::create([
						'ticket_id' => $this->ticket['id'],
						'handle_id' => User::getCurrent()['id'],
						'comment' => $this->actionForm->getValue('comment')
					]);
				}
				
				if ($this->actionForm->getValue('appropriate') and
					$this->ticket->save(['handle_id' => User::getCurrent()['id']])) {
					$this->flashNow('This ticket is now assigned to you');
				} elseif ($this->actionForm->getValue('expand') and
					$this->ticket->expandRecording([
						(int) $this->actionForm->getValue('expand_left'),
						(int) $this->actionForm->getValue('expand_right')
					])) {
					$this->flash('Expanded timeline, ' . $action . ' another ticket while preparing');
					return $this->redirect('tickets', 'view', $this->ticket, $this->project);
				} elseif ($this->actionForm->getValue('failed') and
					$this->ticket->save([
						'handle_id' => null,
						'failed' => true
					])) {
					LogEntry::createForTicket($this->ticket, [
						'comment_id' => (isset($comment))? $comment['id'] : null,
						'event' => 'Action.' . $action . '.failed',
						'handle_id' => User::getCurrent()['id']
					]);
					
					$this->flash('Marked ticket as failed');
					return $this->redirect('tickets', 'view', $this->ticket, $this->project);
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
					
					$oldState = $this->ticket['ticket_state'];
					
					if ($this->ticket->save([
						'ticket_state' => $this->ticket->queryNextState($state),
						'handle_id' => null,
						'failed' => false,
						'properties' => $properties
					])) {
						LogEntry::createForTicket($this->ticket, [
							'comment_id' => (isset($comment))? $comment['id'] : null,
							'event' => 'Action.' . $action,
							'handle_id' => User::getCurrent()['id'],
							'from_state' => $oldState,
							'to_state' => $state
						]);
						
						$this->flash('Successfully finished ' . $state);
					}
					
					return $this->redirect('tickets', 'view', $this->ticket, $this->project); 
				}
			}
			
			$this->comments = $this->ticket
				->Comments
				->join(['User'])
				->orderBy('created DESC');
			$this->log = $this->ticket
				->LogEntries
				->join(['Handle'])
				->orderBy('created DESC');
			
			/*
			if (Request::isPostRequest()) {
				…
					if (Request::post('reset', Request::checkbox) and !empty($states['reset'])) {
						if (!empty($ticket['parent_id'])) {
							$this->Ticket->resetRecordingTask($ticket['parent_id']);
						}
						
						return $this->_redirectWithReferer($ticket);
				…
					} else {
				…
					if ($this->Ticket->save()) {
						
						if (Request::post('forward', Request::checkbox)) {
							if (isset($ticket['encoding_profile_id']) and $forward = $this->Ticket->findFirst(array(), 'project_id = ? AND state_id = ? AND encoding_profile_id = ? AND id != ?', array($this->Project->id, $states['from'], $ticket['encoding_profile_id'], $ticket['id']))) {
								return $this->View->redirect('tickets', $action, $forward + array('project_slug' => $this->Project->slug, '?forward'));
							}
							
							if ($forward = $this->Ticket->findFirst(array(), 'project_id = ? AND state_id = ? AND id != ?', array($this->Project->id, $states['from'], $ticket['id']))) {
								return $this->View->redirect('ticket', 'view', $forward + array('project_slug' => $this->Project->slug, '?forward'));
							}
							
							$this->flash('No more tickets to ' . $action);
						}
						
						return $this->_redirectWithReferer($ticket);
					}
				}
			}
			*/
			
			// $this->comments = $this->ticket->Comments->join(['User']);
			
			return $this->render('tickets/view');
		}
		
		private function _undoAction($action, array $arguments) {
			$this->ticket = Ticket::findByOrThrow([
				'id' => $arguments['id'],
				'project_id' => $this->project['id']
			]);
			
			if (!$this->ticket->isEligibleAction($action)) {
				$this->flash('Ticket is not in the required state to undo the action ' . $action);
				return $this->redirect('tickets', 'view', $this->ticket, $this->project);
			}
			
			if ($this->ticket->save([
				'ticket_state' => $this->ticket->queryPreviousState(
					TicketState::getStateByAction($action)
				),
				'handle_id' => null
			])) {
				$this->flash('Ticket reset from action ' . $action);
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
				$ticket->save([
					'needs_attention' => $this->form->getValue('needs_attention')
				]);
				
				if (Comment::create([
					'ticket_id' => $ticket['id'],
					'handle_id' => User::getCurrent()['id'],
					'comment' => $this->form->getValue('text')
				])) {
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
			
			$this->_assignSelectValues();
			
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
						Comment::create([
							'ticket_id' => $ticket['id'],
							'handle_id' => User::getCurrent()['id'],
							'comment' => $this->form->getValue('comment')
						]);
					}
					
					// Create child tickets
					// FIXME: this creates children for all tickets, add additional argument for function
					if ($this->form->getValue('create_recording_tickets')) {
						Ticket::createMissingRecordingTickets(
							$this->project['id']
						);
					}
					
					if ($this->form->getValue('create_encoding_tickets')) {
						Ticket::createMissingEncodingTickets(
							$this->project['id']
						);
					}
					
					return $this->redirect('tickets', 'view', $ticket);
				}
			}
			
			return $this->render('tickets/edit');
		}
		
		public function edit(array $arguments) {
			$this->ticket = Ticket::findByOrThrow([
				'id' => $arguments['id'],
				'project_id' => $this->project['id']
			]);
			
			$this->form();
			
			if ($this->form->wasSubmitted() and $this->ticket->save($this->form->getValues())) {
				if ($this->form->getValue('comment')) {
					Comment::create([
						'ticket_id' => $this->ticket['id'],
						'handle_id' => User::getCurrent()['id'],
						'comment' => $this->form->getValue('comment')
					]);
				}
				
				$this->flash('Ticket updated');
				return $this->redirect('tickets', 'view', $this->ticket, $this->project);
			}
			
			$this->_assignSelectValues();
			
			return $this->render('tickets/edit');
		}
		
		private function _assignSelectValues() {
			$this->states = $this->project
				->States
				->where([
					'ticket_type' => (isset($this->ticket))?
						$this->ticket['ticket_type'] : 'meta'
				]);
			
			$this->users = User::findAll()
				->select('id, name')
				->orderBy('name')
				->indexBy('id', 'name');
			
			if (isset($this->ticket) and
				!empty($this->ticket['encoding_profile_version_id'])) {
				$this->profile = EncodingProfileVersion::findAll(
					['EncodingProfile' => ['select' => 'id, name']]
				)
					->where([
						'id' => $this->ticket['encoding_profile_version_id']
					])
					->select('revision, description')
					->first();
			}
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
				
				$properties = $this->ticket
					->Properties
					->indexBy('name')
					->toArray();
				
				if (isset($properties['Fahrplan.ID'])) {
					$properties['Fahrplan.ID']['value'] =
						$this->form->getValue('fahrplan_id');
				}
				
				$ticket['properties'] = $properties;
				
				if ($ticket->save($this->form->getValues())) {
					if (empty($this->form->getValue('duplicate_recording_ticket'))) {
						Ticket::createMissingRecordingTickets(
							$this->project['id']
						);
					} else {
						$recordingTicket = $this->ticket
							->RecordingTicket
							->duplicate();
						
						
						$recordingTicket->save([
							'parent_id' => $ticket['id'],
							'fahrplan_id' => $ticket['fahrplan_id'],
							'title' => $ticket['title'] . ' (Recording)'
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
		
		/*public function mass_edit(array $arguments) {
			if (empty($arguments['id']) or !$tickets = $this->Ticket->findAll(array(), array('id' => explode(',', $arguments['id'])))) {
				throw new EntryNotFoundException();
			}
			
			if (count($tickets) < 2) {
				return $this->View->redirect('tickets', 'view', $tickets[0] + array('project_id' => $this->Project->id));
			}
			
			if (Request::isPostRequest()) {
				foreach ($tickets as $ticket) {
					$this->Ticket->clear();
					$this->Ticket->id = $ticket['id'];
					
					if (Request::post('assignee') == '–') {
						$this->Ticket->user_id = null;
					} elseif (Request::post('assignee', Request::int)) {
						$this->Ticket->user_id = Request::post('assignee', Request::int);
					}
					
					if (Request::post('priority', Request::float)) {
						$this->Ticket->priority = Request::post('priority', Request::float);
					}
					
					if (Request::post('set_needs_attention', Request::checkbox)) {
						$this->Ticket->needs_attention = Request::post('needs_attention', Request::checkbox);
					}
					
					if (Request::post('set_failed', Request::checkbox)) {
						$this->Ticket->failed = Request::post('failed', Request::checkbox);
					}
					
					$this->Ticket->save();
				}
				
				$this->flash('Tickets updated');
				return $this->View->redirect('tickets', 'index', array('project_slug' => $this->Project->slug));
			}
			
			$this->View->assign('tickets', $tickets);
			$this->View->assign('users', $this->User->getList('name', null, array(), 'role, name'));
			$this->View->assign('types', $this->Type->getList('name'));
			$this->View->assign('states', $this->State->getList('name', array('ticket_type_id' => 1), array(), 'id'));
			
			$this->View->render('tickets/mass_edit');
		}*/
		
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