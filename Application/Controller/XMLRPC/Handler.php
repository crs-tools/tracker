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
		
		private $_projects = [];
		
		public function __construct() {
			// TODO: move to Controller_XMLRPC
			// set error reporting to suppress notices, since error messages break XML output
			error_reporting(E_ALL & ~ E_NOTICE);
		}

		protected function authenticate($method, array $arguments) {
			if (empty($_GET['group']) or empty($_GET['hostname'])) {
				return $this->_XMLRPCFault(-32500, 'incomplete arguments');
			}
			
			if (!$group = WorkerGroup::findBy(array('token' => $_GET['group']))) {
				return $this->_XMLRPCFault(-32500, 'worker group not found');
			}

			$signature = array_pop($this->arguments);
			if (!self::_validateSignature($group['secret'], $signature, array_merge(array(
				$this->Request->getURL(),
				self::XMLRPC_PREFIX . $method,
				$group['token'],
				$_GET['hostname']),
				$this->arguments))) {
				return $this->_XMLRPCFault(-32500, 'invalid or missing signature');
			}
			
			$name = self::_getNameFromHostName($_GET['hostname']);
			
			if (!$this->worker = Worker::findBy(array('name' => $name))) {
				$this->worker = Worker::create(array(
					'name' => $name,
					'worker_group_id' => $group['id']
				));
			} else {
				if ($this->worker['worker_group_id'] !== $group['id']) {
					// update group id, if mismatching with group related to given credentials
					$this->worker->save(['worker_group_id' => $group['id']]);
				}
				
				$this->worker->touch(['last_seen']);
			}

			// store projects ids of projects assigned to parent worker group
			$this->_assignedProjects = $group
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
						null,
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
			
			// TODO: replace with hash_equals from PHP 5.6 on
			return str_hash_compare($hash, $signature);
		}

		private static function _getNameFromHostName($hostName) {
			if (filter_var($hostName, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
				return $hostName;
			}
			
			return strstr($hostName . '.', '.', true);
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
		 * Get details about the encoding profiles available for this project.
		 *
		 * @param integer encoding_profile_id get details only for specified profile
		 * @return array profile details
		 */
		public function getEncodingProfiles($encoding_profile_id = null) {
			if(!empty($encoding_profile_id)) {
				$profiles = array(EncodingProfile::findBy(array('id' => $encoding_profile_id))->toArray());
			} else {
				$profiles = EncodingProfile::findAll()->toArray();
			}
			return is_array($profiles) ? $profiles : array();
		}

		/**
		 * Get consecutive ticket state of given ticket type and ticket state available for this project.
		 *
		 * @param integer project_id project identifier
		 * @param string ticket_type type of ticket (meta, recording, encoding, ingest)
		 * @param string ticket_state ticket state to find successor of
		 * @return array ticket state
		 */
		public function getNextState($project_id, $ticket_type, $ticket_state) {
			return ProjectTicketState::getNextState($project_id, $ticket_type, $ticket_state);
		}

		/**
		 * Get preceding ticket state of given ticket type and ticket state available for this project.
		 *
		 * @param integer project_id project identifier
		 * @param string ticket_type type of ticket (meta, recording, encoding, ingest)
		 * @param string ticket_state ticket state to find predecessor of
		 * @return array ticket state
		 */
		public function getPreviousState($project_id, $ticket_type, $ticket_state) {
			return ProjectTicketState::getPreviousState($project_id, $ticket_type, $ticket_state);
		}

		/**
		 * Get consecutive ticket state of given ticket
		 *
		 * @param integer ticket_id ticket identifier
		 * @return array ticket state
		 */
		public function getTicketNextState($ticket_id) {
			return Ticket::findBy(['id' => $ticket_id])
				->queryNextState()
				->fetchRow();
		}

		/**
		 * Set ticket state of given ticket to consecutive state, if allowed
		 *
		 * (Maybe deprecated)
		 * @param integer ticket_id ticket identifier
		 * @param string log_message optional log message
		 * @return bool true if state successfully advanced
		 * @throws Exception
		 */
		public function setTicketNextState($ticket_id, $log_message = '') {
			$ticket = Ticket::findBy(['id' => $ticket_id]);
			if(!$ticket) {
				throw new Exception(__FUNCTION__.': ticket not found',101);
			}

			if(empty($ticket['handle_id']) || $ticket['handle_id'] != $this->worker['id']) {
				throw new Exception(__FUNCTION__.': ticket is not assigned to you',102);
			}

			if(!in_array($ticket['project_id'],$this->_assignedProjects)) {
				throw new Exception(__FUNCTION__.': ticket in project not assigned to worker group',103);
			}

			$state = $ticket->State;
			if(false && !$state['service_executable']) {
				throw new Exception(__FUNCTION__.': current ticket state is not serviceable',104);
			}
			
			if($ticket['ticket_state_next'] === null) {
				throw new Exception(__FUNCTION__.': no next state available!',105);
			}
			
			$previousState = $state['ticket_state'];

			if($ticket->save(['ticket_state' => $ticket['ticket_state_next']])) {
				LogEntry::create(array(
					'ticket_id' => $ticket['id'],
					'from_state' => $previousState,
					'to_state' => $ticket['ticket_state_next'],
					'handle_id' => $this->worker['id'],
					'event' => 'RPC.'.__FUNCTION__,
					'comment' => $log_message));

				return true;
			}

			return false;
		}

		/**
		 * Control channel for workers.
		 *
		 * Workers for services are supposed to poll the tracker periodically to notify about there state
		 * and progress. The return value is used to apply commands.
		 *
		 * @param integer ticket_id of current ticket a worker is working on, empty if none assigned
		 * @param string optional log message
		 * @return string command to handle by worker, 'OK' if nothing special to do
		 */
		public function ping($ticket_id = null, $log_message = null) {
			// log ping
			$time_since = ($this->worker['last_seen']) ? (new DateTime())->diff(new DateTime($this->worker['last_seen']))->format('%Hh %imin %ss') : 'long long time';
			Log::debug('ping from '.$this->worker['name'].' ('.$this->Request->getRemoteIP().') [last ping '.$time_since." ago]: ticket_id=$ticket_id log_message='$log_message'");

			// set cmd for return value
			$cmd = 'OK';
			$reason = '';

			$state = array();
			// check ticket state if id given
			$ticket_id = intval($ticket_id);
			if($ticket_id > 0) {
				if(!$ticket = Ticket::find(['id' => $ticket_id], ['State', 'Handle'])) {
					$reason = 'ticket not found';
				} elseif($ticket['handle_id'] == null) {
					$reason = 'ticket is unassigned';
				} elseif($ticket['handle_id'] != $this->worker['id']) {
					$reason = 'ticket is assigned to other handle: '.$ticket['handle_name'];
				} elseif(!in_array($ticket['project_id'],$this->_assignedProjects)) {
					$reason = 'ticket in project not assigned to worker group';
				}
				$state = $ticket->State;
				if(empty($state) || !$state['service_executable']) {
					$reason = 'ticket is in non-service state: '.$ticket['ticket_state'];
				}

				// lose ticket if error occurred
				if(!empty($reason)) {
					$cmd = 'Ticket lost';
					Log::warning('[RPC] ping: '.$reason);
				}
			} else {
				$ticket_id = null;
			}

			if($cmd != 'OK') {
				// only log valid ticket ids
				if($ticket) {
					LogEntry::create(array(
						'ticket_id' => $ticket_id,
						'handle_id' => $this->worker['id'],
						'comment' => "Worker received command '$cmd'\n\nReason: $reason",
						'event' => 'RPC.'.__FUNCTION__
					));
				}
			}

			// TODO: add last_ping?

			return $cmd;
		}
		
		/**
		* Get properties of ticket with given id
		*
		* @param int ticket_id id of ticket
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
		* @param int ticket_id id of ticket
		* @param array associative array of properties ( key => value )
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
		 * @param string ticket_type type of ticket
		 * @param string ticket_state ticket state the returned ticket will be in after this call
		 * @param array filter_parameters return only tickets matching given properties
		 * @return array ticket data or false if no matching ticket found (or user is halted)
		 * @throws Exception on error
		 */
		public function assignNextUnassignedForState($ticket_type = '', $ticket_state = '', $filter_properties = array()) {
			/* TODO reintroduce worker hold
			if(!$this->checkReadOnly()) {
				return false;
			}*/

			if(empty($ticket_type) || empty($ticket_state)) {
				throw new EntryNotFoundException(__FUNCTION__.': ticket type or ticket state missing',401);
			}

			// create query: find all tickets in state
			$tickets = Ticket::findAll(['State'])
				->from('view_serviceable_tickets', 'tbl_ticket')
				->where(array('project_id' => $this->_assignedProjects, 'ticket_type' => $ticket_type, 'next_state' => $ticket_state, 'next_state_service_executable' => 1))
				->orderBy('ticket_priority(id) DESC');

			// filter out virtual conditions used for further where conditions
			$virtualConditions = array(
				'Record.StartedBefore' => 'time_start < ?',
				'Record.EndedAfter' => 'time_end > ?',
				'Record.EndedBefore' => 'time_end < ?'
			);

			// extract virtual filter properties
			if(!empty($filter_properties)) {
				Log::debug('got property filter: '.var_export($filter_properties,true));
				foreach($virtualConditions as $property => $condition) {
					if(array_key_exists($property, $filter_properties)) {
						Log::debug('found property '.$property);
						$tickets->where($condition,array($filter_properties[$property]));
						unset($filter_properties[$condition]);
					}
				}
			}

			// check again if we still need to filter tickets properties
			if(empty($filter_properties)) {
				$ticket = $tickets->first();
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
						break;
					}
				}
			}
			
			if (empty($ticket)) {
				return false;
			}

			/* TODO handling abandoned tickets after timeout
			 if(!$ticket = $this->Ticket->findUnassignedByState($service['from'], 1)) {
				// no matching ticket found
				
				// get ping timeout for workers
				$worker_timeout = !empty($this->Config->RPC['worker_timeout']) ? $this->Config->RPC['worker_timeout'] : '5min';
				
				// check for tickets assigned to workers which are not seen for longer than $worker_timeout
				if(!$ticket = $this->Ticket->findAbandonedByState($service['state'],$worker_timeout,1)) {
					return false;
				}
				
				$from_state_id = $service['state'];
				$from_user_id = $ticket['user_id'];
				
				Log::info('[RPC] assignNextUnassignedForState: reassign abandoned ticket #'.$this->Ticket->id);
			} else {
				$from_state_id = $service['from'];
				$from_user_id = null;
			}*/

			$log_entry = array(
				'ticket_id' => $ticket['id'],
				'handle_id' => $this->worker['id'],
				'from_state' => $ticket['ticket_state'],
				'to_state' => $ticket['next_state'],
				'event' => 'RPC.'.__FUNCTION__
			);
			
			$saved = $ticket->save(
				// assign to worker with new state
				array(
					'handle_id' => $this->worker['id'],
					'ticket_state' => $ticket['next_state']
				),
				// ensure ticket is not assigned yet and in the right state
				array(
					'handle_id' => null,
					'ticket_state' => $ticket['ticket_state']
				)
			);
			
			if (!$saved) {
				Log::warning(__FUNCTION__.': race condition with other request. delaying new request');
				return false;
			}

			LogEntry::create($log_entry);
			
			return $ticket->toArray();
		}

		/**
		 * Get all assigned tickets in state $state
		 *
		 * @param string ticket_type type of ticket
		 * @param string ticket_state ticket state
		 * @param array filter_parameters return only tickets matching given properties
		 * @return array ticket data or false if no matching ticket found (or user is halted)
		 * @throws Exception on error
		 */
		public function getAssignedForState($ticket_type = '', $ticket_state = '', $filter_properties = array()) {
			/* TODO reintroduce worker hold
			if(!$this->checkReadOnly()) {
				return false;
			}*/

			if(empty($ticket_type) || empty($ticket_state)) {
				throw new EntryNotFoundException(__FUNCTION__.': ticket type or ticket state missing',401);
			}

			// create query: find all tickets in state
			$tickets = Ticket::findAll(['State'])->from('view_serviceable_tickets', 'tbl_ticket')->where(array('handle_id' => $this->worker['id'], 'ticket_type' => $ticket_type, 'ticket_state' => $ticket_state));

			// filter out virtual conditions used for further where conditions
			$virtualConditions = array(
				'Record.StartedBefore' => 'time_start < ?',
				'Record.EndedAfter' => 'time_end > ?',
				'Record.EndedBefore' => 'time_end < ?'
			);

			// extract virtual filter properties
			if(!empty($filter_properties)) {
				Log::debug('got property filter: '.var_export($filter_properties,true));
				foreach($virtualConditions as $property => $condition) {
					if(array_key_exists($property, $filter_properties)) {
						Log::debug('found property '.$property);
						$tickets->where($condition,array($filter_properties[$property]));
						unset($filter_properties[$condition]);
					}
				}
			}

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
		}

		/**
		 * Unassign ticket and set state to according state after procressing by service.
		 *
		 * A log message can be appended.
		 *
		 * @param integer id of ticket
		 * @param string optional log message
		 * @return boolean true if action was performed sucessfully
		 * @throws Exception
		 */
		public function setTicketDone($ticket_id, $log_message = null) {
			if(!$ticket = Ticket::findAll(['State'])->from('view_serviceable_tickets', 'tbl_ticket')->where(['id' => $ticket_id])->first()) {
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
		 * @param integer id of ticket
		 * @param string optional log message
		 * @return boolean true if action was performed sucessfully
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
		 * @param integer id of ticket
		 * @return boolean true if action was performed sucessfully
		 * @throws Exception
		 */
		public function getJobfile($ticket_id) {
			$properties = $this->getTicketProperties($ticket_id);

			// check for basename
			if(!isset($properties['Encoding.Basename'])) {
				throw new Exception(__FUNCTION__.': could not examine file basename. Is property Record.Language set?',701);
			}

			// get encoding profile
			if(!$profileVersion = Ticket::findBy(array('id' => $ticket_id))->EncodingProfileVersion) {
				throw new EntryNotFoundException(__FUNCTION__.': encoding profile not found',702);
			}

			$template = new DOMDocument();
			
			// prepare template
			if (!$template->loadXML($profileVersion['xml_template'])) {
				throw new Exception(__FUNCTION__.': Couldn\'t parse XML template');
			}
			
			// Process templates as XSL
			
			$content = new DOMDocument('1.0', 'UTF-8');
			$parent = $content->createElement('properties');
			
			foreach ($properties as $name => $value) {
				$element = $content->createElement('property');
				$element->setAttribute('name', $name);
				$element->appendChild(new DOMText($value));
				
				$parent->appendChild($element);
			}
			
			$content->appendChild($parent);
			
			$processor = new XSLTProcessor();
			$processor->importStylesheet($template);

			// pretty print
			$output = $processor->transformToDoc($content);
			
			$output->formatOutput = true;
			$output->encoding = 'UTF-8';
			
			return $output->saveXml();
		}
		
		/**
		* Add a log message regarding the ticket with given id
		*
		* @param int ticket_id id of ticket
		* @param string comment text for log message
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
		 * Check whether project is writable and user not set to read only.
		 *
		 */
		/*private function checkReadOnly() {
			if($this->Project->current()->read_only) {
				throw new ActionNotAllowedException('project is read_only',411);
			}
			
			// return false if user is currently halted
			$halted_until = Date::fromString($this->User->halted_until);
			if(Date::now()->isLater($halted_until)) {
				Log::debug('checkReadOnly: user is halted! new tickets available in '.Date::distanceInWords($halted_until));
				return false;
			}

			if(!empty($this->Config->RPC['hold_services'])) {
				Log::debug('checkReadOnly: all workers are halted by admin.');
				return false;
			}
			return true;
		}*/
	}
	
?>
