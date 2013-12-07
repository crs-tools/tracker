<?php
	
	requires(
		'Controller/XMLRPC',
		'/Model/WorkerGroup'
	);
	
	class Controller_XMLRPC_Handler extends Controller_XMLRPC {
		
		protected $beforeAction = array('authenticate' => true);
		
		const XMLRPC_PREFIX = 'C3TT.';
		
		// private $virtual_properties = array('Encoding.Basename', 'Project.Slug', 'EncodingProfile.Basename', 'EncodingProfile.Extension');
		
		protected function authenticate($method, array $arguments) {
			if (empty($_GET['group']) or empty($_GET['hostname'])) {
				return $this->_XMLRPCFault(-32500, 'incomplete arguments');
			}
			
			if (!$group = WorkerGroup::findBy(array('token' => $_GET['group']))) {
				return $this->_XMLRPCFault(-32500, 'worker group not found');
			}
			
			if (!self::_validateSignature($group['secret'], array_merge(array(
				$this->Request->getURL(),
				self::XMLRPC_PREFIX . $method,
				$group['token'],
				$_GET['hostname']
			), $arguments))) {
				return $this->_XMLRPCFault(-32500, 'invalid or missing signature');
			} 
			
			$name = self::_getNameFromHostName($_GET['hostname']);
			
			if (!$this->worker = Worker::findBy(array('name' => $name))) {
				$this->worker = Worker::create(array(
					'name' => $name,
					'worker_group_id' => $group['id']
				));
			} else {
				// TODO: update last_seen
			}
		}
		
		private static function _validateSignature($secret, $arguments) {
			$signature = array_pop($arguments);
			$hash = hash_hmac(
				'sha256',
				rawurlencode(implode('&', $arguments)),
				$secret
			);

			// TODO: compare in constant time
			return $hash === $signature;
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
         * echo method to testXMLRPC API
         *
         * @param string frist message
         * @param string second message
         * @return string input string
         */
        public function getEcho($msg, $msg2) {
            return $msg . ' ' . $msg2;
        }

		/**
		 * fetches list of projects
		 * 
		 * @param boolean read_only if true, finished projects get listed too (default: false)
		 * @param boolean list_encoding_profiles if true, list of encoding profiles for each project is returned (default: true)
		 * @return array project data
		 */
		/*public function getProjects($read_only = false, $list_encoding_profiles = true) {
			if($list_encoding_profiles) {
				$projects = $this->Project->findAll(array('Project','EncodingProfile'), array('read_only' => $read_only));
			} else {
				$projects = $this->Project->findAll(array(), array('read_only' => $read_only));
			}
			return is_array($projects) ? $projects : array();
		}*/

		/**
		 * returns list of states services can retrieve tickets for (merging, encoding, ....)
		 *
		 * @return array list of services
		 */
		/*public function getServices() {
			return array_keys($this->State->services);
		}*/

		/**
		 * Control channel for workers.
		 *
		 * Workers for services are supposed to poll the tracker periodically to notify about there state
		 * and progress. The return value is used to apply commands.
		 *
		 * @param integer ticket_id of current ticket a worker is working on, empty if none assigned
		 * @param string log messages since last ping
		 * @param mixed progress information of current process
		 * @return string command to handle by worker, 'OK' if nothing special to do
		 */
		/*public function ping($ticket_id = null, $log_message_delta = null, $progress = null) {
			// log ping
			$time_since = ($this->User->last_seen) ? Date::distanceInWords(Date::fromString($this->User->last_seen),null,true) : 'long long time';
			Log::debug('ping from '.$this->User->hostname.' ('.Request::getIP().') [last ping '.$time_since." ago]: ticket_id=$ticket_id progress=$progress log_message_delta=$log_message_delta");

			// set cmd for return value
			$cmd = 'OK';
			$reason = '';

			// check ticket state if id given
			$ticket_id = intval($ticket_id);
			if($ticket_id > 0) {
				if(!$ticket = $this->Ticket->find($ticket_id, array('User','State'))) {
					$reason = 'ticket not found';
				} elseif($this->Ticket->user_id == null) {
					$reason = 'ticket is unassigned';
				} elseif($this->Ticket->user_id != $this->User->id) {
					$reason = 'ticket is assigned to other user: '.$this->Ticket->user_name;
				} elseif(!$service = $this->State->getServiceByTicket($ticket)) {
					$reason = 'ticket is in non-service state: '.$this->Ticket->state_name;
				}
				// lose ticket if error occurred
				if(!empty($reason)) {
					$cmd = 'Ticket lost';
					Log::warn('[RPC] ping: '.$reason);
				}
			} else {
				$ticket_id = null;
			}
			
			// save output
			if($progress != null || $log_message_delta != null) {
				$this->ServiceLogEntry->create(array(
						'ticket_id' => $ticket_id,
						'output_delta' => $log_message_delta,
						'progress' => $progress
					));
			}

			// check next ping command if no previous error occurred
			if(empty($reason) && $this->User->next_ping_command) {
				$cmd = $this->User->next_ping_command;
				$reason = $this->User->next_ping_command_reason;
			}

			if($cmd != 'OK') {
				// reset command and reason
				$this->User->next_ping_command = null;
				$this->User->next_ping_command_reason = null;

				// only log valid ticket ids
				if($ticket) {
					$this->LogEntry->create(array(
						'ticket_id' => $ticket_id,
						'user_id' => $this->User->id,
						'comment' => "User received command '$cmd'\n\nReason: $reason",
						'event' => 'RPC.Ping.Command'
					));
				}
			}

			$this->User->updateLastRequest(false);
			$this->User->save();
			
			return $cmd;
		}*/
		
		/**
		* Get value assigned to given property name of the ticket with given id
		*
		* @param int ticket_id id of ticket
		* @param string property name
		* @return string value of property or empty string if not found
		*/
		/*public function getTicketProperty($ticket_id, $name) {
			if(empty($name)) {
				throw new Exception('getTicketProperty: empty property name',419);
			}
			
			$properties = $this->getTicketProperties($ticket_id);
			
			return isset($properties[$name]) ? $properties[$name] : '';
		}*/

		/**
		* Get properties of ticket with given id
		*
		* @param int ticket_id id of ticket
		* @param string optional pattern of property names (see ltree docs for operator "~")
		* @return array property data
		*/
		/*public function getTicketProperties($ticket_id, $pattern = null) {
			if(!$ticket = $this->Ticket->find($ticket_id,array(), array('project_id' => $this->Project->current()->id))) {
				throw new EntryNotFoundException('getTicketProperties: ticket not found',414);
			}

			// get project properties
			if($pattern != null) {
				$properties = ProjectProperties::indexByField($this->ProjectProperties->findAll(array(),'project_id = :project_id AND name ~ :name',array('project_id' => $this->Project->id, 'name' => $pattern)),'name','value');
			} else {
				$properties = ProjectProperties::indexByField($this->ProjectProperties->findAll(array(),array('project_id' => $this->Project->id)),'name','value');
			}
			
			if(!is_array($properties)) {
				$properties = array();
			}

			// get ticket properties of parent
			if($ticket['parent_id'] !== null) {
				if($pattern != null) {
					$parent_properties = Properties::indexByField($this->Properties->findAll(array(),'ticket_id = :ticket_id AND name ~ :name',array('ticket_id' => $ticket['parent_id'], 'name' => $pattern)),'name','value');
				} else {
					$parent_properties = Properties::indexByField($this->Properties->findAll(array(),array('ticket_id' => $ticket['parent_id'])),'name','value');
				}
				
				if(is_array($parent_properties)) {
					$properties = array_merge($properties, $parent_properties);
				}
			}

			// get ticket properties
			if($pattern != null) {
				$ticket_properties = Properties::indexByField($this->Properties->findAll(array(),'ticket_id = :ticket_id AND name ~ :name',array('ticket_id' => $ticket_id, 'name' => $pattern)),'name','value');
			} else {
				$ticket_properties = Properties::indexByField($this->Properties->findAll(array(),array('ticket_id' => $ticket_id)),'name','value');
			}
			
			if (is_array($ticket_properties)) {
				$properties = array_merge($properties, $ticket_properties);
			}

			// virtual property: project slug
			if($pattern != null && strpos('Project.Slug',$pattern) !== false || $pattern == null) {
				$properties['Project.Slug'] = $this->Project->current()->slug;
			}

			// virtual property: basename for encoding
			if($pattern != null && strpos('Encoding.Basename',$pattern) !== false || $pattern == null) {
				$filename = $this->Properties->getFilename($properties);
				if(strlen($filename) > 0) {
					$properties['Encoding.Basename'] = $filename;
				}
			}

			// add encoding profile properties
			if($ticket['type_id'] == 2) {
				$profile = $this->EncodingProfile->find($ticket['encoding_profile_id'],array(), array('project_id' => $this->Project->current()->id));
				if(!$profile) {
					throw new EntryNotFoundException('getTicketProperties: encoding profile not found',417);
				}
				// virtual property:  EncodingProfile.Basename
				$properties['EncodingProfile.Basename'] = $properties['Encoding.Basename'];
				if(!empty($profile['slug'])) {
					$properties['EncodingProfile.Basename'] .= '_' . $profile['slug'];
				}

				// virtual property:  EncodingProfile.Extension
				$properties['EncodingProfile.Extension'] = $profile['extension'];
				// virtual property:  EncodingProfile.Extension
				$properties['EncodingProfile.Slug'] = $profile['slug'];
			}

			return $properties;
		}*/

		/**
		* Set ticket property for ticket with given id
		*
		* @param int ticket_id id of ticket
		* @param string name property name
		* @param string name value
		* @return true if properties set successfully
		*/
		/*public function setTicketProperty($ticket_id, $name, $value) {
			if(!$ticket = $this->Ticket->find($ticket_id,array(), array('project_id' => $this->Project->current()->id))) {
				throw new EntryNotFoundException('setTicketProperty: ticket not found',414);
			}
			
			if(in_array($name,$this->virtual_properties)) {
					Log::warn('[RPC] setTicketProperties: ingored virtual property '.$name);
					return false;
			}
			
			$this->Properties->clear();
			$this->Properties->set(array('ticket_id' => $ticket_id, $name => $value));
			
			if (!$save = $this->Properties->save()) {
				Log::warn('[RPC] setTicketProperty: race condition with other request. delaying new request');
				return false;
			}
			
			$this->LogEntry->create(array(
				'ticket_id' => $this->Ticket->id,
				'event' => 'RPC.Property.Set',
				'comment' => $name . '="' . $value . '"'
			));
			
			return true;
		}*/

		/**
		* Set ticket properties for ticket with given id
		*
		* @param int ticket_id id of ticket
		* @param array associative array of properties ( key => value )
		* @return true if properties set successfully
		*/
		/*public function setTicketProperties($ticket_id, $properties) {
			if(!$ticket = $this->Ticket->find($ticket_id,array(), array('project_id' => $this->Project->current()->id))) {
				throw new EntryNotFoundException('setTicketProperties: ticket not found',414);
			}
			if(!is_array($properties) || count($properties) < 1) {
				throw new EntryNotFoundException('setTicketProperties: no properties given',420);
			}

			$comment = "";
			foreach($properties as $name => $value) {
				if(in_array($name,$this->virtual_properties)) {
					Log::warn('[RPC] setTicketProperties: ingored virtual property '.$name);
					continue;
				}
				$this->Properties->clear();
				$this->Properties->set(array('ticket_id' => $ticket_id, $name => $value));

				$log_line = $name . '="' . $value . '"';
				if (!$save = $this->Properties->save()) {
					Log::warn('[RPC] setTicketProperties: cannot set property '.$log_line);
					return false;
				}
				$comment .= $log_line . "\n";
			}
			
			$this->LogEntry->create(array(
				'ticket_id' => $this->Ticket->id,
				'event' => 'RPC.Property.Set',
				'comment' => $comment
			));
			
			return true;
		}*/

		/**
		 * Get all unassigned tickets in given state
		 *
		 * @param string id or name of state
		 * @return array ticket data
		 */
		/*public function getUnassignedTicketsInState($state) {
			if(!$this->checkReadOnly()) {
				return array();
			}
			// auto cast types
			if(!is_integer($state)) {
				$state = $this->State->getIdByName($state);
			}

			$tickets = $this->Ticket->findUnassignedByState($state);

			return is_array($tickets) ? $tickets : array();
		}*/

		/**
		 * Get next unassigned ticket ready to be in state $state after transition.
		 *
		 * First ticket found gets assigned to calling user and state transition to $state is performed.
		 *
		 * @param string id or name of state
		 * @return array ticket data or false if no matching ticket found (or user is halted)
		 */
		/*public function assignNextUnassignedForState($state) {
			if(!$this->checkReadOnly()) {
				return false;
			}
			
			// auto cast types
			if(is_integer($state)) {
				$state = $this->State->getNameById($state);
			}
			
			// check if valid state
			if(!$service = $this->State->getService($state)) {
				throw new ActionNotAllowedException('assignNextUnassignedForState: no service found for state', 413);
			}
			
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
			}
			
			$this->Ticket->user_id = $this->User->id;
			$this->Ticket->state_id = $service['state'];
			
			if (!$save = $this->Ticket->save(null, array('user_id' => $from_user_id, 'state_id' => $from_state_id))) {
				Log::warn('[RPC] assignNextUnassignedForState: race condition with other request. delaying new request');
				return false;
			}
			
			$this->LogEntry->create(array(
				'ticket_id' => $this->Ticket->id,
				'from_state_id' => $from_state_id,
				'to_state_id' => $service['state'],
				'event' => 'RPC.Processing.Start'
			));
			
			return $ticket;
		}*/

		/**
		 * Unassign ticket and set state to according state after procressing by service.
		 *
		 * A log message can be appended.
		 *
		 * @param integer id of ticket
		 * @param string optional log message
		 * @return boolean true if action was performed sucessfully
		 */
		/*public function setTicketDone($ticket_id, $log_message = null) {
			if(!$ticket = $this->Ticket->find($ticket_id, array(), array('project_id' => $this->Project->current()->id))) {
				throw new EntryNotFoundException('setTicketDone: ticket not found',414);
			}
			if($this->Ticket->user_id == null) {
				throw new Exception('setTicketDone: ticket not assigned', 415);
			}
			if($this->Ticket->user_id != $this->User->id) {
				throw new Exception('setTicketDone: this is not your ticket', 416);
			}
			if(!$service = $this->State->getServiceByTicket($ticket)) {
				throw new Exception('setTicketDone: no service found for ticket state', 417);
			}
			
			$this->Ticket->user_id = null;
			$this->Ticket->state_id = $service['to'];
			
			if (!$save = $this->Ticket->save(null, array('user_id' => $this->User->id, 'state_id' => $service['state']))) {
				Log::warn('[RPC] setTicketDone: race condition with other request. delaying new request');
				return false;
			}
			
			$this->LogEntry->create(array(
				'ticket_id' => $this->Ticket->id,
				'from_state_id' => $service['state'],
				'to_state_id' => $service['to'],
				'event' => 'RPC.Processing.Done',
				'comment' => $log_message
			));

			return true;
		}*/

		/**
		 * Unassign ticket and set "failed" flag.
		 *
		 * A log message can be appended.
		 *
		 * @param integer id of ticket
		 * @param string optional log message
		 * @return boolean true if action was performed sucessfully
		 */
		/*public function setTicketFailed($ticket_id, $log_message = null) {
			if(!$ticket = $this->Ticket->find($ticket_id, array(), array('project_id' => $this->Project->current()->id))) {
				throw new EntryNotFoundException('setTicketFailed: ticket not found',414);
			}
			if($this->Ticket->user_id == null) {
				throw new Exception('setTicketFailed: ticket not assigned', 415);
			}
			if($this->Ticket->user_id != $this->User->id) {
				throw new Exception('setTicketFailed: this is not your ticket', 416);
			}
			if(!$service = $this->State->getServiceByTicket($ticket)) {
				throw new Exception('setTicketFailed: no service found for ticket state', 417);
			}
			
			$this->Ticket->user_id = null;
			$this->Ticket->failed = true;
			
			if (!$save = $this->Ticket->save(null, array('user_id' => $this->User->id))) {
				Log::warn('[RPC] setTicketFailed: race condition with other request. delaying new request');
				return false;
			}
			
			$this->LogEntry->create(array(
				'ticket_id' => $this->Ticket->id,
				'event' => 'RPC.Processing.Failed',
				'comment' => $log_message
			));

			return true;
		}*/

		/**
		 * Sets the state of a unassigned ticket to the next state in the normal workflow.
		 *
		 * @param int ticket_id id of ticket
		 * @param string id or name of state
		 * @param string optional log message
		 * @return true if set state sucessfully
		 */
		/*public function setTicketNextState($ticket_id, $state, $log_message = null) {
			if(!$this->checkReadOnly()) {
				return false;
			}

			// auto cast types
			if(!is_integer($state)) {
				$state = $this->State->getIdByName($state);
			}
			
			$ticket = $this->Ticket->find($ticket_id,array(), array('failed' => 'false', 'project_id' => $this->Project->current()->id));
			if(!$ticket) {
				throw new EntryNotFoundException('setTicketNextState: ticket not found or failed',414);
			}
			if($this->Ticket->state_id != $state) {
				throw new ActionNotAllowedException('setNextTicketState: ticket not in given state. maybe race condition',418);
			}
			if($this->Ticket->user_id != null) {
				throw new ActionNotAllowedException('setNextTicketState: ticket not unassigned.',418);
			}
			// check if valid state
			if(!$service = $this->State->getServiceByTicket($ticket)) {
				throw new ActionNotAllowedException('setNextTicketState: no service found for state', 419);
			}
			
			$next_state = ($service['from'] == $state) ? $service['state'] : $service['to'];
			$this->Ticket->state_id = $next_state;
			
			if (!$save = $this->Ticket->save(null, array('user_id' => null, 'state_id' => $state))) {
				Log::warn('[RPC] setTicketNextState: race condition with other request. delaying new request');
				return false;
			}
			
			$this->LogEntry->create(array(
				'ticket_id' => $this->Ticket->id,
				'from_state_id' => $state,
				'to_state_id' => $next_state,
				'event' => 'RPC.State.Next',
				'comment' => $log_message
			));
			
			return true;
		}*/

		/*public function getJobfile($ticket_id) {
			$ticket = $this->Ticket->find($ticket_id,array(), array('failed' => 'false', 'type_id' => 2, 'project_id' => $this->Project->current()->id));
			if(!$ticket) {
				throw new EntryNotFoundException('getJobfile: ticket not found, failed or wrong ticket type',416);
			}
			
			$properties = $this->getTicketProperties($this->Ticket->id);

			// check for basename
			if(!isset($properties['Encoding.Basename'])) {
				throw new Exception('getJobfile: could not examine file basename. Is property Record.Language set?',418);
			}

			// get encoding profile
			$profile = $this->EncodingProfile->find($ticket['encoding_profile_id'],array(), array('project_id' => $this->Project->current()->id));
			if(!$profile) {
				throw new EntryNotFoundException('getTicketProperties: encoding profile not found',417);
			}
			
			$template = new DOMDocument();
			
			// prepare template
			if (!$template->loadXML($profile['xml_template'])) {
				throw new Exception('getJobfile: Couldn\'t parse XML template');
			}
			
			if ($template->documentElement->nodeName != 'xsl:stylesheet') {
				// Process XML Template with PHP, this is deprecated since 2012-12-10
				
				// replace property placeholder
				$xpath = new DOMXPath($template);
				$entries = $xpath->query('//property');
				foreach($entries as $property) {
					if($property->attributes->getNamedItem('name') == null) continue;

					// get property value
					$property_value = isset($properties[$property->attributes->getNamedItem('name')->value]) ? $properties[$property->attributes->getNamedItem('name')->value] : '';

					// check further escaping
					$escaping = $property->attributes->getNamedItem('escaping');
					if($escaping != null && $escaping->value == 'ascii') {
						$property_value = iconv("UTF-8", "ASCII//TRANSLIT", $property_value);
					}
					// replace placeholder
					$property->parentNode->replaceChild($template->createTextNode($property_value),$property);
				}

				// set job id
				$id = $this->Ticket->fahrplan_id;
				
				if(!empty($profile['slug'])) {
					$id .= '_' . $profile['slug'];
				} else { // render slug for id from profile name if profile slug is empty
					$id .= '_' . preg_replace('/[^a-zA-Z_\-0-9]/','_',preg_replace('/[.:]/','',$profile['name']));
				}
				$template->documentElement->setAttribute('id',$id);

				return $template->saveXML();
			}
			
			// Process templates as XSL
			
			$content = new DOMDocument('1.0', 'UTF-8');
			$parent = $content->createElement('properties');
			
			foreach ($properties as $name => $value) {
				$element = $content->createElement('property');
				$element->setAttribute('name', $name);
				$element->nodeValue = $value;
				
				$parent->appendChild($element);
			}
			
			$content->appendChild($parent);
			
			$processor = new XSLTProcessor();
			$processor->importStylesheet($template);
			
			return $processor->transformToXML($content);
		}*/
		
		/**
		* Get details about the encoding profiles available for this project.
		*
		* @param encoding_profile_id get details only for specified profile
		* @return array profile details
		*/
		/*public function getEncodingProfiles($encoding_profile_id = null) {
			if($encoding_profile_id) {
				$profiles = $this->EncodingProfile->find($encoding_profile_id, array(), array('project_id' => $this->Project->current()->id));
			} else {
				$profiles = $this->EncodingProfile->findAll(array(), array('project_id' => $this->Project->current()->id));
			}
			
			return is_array($profiles) ? $profiles : array();
		}*/
		
		/**
		* Add a log message regarding the ticket with given id
		*
		* @param int ticket_id id of ticket
		* @param string comment text for log message
		* @return boolean true if comment saved successfully
		*/
		/*public function addLog($ticket_id, $log_message) {
			if(!$ticket = $this->Ticket->find($ticket_id,array(), array('project_id' => $this->Project->current()->id))) {
				throw new EntryNotFoundException('setTicketFailed: ticket not found',414);
			}
			
			return $this->LogEntry->create(array(
				'ticket_id' => $ticket_id,
				'user_id' => $this->User->id,
				'comment' => $log_message,
				'event' => 'RPC.Log'
			));
		}*/
		
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
