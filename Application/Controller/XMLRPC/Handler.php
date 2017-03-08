<?php
	
	requires(
		'Controller/XMLRPC',
		'/Model/Handle',
		'/Model/WorkerGroup',
		'/Model/EncodingProfile',
		'/Model/LogEntry',
		'/Model/Ticket',
		'/Model/ProjectTicketState'
	);
	
	class Controller_XMLRPC_Handler extends Controller_XMLRPC {
		
		protected $beforeAction = [
			'authenticate' => true
		];
		
		const XMLRPC_PREFIX = 'C3TT.';
		
		private $virtual_properties = [
			'Encoding.Basename',
			'Project.Slug',
			'EncodingProfile.Basename',
			'EncodingProfile.Extension'
		];
		
		private $_workerGroup;
		private $_assignedProjects = [];
		
		public function __construct() {
			// TODO: move to Controller_XMLRPC
			// set error reporting to suppress notices, since error messages break XML output
			error_reporting(E_ALL & ~ E_NOTICE);
		}

		protected function authenticate($method, array $arguments) {
			if (empty($_GET['group']) or empty($_GET['hostname'])) {
				return $this->_XMLRPCFault(-32500, 'incomplete arguments');
			}
			
			if (!$this->_workerGroup = WorkerGroup::findBy(array('token' => $_GET['group']))) {
				return $this->_XMLRPCFault(-32500, 'worker group not found');
			}
			
			if (count($this->arguments) === 0) {
				return $this->_XMLRPCFault(-32500, 'signature missing');
			}

			$signature = array_pop($this->arguments);
			
			if (!self::_validateSignature($this->_workerGroup['secret'], $signature, array_merge(array(
				$this->Request->getURL(),
				self::XMLRPC_PREFIX . $method,
				$this->_workerGroup['token'],
				$_GET['hostname']),
				$this->arguments))) {
				return $this->_XMLRPCFault(-32500, 'invalid or missing signature');
			}
			
			$name = self::_getNameFromHostName($_GET['hostname']);
			
			// FIXME: this is a dirty fix for a race condition!
			$this->worker = Worker::findAll()
				->where(array(
					'name' => $name,
					'worker_group_id' => $this->_workerGroup['id']
					))
				->orderBy('id DESC')
				->limit(1)
				->first();
			
			if (!$this->worker) {
				$this->worker = Worker::create(array(
					'name' => $name,
					'worker_group_id' => $this->_workerGroup['id']
				));
				if (!$this->worker) {
					// creation may have failed due to race condition, query again
					$this->worker = Worker::findAll()
						->where(array(
							'name' => $name,
							'worker_group_id' => $this->_workerGroup['id']
							))
						->orderBy('id DESC')
						->limit(1)
						->first();
				}
				if (!$this->worker) {
					return $this->_XMLRPCFault(-32500, 'can neither create nor find worker entry');
				}
			}

			$this->worker->touch(['last_seen']);

			// store projects ids of projects assigned to parent worker group
			$this->_assignedProjects = $this->_workerGroup
				->Project
				->where(['read_only' => false])
				->pluck('id');
		}
		
		private static function _validateSignature($secret, $signature, $arguments) {
			$args = array();
			foreach($arguments as $argument) {
				$args[] = (is_array($argument))?
					http_build_query(
						['' => $argument],
						'',
						'&',
						PHP_QUERY_RFC3986
					) :
					rawurlencode($argument);
			}
			
			$hash = hash_hmac(
				'sha256',
				implode('%26', $args),
				$secret
			);
			
			return hash_equals($hash, $signature);
		}

		private static function _getNameFromHostName($hostName) {
			if (filter_var($hostName, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
				return $hostName;
			}
			
			return strstr($hostName . '.', '.', true);
		}
		
		private static function _filterFields(array $data, array $fields)
		{
			return array_intersect_key($data, array_flip($fields));
		}
		
		/**
		* get version string of XMLRPC API
		*
		* @return string version string
		*/
		public function getVersion() {
			return '4.0';
		}

		/**
		 * Get details about the encoding profiles available for given project.
		 *
		 * @param integer $project_id id of project
		 * @param integer $encoding_profile_id get details only for specified profile
		 * @return array profile details
		 * @throws Exception
		 */
		public function getEncodingProfiles($project_id, $encoding_profile_id = null) {
			// handle project
			if(!in_array($project_id, $this->_assignedProjects)) {
				throw new Exception(__FUNCTION__ . ': project not assigned to worker group', 101);
			}
			$project = Project::findOrThrow(['id' => $project_id]);
			
			// check for a specific encoding profile
			if(!empty($encoding_profile_id)) {
				$profile = $project->EncodingProfileVersion->where(['encoding_profile_id' => $encoding_profile_id])->first();
				if(!$profile) {
					throw new Exception(__FUNCTION__ . ': encoding profile is not assigned to the project', 102);
				}
				
				return $profile->toArray();
			}
			
			// list all profiles
			return $project->EncodingProfileVersion->fetchAll();
		}
		
		/**
		 * Filter ticket details for output
		 *
		 * @param object $ticket ticket
		 * @return array filtered ticket info
		 * @throws Exception
		 */
		private function _getTicketInfo($ticket) {
			if(!($ticket instanceof Ticket)) {
				return [];
			}
			
			$ticket_info = self::_filterFields($ticket->toArray(), [
				'id',
				'project_id',
				'fahrplan_id',
				'title',
				'ticket_type',
				'ticket_state',
				'ticket_state_next',
				'failed',
				'progress'
			]);
			
			// generate URL for user interaction
			$ticket_info['url_web'] = $this->Request->getRootURL() .
				Router::reverse('tickets', 'view', $ticket->toArray() + ['project_slug' => $ticket->Project['slug']]);
			
			// add type-dependent detail info
			switch($ticket['ticket_type']) {
				case 'encoding':
					$ticket_info['parent_id'] = $ticket['parent_id'];
					$ticket_info['encoding_profile_id'] = $ticket->EncodingProfileVersion['encoding_profile_id'];
					break;
			}
			
			// recursion: get child ticket info
			foreach($ticket->Children as $childTicket) {
				$ticket_info['children'][] = $this->_getTicketInfo($childTicket);
			}
			
			return $ticket_info;
		}
		
		
		/**
		 * Get ticket details of given ticket id
		 *
		 * @param integer $ticket_id ticket identifier
		 * @return array ticket info
		 * @throws Exception
		 */
		public function getTicketInfo($ticket_id)
		{
			$ticket = Ticket::find(['id' => $ticket_id]);
			if(!$ticket) {
				throw new Exception(__FUNCTION__ . ': ticket not found', 201);
			}
			
			if(!in_array($ticket['project_id'], $this->_assignedProjects)) {
				throw new Exception(__FUNCTION__ . ': ticket in project not assigned to worker group', 202);
			}
			
			return $this->_getTicketInfo($ticket);
		}
		
		/**
		 * Add a new meta ticket
		 *
		 * @param integer $project_id id of project to create ticket in
		 * @param string $title ticket title
		 * @param integer $fahrplan_id external reference ID
		 * @param array $properties ticket properties
		 * @return array ticket data
		 * @throws Exception if ticket cannot be created
		 */
		public function createMetaTicket($project_id, $title, $fahrplan_id, array $properties = [])
		{
			// check project
			if(!in_array($project_id, $this->_assignedProjects)) {
				throw new Exception(__FUNCTION__ . ': project not assigned to worker group', 1001);
			}
			
			// check title
			if(empty($title)) {
				throw new Exception(__FUNCTION__ . ': ticket title is empty', 1002);
			}
			
			// check fahrplan_id
			if(empty($fahrplan_id) || !is_numeric($fahrplan_id)) {
				throw new Exception(__FUNCTION__ . ': fahrplan ID is invalid', 1003);
			}
			// Internal fahrplan id is mandatory, must be unique and can't be changed later
			if(Ticket::exists([ 'project_id' => $project_id, 'fahrplan_id' => $fahrplan_id ])) {
				throw new Exception(__FUNCTION__ . ': ticket with given fahrplan ID already exists', 1004);
			}
			
			$project = Project::find(['id' => $project_id]);
			
			$ticket_data = [
				'ticket_type' => 'meta',
				'project_id' => $project['id'],
				'project_slug' => $project['project_slug'],
				'title' => $title,
				'fahrplan_id' => $fahrplan_id
			];
			
			$first_state = $project->queryFirstState($ticket_data['ticket_type'])->first();
			if(empty($first_state)) {
				throw new Exception(__FUNCTION__ . ': no valid states configured in project for ticket type meta', 1005);
			}
			$ticket_data['ticket_state'] = $first_state['ticket_state'];
			
			// store remaining properties
			$ticket_data['properties'] = array();
			foreach($properties as $name => $value) {
				$ticket_data['properties'][$name] = [
					'name' => $name,
					'value' => $value
				];
			}
			
			try {
				$ticket = Ticket::createOrThrow($ticket_data);
				
				LogEntry::create(array(
					'ticket_id' => $ticket['id'],
					'from_state' => '',
					'to_state' => $ticket['ticket_state'],
					'handle_id' => $this->worker['id'],
					'event' => 'RPC.' . __FUNCTION__,
					'comment' => 'ticket created'));
				
				Ticket::createMissingEncodingTickets(
					$project['id']
				);
				
				return $ticket->toArray();
			} catch(Exception $e) {
				throw new Exception(__FUNCTION__ . ': ticket creation failed. reason: ' . $e->getMessage(), 1006);
			}
		}
		
		/**
		 * Get meta ticket for given fahrplan id
		 *
		 * @param integer $project_id id of project to create ticket in
		 * @param integer $fahrplan_id external reference ID
		 * @return array ticket data
		 * @throws Exception if ticket not found
		 */
		public function getMetaTicketInfo($project_id, $fahrplan_id)
		{
			// check project
			if(!in_array($project_id, $this->_assignedProjects)) {
				throw new Exception(__FUNCTION__ . ': project not assigned to worker group', 1101);
			}
			
			// handle ticket
			$metaTicket = Ticket::find([ 'ticket_type' => 'meta', 'project_id' => $project_id, 'fahrplan_id' => $fahrplan_id ]);
			if(!$metaTicket) {
				throw new EntryNotFoundException(__FUNCTION__ . ': no meta ticket found in this project for given fahrplan id', 1102);
			}
			
			return $this->_getTicketInfo($metaTicket);
		}
		
		/**
		 * Advance ticket state from initial state to first serviceable state.
		 *
		 * @param integer $ticket_id ticket identifier
		 * @return bool true if state successfully advanced
		 * @throws Exception
		 */
		public function setCommenceTicketState($ticket_id) {
			$ticket = Ticket::findBy(['id' => $ticket_id]);
			if(!$ticket) {
				throw new Exception(__FUNCTION__.': ticket not found',1301);
			}

			if(!empty($ticket['handle_id'])) {
				throw new Exception(__FUNCTION__.': ticket is currently assigned',1302);
			}

			if(!in_array($ticket['project_id'],$this->_assignedProjects)) {
				throw new Exception(__FUNCTION__.': ticket in project not assigned to worker group',1303);
			}

			$initial_state = $ticket->Project->queryFirstState($ticket['ticket_type'])->first();
			if($initial_state['ticket_state'] != $ticket['ticket_state']) {
				throw new Exception(__FUNCTION__.': ticket is not in initial state!',1304);
			}
			
			$commenceState = ProjectTicketState::getCommenceState($ticket['project_id'], $ticket['ticket_type']);
			if(!$commenceState) {
				throw new Exception(__FUNCTION__.': ticket has no commence state!',1305);
			}
			
			$log_entry = [
				'ticket_id' => $ticket['id'],
				'from_state' => $ticket['ticket_state'],
				'to_state' => $commenceState,
				'handle_id' => $this->worker['id'],
				'event' => 'RPC.'.__FUNCTION__,
				'comment' => 'Advanced ticket from initial state'
			];

			if(!$save = $ticket->save(['ticket_state' => $commenceState])) {
				Log::warning(__FUNCTION__.': setting to commence state failed');
				return false;
			}

			LogEntry::create($log_entry);
			
			return true;
		}

		/**
		 * Control channel for workers.
		 *
		 * Workers for services are supposed to poll the tracker periodically to notify about there state
		 * and progress. The return value is used to apply commands.
		 *
		 * @param integer $ticket_id of current ticket a worker is working on, empty if none assigned
		 * @param string $log_message optional log message
		 * @return string command to handle by worker, 'OK' if nothing special to do
		 */
		public function ping($ticket_id = null, $log_message = null) {
			// log ping
			$time_since = ($this->worker['last_seen']) ? (new DateTime())->diff(new DateTime($this->worker['last_seen']))->format('%Hh %imin %ss') : 'long long time';
			Log::debug('ping from '.$this->worker['name'].' ('.$this->Request->getRemoteIP().') [last ping '.$time_since." ago]: ticket_id=$ticket_id log_message='$log_message'");
			
			// assume nothing is wrong
			$cmd = 'OK';
			$reason = false;
			
			// return, if worker is idling
			if(!$ticket_id) {
				return $cmd;
			}
			
			$ticket = Ticket::find(['id' => $ticket_id], ['State', 'Handle']);
			if(!$ticket) {
				$reason = 'ticket not found';
			} elseif($ticket['handle_id'] == null) {
				$reason = 'ticket is unassigned';
			} elseif($ticket['handle_id'] != $this->worker['id']) {
				$reason = 'ticket is assigned to other handle: '.$ticket['handle_name'];
			} elseif(!in_array($ticket['project_id'],$this->_assignedProjects)) {
				$reason = 'ticket in project not assigned to worker group';
			} else {
				$state = $ticket->State;
				if(empty($state) || !$state['service_executable']) {
					$reason = 'ticket is in non-service state: '.$ticket['ticket_state'];
				}
			}

			// lose ticket, if an error occurred
			if($reason) {
				$cmd = 'Ticket lost';
				Log::warning('[RPC] ping: '.$reason);
				
				if($ticket) {
					LogEntry::create(array(
						'ticket_id' => $ticket_id,
						'handle_id' => $this->worker['id'],
						'comment' => "Worker received command '$cmd'\n\nReason: $reason",
						'event' => 'RPC.'.__FUNCTION__
					));
				}
			}
			
			return $cmd;
		}
		
		/**
		* Get properties of ticket with given id
		*
		* @param int $ticket_id id of ticket
		* @return array property data
		* @throws Exception if ticket not found
		*/
		public function getTicketProperties($ticket_id) {
			$ticket = Ticket::findOrThrow(['id' => $ticket_id], ['Project']);

			return $ticket
				->MergedProperties
				->indexBy('name', 'value')
				->toArray();
		}
		
		/**
		* Set ticket properties for ticket with given id
		*
		* @param int $ticket_id id of ticket
		* @param array $properties associative array of properties ( key => value )
		* @return true if properties set successfully
		* @throws Exception if ticket not exists
		*/
		public function setTicketProperties($ticket_id, array $properties) {
			if(!$ticket = Ticket::find(['id' => $ticket_id], ['Handle','Parent','Project','Properties'])) {
				throw new EntryNotFoundException(__FUNCTION__.': ticket not found',301);
			}
			if(!is_array($properties) || count($properties) < 1) {
				throw new EntryNotFoundException(__FUNCTION__.': no properties given',302);
			}
			if(!in_array($ticket['project_id'],$this->_assignedProjects)) {
				throw new Exception(__FUNCTION__.': ticket in project not assigned to worker group',303);
			}

			$ticket_properties = array();
			$log_message = array();
			$log_message[] = __FUNCTION__.': changing properties';
			foreach($properties as $name => $value) {
				if(in_array($name,$this->virtual_properties)) {
					Log::warning('[RPC] setTicketProperties: ingored virtual property '.$name);
					continue;
				} elseif($value !== '') {
					$ticket_properties[] = array('name' => $name, 'value' => $value);
					$log_message[] = $name . '=' . $value;
				} else {
					$ticket_properties[] = array('name' => $name, '_destroy' => 1);
					$log_message[] = 'deleting property: ' . $name;
				}
			}
			if($ticket->save(array('properties' => $ticket_properties))) {
				LogEntry::create(array(
					'ticket_id' => $ticket['id'],
					'handle_id' => $this->worker['id'],
					'comment' => implode("\n",$log_message),
					'event' => 'RPC.'.__FUNCTION__
				));
				return true;
			}
			return false;
		}

		/**
		 * Get next unassigned ticket ready to be in state $state after transition.
		 *
		 * First ticket found gets assigned to calling user and state transition to $state is performed.
		 *
		 * @param string $ticketType type of ticket
		 * @param string $ticketState ticket state the returned ticket will be in after this call
		 * @param array $propertyFilters return only tickets matching given properties
		 * @return array ticket data or false if no matching ticket found (or user is halted)
		 * @throws Exception on error
		 */
		public function assignNextUnassignedForState($ticketType = '', $ticketState = '', array $propertyFilters = []) {
			if (empty($ticketType) || empty($ticketState)) {
				throw new EntryNotFoundException(__FUNCTION__.': ticket type or ticket state missing', 401);
			}
			
			if ($this->_workerGroup['paused']) {
				return false;
			}
			
			// create query: find all tickets in state
			$ticket = null;
			try {
				$tickets = Ticket::findAll(['State'])
					->from('view_serviceable_tickets', 'tbl_ticket')
					->where([
						'project_id' => $this->_assignedProjects,
						'ticket_type' => $ticketType,
						'next_state' => $ticketState,
						'next_state_service_executable' => 1,
						'handle_id' => null
					])
					->scoped([
						'virtual_property_filter' => [$propertyFilters]
					])
					->orderBy('ticket_priority(id) DESC');
				
				$this->_workerGroup->filterTickets(
					$this->_assignedProjects,
					$tickets
				);
				
				$ticket = $tickets->first();
			} catch(Exception $e) {
				Log::warning($e->getMessage());
				throw new Exception(__FUNCTION__.': error fetching tickets. suspecting invalid parameters.' .
					sprintf(' (got %s: %s)', get_class($e), $e->getMessage()), 402);
			}
			
			if ($ticket === null) {
				return false;
			}
			
			/* TODO handling abandoned tickets after timeout */
			
			$logEntry = [
				'ticket_id' => $ticket['id'],
				'handle_id' => $this->worker['id'],
				'from_state' => $ticket['ticket_state'],
				'to_state' => $ticket['next_state'],
				'event' => 'RPC.'.__FUNCTION__
			];
			
			$saved = $ticket->save(
				// assign to worker with new state
				[
					'handle_id' => $this->worker['id'],
					'ticket_state' => $ticket['next_state']
				],
				// ensure ticket is not assigned yet and in the right state
				[
					'handle_id' => null,
					'ticket_state' => $ticket['ticket_state']
				]
			);
			
			if (!$saved) {
				Log::warning(__FUNCTION__.': race condition with other request. delaying new request');
				return false;
			}

			LogEntry::create($logEntry);
			
			return $ticket->toArray();
		}

		/**
		 * Get all assigned tickets in state $state
		 *
		 * @param string $ticketType type of ticket
		 * @param string $ticketState ticket state
		 * @param array $propertyFilters return only tickets matching given properties
		 * @return array ticket data or false if no matching ticket found (or user is halted)
		 * @throws Exception on error
		 */
		public function getAssignedForState($ticketType = '', $ticketState = '', array $propertyFilters = []) {
			if (empty($ticketType) || empty($ticketState)) {
				throw new EntryNotFoundException(__FUNCTION__.': ticket type or ticket state missing', 401);
			}

			if ($this->_workerGroup['paused']) {
				return false;
			}

			// create query: find all tickets in state
			$tickets = Ticket::findAll(['State'])
				->from('view_serviceable_tickets', 'tbl_ticket')
				->where([
					'handle_id' => $this->worker['id'],
					'ticket_type' => $ticketType,
					'ticket_state' => $ticketState
				])
				->scoped([
					'virtual_property_filter' => [$propertyFilters]
				]);
			
			$this->_workerGroup->filterTickets(
				$this->_assignedProjects,
				$tickets
			);
			
			return $tickets->toArray();
			
			/*
			// check again if we still need to filter tickets properties
			$tickets_matching = array();
			if(empty($filter_properties)) {
				$tickets_matching = $tickets->toArray();
			} else {
				foreach($tickets as $_ticket) {
					$ticket = $_ticket;
					$properties = $this->getTicketProperties($_ticket['id']);
					foreach($properties as $name => $value) {
						if(array_key_exists($name,$filter_properties) && $filter_properties[$name] != $value) {
							// if property mismatch, invalidate current ticket guess
							$ticket = null;
							break;
						}
					}
					if($ticket) {
						$tickets_matching[] = $ticket->toArray();
					}
				}

			}

			return $tickets_matching;
			*/
		}

		/**
		 * Get all tickets in state $state from projects assigned to the workerGroup, unless workerGroup is halted
		 *
		 * @param string $ticketType
		 * @param string $ticketState
		 * @param array $propertyFilters filter_parameters return only tickets matching given properties
		 * @return array ticket data or false if no matching ticket found (or user is halted)
		 * @throws EntryNotFoundException
		 */
		public function getTicketsForState($ticketType = '', $ticketState = '', array $propertyFilters = []) {
			if (empty($ticketType) || empty($ticketState)) {
				throw new EntryNotFoundException(__FUNCTION__.': ticket type or ticket state missing', 401);
			}

			if ($this->_workerGroup['paused']) {
				return false;
			}

			// create query: find all tickets in state
			$tickets = Ticket::findAll(['State'])
				->from('tbl_ticket')
				->where([
					'project_id' => $this->_assignedProjects,
					'ticket_type' => $ticketType,
					'ticket_state' => $ticketState
				])
				->scoped([
					'virtual_property_filter' => [$propertyFilters]
				]);

			$this->_workerGroup->filterTickets(
				$this->_assignedProjects,
				$tickets
			);

			return $tickets->toArray();
		}

		/**
		 * Unassign ticket and set state to according state after processing by service.
		 *
		 * A log message can be appended.
		 *
		 * @param integer $ticket_id id of ticket
		 * @param string $log_message optional log message
		 * @return boolean true if action was performed successfully
		 * @throws Exception
		 */
		public function setTicketDone($ticket_id, $log_message = null) {
			if(!$ticket = Ticket::findAll(['State'])->where(['id' => $ticket_id])->first()) {
				throw new EntryNotFoundException(__FUNCTION__.': ticket not found or parent in wrong state',501);
			}

			if($ticket['handle_id'] == null) {
				throw new Exception(__FUNCTION__.': ticket not assigned', 502);
			}
			if($ticket['handle_id'] != $this->worker['id']) {
				throw new Exception(__FUNCTION__.': ticket is assigned to other handle: '.$ticket['handle_name'], 503);
			}
			if(!in_array($ticket['project_id'],$this->_assignedProjects)) {
				throw new Exception(__FUNCTION__.': ticket in project not assigned to worker group',504);
			}
			$state = $ticket->State;
			if(empty($state) || !$state['service_executable']) {
				throw new Exception(__FUNCTION__.': ticket is in non-service state: '.$ticket['ticket_state'], 505);
			}
			
			if($ticket['ticket_state_next'] === null) {
				throw new Exception(__FUNCTION__.': no next state available!',506);
			}

			$log_entry = array(
				'ticket_id' => $ticket['id'],
				'handle_id' => $this->worker['id'],
				'from_state' => $ticket['ticket_state'],
				'to_state' => $ticket['ticket_state_next'],
				'event' => 'RPC.'.__FUNCTION__,
				'comment' => $log_message
			);

			if (!$save = $ticket->save(array('handle_id' => null, 'ticket_state' => $ticket['ticket_state_next']))) {
				Log::warning(__FUNCTION__.': race condition with other request. delaying new request');
				return false;
			}

			LogEntry::create($log_entry);

			return true;
		}

		/**
		 * Unassign ticket and set "failed" flag.
		 *
		 * A log message can be appended.
		 *
		 * @param integer $ticket_id id of ticket
		 * @param string $log_message optional log message
		 * @return boolean true if action was performed successfully
		 * @throws Exception
		 */
		public function setTicketFailed($ticket_id, $log_message = null) {
			if(!$ticket = Ticket::find(['id' => $ticket_id])) {
				throw new EntryNotFoundException(__FUNCTION__.': ticket not found',601);
			}

			if($ticket['handle_id'] == null) {
				throw new Exception(__FUNCTION__.': ticket not assigned', 602);
			}
			if($ticket['handle_id'] != $this->worker['id']) {
				throw new Exception(__FUNCTION__.': ticket is assigned to other handle: '.$ticket['handle_name'], 603);
			}
			if(!in_array($ticket['project_id'],$this->_assignedProjects)) {
				throw new Exception(__FUNCTION__.': ticket in project not assigned to worker group', 604);
			}
			$state = $ticket->State;
			if(empty($state) || !$state['service_executable']) {
				throw new Exception(__FUNCTION__.': ticket is in non-service state: '.$ticket['ticket_state'], 605);
			}

			$log_entry = array(
				'ticket_id' => $ticket['id'],
				'handle_id' => $this->worker['id'],
				'from_state' => $ticket['ticket_state'],
				'event' => 'RPC.'.__FUNCTION__,
				'comment' => $log_message
			);

			if (!$save = $ticket->save(array('handle_id' => null, 'failed' => true))) {
				Log::warning(__FUNCTION__.': race condition with other request. delaying new request');
				return false;
			}

			LogEntry::create($log_entry);

			return true;
		}

		/**
		 * Render job file for master.pl encoding scripts
		 *
		 * @param integer $ticket_id id of ticket
		 * @return boolean true if action was performed successfully
		 * @throws Exception
		 */
		public function getJobfile($ticket_id) {
			$properties = $this->getTicketProperties($ticket_id);

			// get encoding profile
			if(!$profileVersion = Ticket::findBy(array('id' => $ticket_id))->EncodingProfileVersion) {
				throw new EntryNotFoundException(__FUNCTION__.': encoding profile not found',702);
			}

			return $profileVersion->getJobfile($properties);
		}
		
		/**
		* Add a log message regarding the ticket with given id
		*
		* @param int $ticket_id id of ticket
		* @param string $log_message comment text for log message
		* @return boolean true if comment saved successfully
		* @throws Exception if ticket not found
		*/
		public function addLog($ticket_id, $log_message) {
			if(!$ticket = Ticket::find(['id' => $ticket_id])) {
				throw new EntryNotFoundException(__FUNCTION__.': ticket not found',801);
			}

			return LogEntry::create(array(
				'ticket_id' => $ticket_id,
				'handle_id' => $this->worker['id'],
				'comment' => $log_message,
				'event' => 'RPC.'.__FUNCTION__
			)) !== false;
		}
		
		/**
		 * Return a list of projects assigned to the current worker group
		 *
		 * @return array list of project IDs serviceable and assigned to current worker group
		 */
		public function getServiceableProjects() {
			return $this->_assignedProjects;
		}
	}
