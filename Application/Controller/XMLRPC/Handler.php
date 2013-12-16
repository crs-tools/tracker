<?php
	
	requires(
		'Controller/XMLRPC',
		'/Model/WorkerGroup',
        '/Model/EncodingProfile',
        '/Model/LogEntry',
        '/Model/Ticket',
        '/Model/TicketState'
	);
	
	class Controller_XMLRPC_Handler extends Controller_XMLRPC {
		
		protected $beforeAction = array('authenticate' => true);
		
		const XMLRPC_PREFIX = 'C3TT.';
		
		private $virtual_properties = array(
            'Encoding.Basename',
            'Project.Slug',
            'EncodingProfile.Basename',
            'EncodingProfile.Extension'
        );

        /**
         * constructor
         *
         * set error reporting to suppress notices, since error messages break XML output
         */
        public function __construct() {
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
				// TODO: update last_seen
			}
		}
		
		private static function _validateSignature($secret, $signature, $arguments) {
            $args = array();
            foreach($arguments as $argument) {
                $args[] = is_array($argument) ? http_build_query($argument) : $argument;
            }

			$hash = hash_hmac(
				'sha256',
				rawurlencode(implode('&', $args)),
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
            return TicketState::getNextState($project_id, $ticket_type, $ticket_state);
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
            return TicketState::getPreviousState($project_id, $ticket_type, $ticket_state);
        }

        /**
         * Get consecutive ticket state of given ticket
         *
         * @param integer ticket_id ticket identifier
         * @return array ticket state
         */
        public function getTicketNextState($ticket_id) {
            $ticket = Ticket::findBy(['id' => $ticket_id]);

            return TicketState::getNextState($ticket['project_id'], $ticket['ticket_type'], $ticket['ticket_state']);
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
                throw new Exception(__METHOD__.': ticket not found',101);
            }

            if(empty($ticket['handle_id']) || $ticket['handle_id'] != $this->worker['id']) {
                throw new Exception(__METHOD__.': ticket is not assigned to you',102);
            }

            $state = $ticket->State;
            if(false && !$state['service_executable']) {
                throw new Exception(__METHOD__.': current ticket state is not serviceable',103);
            }

            $next_state = $state->getNextState();
            if(!$next_state) {
                throw new Exception(__METHOD__.': no next state available!',104);
            }

            if($ticket->save(['ticket_state' => $next_state['ticket_state']])) {
                LogEntry::create(array(
                    'ticket_id' => $ticket['id'],
                    'from_state' => $state['ticket_state'],
                    'to_state' => $next_state['ticket_state'],
                    'handle_id' => $this->worker['id'],
                    'event' => __FUNCTION__,
                    'comment' => $log_message));

                return true;
            }

            return false;
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
				if(!$ticket = Ticket::find(['id' => $ticket_id], ['State', 'User'])) {
					$reason = 'ticket not found';
				} elseif($ticket['handle_id'] == null) {
					$reason = 'ticket is unassigned';
				} elseif($ticket['handle_id'] != $this->worker['id']) {
					$reason = 'ticket is assigned to other user: '.$ticket['user_name'];
				} elseif($state = $ticket->State && !$state['service_executable']) {
					$reason = 'ticket is in non-service state: '.$ticket['ticket_state'];
				}

				// lose ticket if error occurred
				if(!empty($reason)) {
					$cmd = 'Ticket lost';
					Log::warn('[RPC] ping: '.$reason);
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
						'comment' => "User received command '$cmd'\n\nReason: $reason",
						'event' => __FUNCTION__
					));
				}
			}

			$this->worker->save(['last_seen' =>  new DateTime()]);
			
			return $cmd;
		}
		
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
		* @param string sub_path prefix of property names
		* @return array property data
        * @throws Exception if ticket not found
		*/
		public function getTicketProperties($ticket_id, $pattern = null) {
            if(!$ticket = Ticket::find(['id' => $ticket_id], ['User','Parent','Project'])) {
                throw new EntryNotFoundException(__FUNCTION__.': ticket not found',201);
            }

			// get project properties
            $properties = $this->_getProperties($ticket->Project);

			// get ticket properties of parent
			if($ticket['parent_id'] !== null) {
                $properties = array_merge($properties,$this->_getProperties($ticket->Parent,$pattern));
            }

            // get ticket properties of related recording ticket
            if($ticket['ticket_type'] != 'recording') {
                $children = ($ticket->Parent) ? $ticket->Parent->Children : $ticket->Children;
                $children->where(array('ticket_type' => 'recording'));
                $recording_ticket = $children->first();

                // get ticket properties of parent
			    if($recording_ticket) {
                    $properties = array_merge($properties,$this->_getProperties($recording_ticket,$pattern));
                }
            }

            // get ticket properties of related ingest ticket
            if($ticket['ticket_type'] != 'ingest') {
                $children = ($ticket->Parent) ? $ticket->Parent->Children : $ticket->Children;
                $children->where(array('ticket_type' => 'ingest'));
                $ingest_ticket = $children->first();

                // get ticket properties of parent
                if($ingest_ticket) {
                    $properties = array_merge($properties,$this->_getProperties($ingest_ticket,$pattern));
                }
            }

            // get ticket properties
            $properties = array_merge($properties,$this->_getProperties($ticket,$pattern));

			// virtual property: project slug
			if($pattern != null && strpos('Project.Slug',$pattern) !== false || $pattern == null) {
				$properties['Project.Slug'] = $ticket->Project['slug'];
			}

            // virtual property: basename for encoding, project slug, fahrplan id, ticket slug
			if($pattern != null && strpos('Encoding.Basename',$pattern) !== false || $pattern == null) {
                $parts = array();

                if(isset($properties['Project.Slug'])) {
                    array_push($parts, $properties['Project.Slug']);
                }

                if(isset($properties['Fahrplan.ID'])) {
                    array_push($parts, $properties['Fahrplan.ID']);
                }

                /*
                // add language if project has multiple languages
                if($this->Project->current() and count($this->Project->current()->languages) > 0) {
                    if(!isset($properties['Record.Language'])) {
                        // error: language is not set, return empty string
                        return '';
                    } else {
                        $parts[] = $properties['Record.Language'];
                    }
                }*/

                if(isset($properties['Fahrplan.Slug'])) {
                    array_push($parts, $properties['Fahrplan.Slug']);
                }

                $properties['Encoding.Basename'] = implode('-', $parts);
			}

            // add encoding profile properties
            if($ticket['ticket_type'] == 'encoding') {
                $profile = $ticket->EncodingProfileVersion->EncodingProfile;
                if(!$profile) {
                    throw new EntryNotFoundException(__FUNCTION__.': encoding profile not found',202);
                }

                // add virtual properties
                if(isset($properties['Encoding.Basename'])) {
                    $properties['EncodingProfile.Basename'] = $properties['Encoding.Basename'];
                    if(!empty($profile['slug'])) {
                        $properties['EncodingProfile.Basename'] .= '_' . $profile['slug'];
                    }
                }

                $properties['EncodingProfile.Slug'] = $profile['slug'];
                $properties['EncodingProfile.Extension'] = $profile['extension'];
                $properties['EncodingProfile.MirrorFolder'] = $profile['mirror_folder'];
            }

			return $properties;
		}

        private function _getProperties(Model $model, $pattern = null) {
            $properties =  $model->Properties->indexBy('name','value');

            if($pattern != null) {
                $properties->where('name ~ ?',array($pattern));
            }

            return $properties->toArray();
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
            if(!$ticket = Ticket::find(['id' => $ticket_id], ['User','Parent','Project','Properties'])) {
                throw new EntryNotFoundException(__FUNCTION__.': ticket not found',301);
            }
			if(!is_array($properties) || count($properties) < 1) {
				throw new EntryNotFoundException(__FUNCTION__.': no properties given',302);
			}

            $ticket_properties = array();
            $log_message = array();
            $log_message[] = __FUNCTION__.': changing properties';
            foreach($properties as $name => $value) {
                if(in_array($name,$this->virtual_properties)) {
                    Log::warn('[RPC] setTicketProperties: ingored virtual property '.$name);
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
                    'event' => __FUNCTION__
                ));
                return true;
            }
            return false;
		}

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
