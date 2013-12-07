<?php
	
	requires(
		'String',
		'/Controller/Application',
		'/Model/Ticket'
	);
	
	class Controller_Tickets extends Controller_Application {
    	
		public $requireAuthorization = true;
		
		/*
		public $beforeFilter = true;
		
		private static $_searchMapping = array('title' => 'title', 'assignee' => 'user_id', 'type' => 'type_id', 'state' => 'state_id', 'encoding_profile' => 'encoding_profile_id', 'fahrplan_id' => 'fahrplan_id');
		
		public function beforeFilter($arguments, $action) {
			if ($this->Project->read_only and !in_array($action, array('index', 'view', 'log', 'export', 'export_wiki', 'export_podcast'))) {
				$this->flash('You can\'t alter tickets in this project because it\'s locked');
				$this->View->redirect('tickets', 'index', array('project_slug' => $this->Project->slug));
				return false;
			}
			
			return true;
		}
		*/
		
		public function index() {
			// TODO: join encoding profile?
			$this->tickets = Ticket::findAll([])
				->withDefaultProperties();
			
			/*
			$tickets = $this->Ticket->getAsTable(array('project_id' => $this->Project->id));
			
			if (Request::get('t')) {
				switch (Request::get('t')) {
					case 'recording':
						$tickets->where('state_id IN (? , ? , ? , ? , ?) AND parent_id IS NULL', $this->State->getIdsByName(array('locked', 'scheduled', 'recording', 'recorded', 'merging')));
						break;
					case 'cutting':
						$tickets->where('state_id IN (? , ? , ? , ?) AND parent_id IS NULL', $this->State->getIdsByName(array('merged', 'cutting', 'fixing', 'cut')));
						break;
					case 'encoding':
						$tickets->join('tbl_ticket', '', 'parent_id = tbl_ticket.id AND type_id = ?', array(2), 'LEFT');
						$tickets->join('tbl_ticket', '', 'id = tbl_ticket.parent_id', array(), 'LEFT');
						$tickets->join('tbl_encoding_profile', '', 'id = tbl_ticket_2.encoding_profile_id', array(), 'LEFT');
						$tickets->where('
							((state_id IN (? , ? , ? , ?) AND tbl_encoding_profile_2.approved) OR
							(tbl_ticket_2.state_id IN (? , ? , ? , ?) AND tbl_encoding_profile.approved)) OR
							(parent_id IS NOT NULL AND type_id = ? AND state_id != ? AND tbl_ticket_3.state_id != ?) OR
							(parent_id IS NULL AND state_id != ? AND tbl_ticket_2.state_id != ?)', array_merge($this->State->getIdsByName(array(
								'ready to encode', 'encoding', 'encoded', 'tagging',
								'ready to encode', 'encoding', 'encoded', 'tagging'
							)),
							array(2),
							$this->State->getIdsByName(array( // TODO: this part of the query needs enhancement
								'material needed', 'copied',
								'copied', 'material needed'
							))
						));
						break;
					case 'releasing':
						$states = $this->State->getIdsByName(array('tagged', 'checking', 'checked', 'postprocessing', 'postprocessed', 'ready to release'));
						$tickets->join('tbl_ticket', '', 'parent_id = tbl_ticket.id', array(), 'LEFT');
						$tickets->where('state_id IN (? , ? , ? , ? , ? , ?) OR tbl_ticket_2.state_id IN (? , ? , ? , ? , ? , ?)', array_merge($states, $states));
						break;
				}
			} else {
				if (Request::get('u', Request::int)) {
					$tickets->join('tbl_ticket', '', 'parent_id = tbl_ticket.id', array(), 'LEFT');
					$tickets->where('user_id = ? OR tbl_ticket_2.user_id = ?', array(Request::get('u', Request::int), Request::get('u', Request::int)));
				}
			}
			
			if ($query = Request::get('q')) {
				if (mb_strlen($query) == 4 and ctype_digit($query)) {
					$tickets->where(array('fahrplan_id' => Request::get('q', Request::int)));
				} else {
					$tickets->where('title ILIKE ?', array('%' . Request::get('q') . '%'));
				}
			}
			
			// TODO: move this to Model_Searchable or Database_Query_Search
			if (Request::exists(Request::get, 'search')) {
				$fields = Request::post('fields');
				$operators = Request::post('operators');
				$values = Request::post('values');
				
				if ($fields and $operators and $values and count($fields) == count($operators) and count($fields) == count($values)) {
					$tickets->join('tbl_ticket', '', 'parent_id = tbl_ticket.id', array(), 'LEFT');
					
					foreach ($fields as $i => $key) {
						$condition = '';
						$params = array();
						
						if (empty($operators[$i]) or empty($values[$i])) {
							continue;
						}
						
						if (!isset(self::$_searchMapping[$key])) {
							continue;
						}
						
						switch ($operators[$i]) {
							case 'is':
								$condition = self::$_searchMapping[$key] . ' = ?';
								$params[] = $values[$i];
								break;
							case 'is_not':
								$condition = self::$_searchMapping[$key] . ' != ?';
								$params[] = $values[$i];
								break;
							case 'is_in':
							case 'is_not_in':
								$parts = explode(',', $values[$i]);
								$condition = self::$_searchMapping[$key] . (($operators[$i] == 'is_not_in')? 'NOT ' : '') . ' IN (' . substr(str_repeat('? , ', count($parts)), 0, -3) . ')';
								
								foreach ($parts as $part) {
									$params[] = trim($part);
								}
								break;
							case 'contains':
								$condition = self::$_searchMapping[$key] . ' ILIKE ?';
								$params[] = '%' . $values[$i] . '%';
								break;
							case 'begins_with':
								$condition = self::$_searchMapping[$key] . ' ILIKE ?';
								$params[] = $values[$i] . '%';
								break;
							case 'ends_with':
								$condition = self::$_searchMapping[$key] . ' ILIKE ?';
								$params[] = '%' . $values[$i];
								break;
							default:
								continue 2;
						}
						
						switch ($key) {
							case 'state':
								if ($this->State->getTypeById($values[$i]) == 1) {
									$condition .= ' AND type_id = ?';
									$params[] .= 1;
									break;
								} else {
									$condition = '(' . $condition . ' AND type_id != ?) OR tbl_ticket_2.' . $condition;
									$params[] .= 1;
									$params[] .= $values[$i];
									break;
								}
							case 'type':
							case 'encoding_profile':
								$condition .= ' OR tbl_ticket_2.' . $condition;
								$params = array_merge($params, $params);
								break;
						}
						
						$tickets->where($condition, $params);
					}
				}
				
				$this->View->assign('types', $this->Type->getList('name'));
				$this->View->assign('states', Model::groupByField($this->State->findAll(array()), 'ticket_type_id'));
				$this->View->assign('profiles', Model::indexByField($this->EncodingProfile->findAll(array(), array('project_id' => $this->Project->id), array(), null, null, 'id, name'), 'id', 'name'));
				$this->View->assign('users', $this->User->getList('name', null, array(), 'role, name'));
			}
			
			$tickets = $this->Ticket->findBySQL($tickets, array(), array('User', 'State', 'EncodingProfile'));
			
			if (is_array($tickets)) {
				$tickets = Ticket::sortByFahrplanStart($tickets);
			}
			
			if (Request::isPostRequest() and Request::post('edit')) {
				return $this->View->redirect('tickets', 'edit', array('project_slug' => $this->Project->slug, 'id' => implode(Model::indexByField($tickets,'id', 'id'), ',')));
			}
			
			$this->View->assign('tickets', $tickets);
			*/
			/*if ($this->View->respondTo('json')) {
				$this->render('tickets/table.tpl');
			} else {*/
			
			return $this->render('tickets/index.tpl');
		}
		
		public function view(array $arguments = array()) {
			if (!$this->ticket = Ticket::findBy(['fahrplan_id' => $arguments['fahrplan_id'], 'project_id' => $this->project['id']], [], ['User'])) {
				throw new EntryNotFoundException();
			}
			
			$this->commentForm = $this->form('tickets', 'comment', $this->project->toArray() + $this->ticket->toArray());
			
			$this->parent = $this->ticket->Parent;
			$this->children = $this->ticket->Children;
			$this->children->fetch();
			
			$this->properties = $this->ticket->Properties;
			
			/*
			if (empty($arguments['id']) or !$ticket = $this->Ticket->find($arguments['id'], array('User', 'State'), array('project_id' => $this->Project->id))) {
				throw new EntryNotFoundException();
			}
			
			if (!empty($ticket['parent_id'])) {
				$this->View->assign('parent', $this->Ticket->getParent($ticket['parent_id']));
			} else {
				$this->View->assign('children', $this->Ticket->getChildren($ticket['id']));
			}
			
			$properties = $this->Properties->findByObjectWithRoot($ticket['id']);
			
			if (!empty($properties)) {
				$this->View->assign('properties', Model::groupByField($properties, 'root'));
			}
			
			$this->View->assign('ticket', $ticket);
			$this->View->assign('timeline', $this->Ticket->getTimeline($ticket['id']));
			$this->View->render('tickets/view.tpl');
			*/
			
			return $this->render('tickets/view.tpl');
		}
		/*
		public function log($arguments = array()) {
			if (empty($arguments['entry']) or !$log = $this->LogEntry->find($arguments['entry'], array())) {
				throw new EntryNotFoundException();
			}
			
			if ($arguments['id'] != $log['ticket_id'] or !$this->Ticket->find($arguments['id'], array(), array('project_id' => $this->Project->id), array(), null, 'id')) {
				throw new EntryNotFoundException();
			}
			
			$this->View->contentType('text/plain', true);
			$this->View->output($log['comment']);
		}
		*/
		public function feed() {
			
			/*
			$conditions = null;
			$params = array();
			
			if (Request::get('before', Request::int)) {
				$conditions = 'id < ?';
				$params[] = Request::get('before', Request::int);
			} elseif (Request::get('after', Request::int)) {
				$conditions = 'id > ?';
				$params[] = Request::get('after', Request::int);
			}
			
			$log = $this->LogEntry->findByProjectId($this->Project->id, array('User', 'Comment'), $conditions, $params);
			
			$entries = array();
			$entryIndex = -1;
			
			if (!empty($log)) {
				foreach ($log as $entry) {
					if (!isset($entries[$entryIndex])) {
						$entryIndex++;
						$entries[$entryIndex] = $entry;
						continue;
					}
					
					if (
						$entries[$entryIndex]['event'] !== $entry['event']
						or $entries[$entryIndex]['from_state_id'] !== $entry['from_state_id']
						or $entries[$entryIndex]['to_state_id'] !== $entry['to_state_id']
						or $entries[$entryIndex]['event'] == 'RPC.Ping.Command'
						or $entries[$entryIndex]['event'] == 'Comment.Add'
					) {
						$entryIndex++;
						$entries[$entryIndex] = $entry;
						continue;
					}
				
					if (!isset($entries[$entryIndex]['children'])) {
						$entries[$entryIndex]['children'] = array();
					}
				
					$entries[$entryIndex]['children'][] = $entry;
				}
			}
			*/
			/*
			if ($numberOfEntries > 0) {
				$lastEntry = &$log[0];
				$lastEntry['tickets'] = array($lastEntry['ticket_fahrplan_id']);
				
				for ($entry = 1; $entry < $numberOfEntries; $entry++) {
					if ($lastEntry['event'] !== $log[$entry]['event']
						or $lastEntry['from_state_id'] !== $log[$entry]['from_state_id']
						or $lastEntry['to_state_id'] !== $log[$entry]['to_state_id']) {
						$lastEntry = &$log[$entry];
						$lastEntry['tickets'] = array($lastEntry['ticket_fahrplan_id']);
						continue;
					}
					
					$lastEntry['tickets'][] = $log[$entry]['ticket_fahrplan_id'];
					
					unset($log[$entry]);
				}
			}
			*/
			
			// var_dump($log);
			/*
			$this->View->assign('log', $entries);
			$this->View->assign('messages', Model::indexByField($this->LogMessage->findAll(array(), null, array(), null, null, 'event, feed_message, feed_message_multiple, feed_include_log'), 'event'));
			$this->View->assign('stats', array(
				'cutting' => $this->Ticket->getRows(array('state_id' => $this->State->getIdByName('merged'), 'project_id' => $this->Project->id)),
				'checking' => $this->Ticket->getRows(array('state_id' => $this->State->getIdByName('tagged'), 'project_id' => $this->Project->id)),
				'fixing' => $this->Ticket->getRows(array('failed' => true, 'project_id' => $this->Project->id)),
			));
			$this->View->assign('progress', $this->Ticket->getProgress(array('project_id' => $this->Project->id)));
			*/
			
			$this->stats = array(
				'cutting' => 0,
				'checking' => 0,
				'fixing' => 0
			);
			
			return $this->render('tickets/feed.tpl');
		}
		/*
		public function cut(array $arguments = array()) {
			$this->_action('cut', $arguments);
		}
		
		public function uncut(array $arguments = array()) {
			$this->_undoAction('cut', $arguments);
		}
		
		public function check(array $arguments = array()) {
			$this->_action('check', $arguments);
		}
		
		public function uncheck(array $arguments = array()) {
			$this->_undoAction('check', $arguments);
		}
		
		public function fix(array $arguments = array()) {
			$this->_action('fix', $arguments);
		}
		
		public function unfix(array $arguments = array()) {
			$this->_undoAction('fix', $arguments);
		}
		
		public function handle(array $arguments = array()) {
			$this->_action('handle', $arguments);
		}
		
		public function unhandle(array $arguments = array()) {
			$this->_undoAction('handle', $arguments);
		}
		
		private function _action($action, array $arguments = array()) {
			if (empty($arguments['id']) or !$ticket = $this->Ticket->find($arguments['id'], array('User', 'State'), array('project_id' => $this->Project->id))) {
				throw new EntryNotFoundException();
			}
			
			if (!($states = $this->State->getAction($action, $ticket))) {
				$this->flash('Ticket is not in the required state to execute this action');
				return $this->_redirectWithReferer($ticket);
			}
			
			if (Request::isPostRequest()) {
				if (Request::post('appropriate')) {
					$this->Ticket->user_id = $ticket['user_id'] = $this->User->get('id');
					
					if ($this->Ticket->save()) {
						$this->flashNow('The ticket is now assigned to you');
					}					
				} elseif (!Request::post('language') and $action == 'cut' and count($this->Project->languages) > 0 and !Request::post('failed', Request::checkbox) and !Request::post('expand', Request::checkbox)) {
					$this->flashNow('You have to choose the language of the talk');
				} else {
					
					if (Request::post('reset', Request::checkbox) and !empty($states['reset'])) {
						if (!empty($ticket['parent_id'])) {
							$this->Ticket->resetRecordingTask($ticket['parent_id']);
						}
						
						return $this->_redirectWithReferer($ticket);
					} elseif (Request::post('expand', Request::checkbox) and $action == 'cut') {
						$this->Ticket->expandRecordingTask($ticket['id'], Request::post('expand_by', Request::int));
						
						$this->LogEntry->create(array(
							'ticket_id' => $this->Ticket->id,
							'event' => 'Action.' . mb_ucfirst($action) . '.Expand'
						));
						
						return $this->_redirectWithReferer($ticket);
					} elseif (Request::post('failed', Request::checkbox) and isset($states['failed'])) {
						$this->Ticket->state_id = $states['failed'];
						$this->Ticket->user_id = null;
						$this->Ticket->failed = $states['to_failed'];
					} else {
						$this->Ticket->failed = false;
						$this->Ticket->user_id = null;
						$this->Ticket->state_id = $states['to'];
						
						if ($action == 'cut') {
							if (count($this->Project->languages) > 0) {
								$this->Properties->save(array(
									'ticket_id' => $ticket['id'],
									'Record.Language' => Request::post('language')
								));
							}
							
							if (Request::post('delay', Request::checkbox)) {
								$this->Properties->save(array(
									'ticket_id' => $ticket['id'],
									'Record.AVDelay' => Properties::millisecondsToDelay(Request::post('delay_by', Request::int))
								));
							}							
						}
												
						if ($action == 'fix' and Request::post('replacement')) {
							$this->Properties->save(array('ticket_id' => $ticket['id'], 'Record.SourceReplacement' => Request::post('replacement')));
						}
					}
					
					if (Request::post('comment', Request::unfiltered)) {
						$this->Comment->create(array(
							'ticket_id' => $ticket['id'],
							'user_id' => $this->User->get('id'),
							'comment' => Request::post('comment', Request::unfiltered),
							'user_set_failed' => $this->Ticket->failed
						));
					}
					
					if ($this->Ticket->save()) {
						$this->LogEntry->create(array(
							'ticket_id' => $this->Ticket->id,
							'from_state_id' => $ticket['state_id'],
							'to_state_id' => $states['to'],
							'event' => 'Action.' . mb_ucfirst($action) . ((Request::post('failed', Request::checkbox) and isset($states['failed']))? '.Failed' : ''),
							'comment_id' => (Request::post('comment', Request::unfiltered))? $this->Comment->id : null
						));
						
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
			} else {
				if ($ticket['state_id'] != $states['state']) {
					$ticket['user_id'] = $this->User->get('id');
					
					$this->Ticket->failed = false;
					$this->Ticket->state_id = $ticket['state_id'] = $states['state'];
					$this->Ticket->user_id = $this->User->get('id');
					$this->Ticket->save();
				}
			}
			
			$properties = $this->Properties->findByObjectWithRoot($ticket['id']);
			
			if (!is_array($properties)) {
				$properties = array();
			}
			
			if (isset($ticket['parent_id'])) {
				$parent = $this->Properties->findByObjectWithRoot($ticket['parent_id']);
				
				if (!empty($parent)) {
					$properties = array_merge($properties, $parent);
				}
			}
			
			$this->View->assign('properties', Model::groupByField($properties, 'root'));
			$this->View->assign('action', $action);
			$this->View->assign('state', $this->State->getNameById($states['state']));
			
			$this->View->assign('ticket', $ticket);
			$this->View->assign('timeline', $this->Ticket->getTimeline($ticket['id']));
			$this->View->render('tickets/view.tpl');
		}
		
		private function _undoAction($action, array $arguments = array()) {
			if (empty($arguments['id']) or !$ticket = $this->Ticket->find($arguments['id'], array('User'), array('project_id' => $this->Project->id))) {
				throw new EntryNotFoundException();
			}
			
			if (!$states = $this->State->getAction($action, $ticket)) {
				$this->flash('Ticket is not in the required state to undo this action');
				return $this->_redirectWithReferer($ticket);
			}
			
			$this->Ticket->state_id = $states['from'];
			$this->Ticket->user_id = null;
			
			if ($states['from_failed']) {
				$this->Ticket->failed = true;
			}
			
			$this->Ticket->save();
			
			$this->_redirectWithReferer($ticket);
		}
		
		public function reset($arguments = array()) {
			if (empty($arguments['id']) or !$ticket = $this->Ticket->find($arguments['id'], array(), array('project_id' => $this->Project->id))) {
				throw new EntryNotFoundException();
			}
			
			if ($this->Ticket->resetEncodingTask($ticket['id'])) {
				$this->flash('Encoding task resetted');
			}
			
			$this->_redirectWithReferer($ticket);
		}*/
		
		public function comment(array $arguments = array()) {
			if (empty($arguments['id']) or !$ticket = $this->Ticket->find($arguments['id'], array(), array('project_id' => $this->Project->id))) {
				throw new EntryNotFoundException();
			}
			
			if (Request::isPostRequest()) {
				$this->Comment->ticket_id = $ticket['id'];
				$this->Comment->user_id = $this->User->get('id');
				$this->Comment->comment = Request::post('text', Request::unfiltered);
				$this->Comment->user_set_needs_attention = Request::post('needs_attention', Request::checkbox);
				
				if (Request::post('needs_attention', Request::checkbox) xor $ticket['needs_attention']) {
					$this->Ticket->needs_attention = (boolean) Request::post('needs_attention', Request::checkbox);
					$this->Ticket->save();
				}
				
				if ($this->Comment->save()) {
					$this->LogEntry->create(array(
						'ticket_id' => $ticket['id'],
						'comment_id' => $this->Comment->id,
						'event' => 'Comment.Add'
					));
					
					$this->flash('Comment added');
				}
			}
			
			return $this->View->redirect('tickets', 'view', $ticket + array('project_slug' => $this->Project->slug));
		}
		
		/*
		public function delete_comment(array $arguments = array()) {
			if (empty($arguments['ticket_id']) or !$ticket = $this->Ticket->find($arguments['ticket_id'], array(), array('project_id' => $this->Project->id))) {
				throw new EntryNotFoundException();
			}
			
			// TODO: isOwner?
			
			if ($this->Comment->delete($arguments['id'])) { // TODO: check if comment belongs to ticket
				$this->flash('Comment deleted');
			}
			
			return $this->View->redirect('tickets', 'view', $ticket + array('project_slug' => $this->Project->slug));
		}
		
		public function create() {
			// TODO: check if encoding_profile_id is set for tickets with type_id = 2
			//       perhaps we have do set a custom validation in model 
			if (Request::isPostRequest()) {
				$this->Ticket->project_id = $this->Project->id;
				
				// temporary fix
				$this->Ticket->fahrplan_id = 0;
				
				$this->Ticket->title = Request::post('title');
				$this->Ticket->slug = Request::post('title');
				$this->Ticket->priority = Request::post('priority', Request::float);
				
				$this->Ticket->needs_attention = Request::post('needs_attention', Request::checkbox);
				
				$this->Ticket->type_id = $this->State->getTypeById(Request::post('state', Request::int));
				$this->Ticket->state_id = Request::post('state', Request::int);
				$this->Ticket->failed = Request::post('failed', Request::checkbox);
				
				if ($this->Ticket->type_id == 2) {
					$this->Ticket->encoding_profile_id = Request::post('encoding_profile', Request::int);
				}
				
				if (Request::post('comment', Request::unfiltered)) {
					$this->Comment->create(array(
						'ticket_id' => $ticket['id'],
						'user_id' => $this->User->get('id'),
						'comment' => Request::post('comment', Request::unfiltered),
						'user_set_needs_attention' => Request::post('needs_attention', Request::checkbox),
						'user_set_failed' => Request::post('failed', Request::checkbox),
					));
				}
				
				if ($this->Ticket->type_id != 3 and !User::isAllowed('tickets', 'create_all')) {
					$this->flash('You are not allowed to create a ticket of this type', View::flashError);
					return $this->View->redirect('tracker', 'index', array('project_slug' => $this->Project->slug));
				}
				
				if (Request::post('assignee', Request::int)) {
					$this->Ticket->user_id = Request::post('assignee', Request::int);
				}
				
				if (Request::post('parent', Request::int)) {
					$this->Ticket->parent_id = Request::post('parent', Request::int);
				}
				
				
				if ($this->Ticket->save()) {
					$this->Properties->update($this->Ticket->id, Request::post('property_name'), Request::post('property_value'));
					// TODO: set Fahrplan.ID as fahrplan_id
					
					$this->flash('Ticket created');
					return $this->View->redirect('tickets', 'index', array('project_slug' => $this->Project->slug));
				}
			}
			
			$this->View->assign('types', $this->Type->getList('name'));
			$this->View->assign('profiles', Model::indexByField($this->EncodingProfile->findAll(array(), array('project_id' => $this->Project->id), array(), null, null, 'id, name'), 'id', 'name'));
			// TODO: perhaps order by name instead of vid?
			$this->View->assign('tickets', $this->Ticket->findAll(array(), array('project_id' => $this->Project->id, 'parent_id IS NULL'), array(), 'fahrplan_id', null, 'id, type_id, fahrplan_id, title'));
			$this->View->assign('states', Model::groupByField($this->State->findAll(array(), (User::isAllowed('tickets', 'create_all'))? array() : array('ticket_type_id' => 3), array(), 'id'), 'ticket_type_id'));
			$this->View->assign('users', $this->User->getList('name', null, array(), 'role, name'));
			
			$this->View->render('tickets/edit.tpl');
		}
		
		public function edit(array $arguments = array()) {
			if (empty($arguments['id'])) {
				throw new EntryNotFoundException();
			}
			
			if (mb_strpos($arguments['id'], ',')) {
				return $this->mass_edit($arguments);
			}
			
			if (!$ticket = $this->Ticket->find($arguments['id'], array(), array('project_id' => $this->Project->id))) {
				throw new EntryNotFoundException();
			}
			
			if (Request::isPostRequest()) {
				$this->Ticket->title = Request::post('title', Request::unfiltered);
				// $this->Ticket->slug = Request::post('title');
				$this->Ticket->priority = Request::post('priority', Request::float);
				
				if ($ticket['type_id'] == 2) {
					$this->Ticket->encoding_profile_id = Request::post('encoding_profile', Request::int);
				}
				
				$this->Ticket->needs_attention = Request::post('needs_attention', Request::checkbox);
				
				$this->Ticket->type_id = $this->State->getTypeById(Request::post('state', Request::int));
				$this->Ticket->state_id = Request::post('state', Request::int);
				$this->Ticket->failed = Request::post('failed', Request::checkbox);
				
				if (Request::post('comment', Request::unfiltered)) {
					$this->Comment->create(array(
						'ticket_id' => $ticket['id'],
						'user_id' => $this->User->get('id'),
						'comment' => Request::post('comment', Request::unfiltered),
						'user_set_needs_attention' => Request::post('needs_attention', Request::checkbox),
						'user_set_failed' => Request::post('failed', Request::checkbox),
					));
				}
				
				if (Request::post('assignee', Request::int)) {
					$this->Ticket->user_id = Request::post('assignee', Request::int);
				} else {
					$this->Ticket->user_id = null;
				}
				
				if (Request::post('parent', Request::int)) {
					$this->Ticket->parent_id = Request::post('parent', Request::int);
				} else {
					$this->Ticket->parent_id = null;
				}
				
				$this->Properties->update($ticket['id'], Request::post('property_name'), Request::post('property_value'), Request::post('properties'));
				
				if ($this->Ticket->save()) {
					$this->flash('Ticket updated');
					return $this->_redirectWithReferer($ticket);
				}
			}
			
			$this->View->assign('ticket', $ticket);
			$this->View->assign('properties', $this->Properties->findByObject($ticket['id']));
			$this->View->assign('profiles', Model::indexByField($this->EncodingProfile->findAll(array(), array('project_id' => $this->Project->id), array(), null, null, 'id, name'), 'id', 'name'));
			$this->View->assign('types', $this->Type->getList('name'));
			$this->View->assign('tickets', $this->Ticket->findAll(array(), array('project_id' => $this->Project->id, 'parent_id IS NULL'), array(), 'fahrplan_id', null, 'id, type_id, fahrplan_id, title'));
			$this->View->assign('states', $this->State->getList('name', array('ticket_type_id' => $ticket['type_id']), array(), 'id'));
			$this->View->assign('users', $this->User->getList('name', null, array(), 'role, name'));
			
			$this->View->render('tickets/edit.tpl');
		}
		
		public function mass_edit(array $arguments = array()) {
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
			
			$this->View->render('tickets/mass_edit.tpl');
		}
		
		public function delete(array $arguments = array()) {
			if (!empty($arguments) and $this->Ticket->delete($arguments['id'], array('project_id' => $this->Project->id))) {
				$this->flash('Ticket deleted');
			}
 			
			return $this->View->redirect('tickets', 'index', array('project_slug' => $this->Project->slug));
		}
		
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