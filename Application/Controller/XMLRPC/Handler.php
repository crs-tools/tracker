<?php
	
	class Controller_XMLRPC_Handler extends Controller_XMLRPC {
		
		public $requireAuth = true;
		
		public $prefix = 'C3TT.';
		
		public function __construct($action = null, $arguments = null) {
			// check config
			if(empty($this->Config->RPC['secret'])) {
				throw new Exception('RPC: secret missing. tracker not setup up properly',500);
			}

			// check credentials
			if (!isset($arguments['uid'])) {
				throw new ActionNotAllowedException('NO_UID', 401);
			} elseif(!$this->User->auth($arguments['uid'])) { // register unknown uid
				if(!isset($arguments['hostname']) || empty($arguments['hostname'])) {
					throw new ActionNotAllowedException('NO_HOSTNAME', 402);
				}

				$hash_compare = md5($arguments['hostname'].$this->Config->RPC['secret']);
				if($hash_compare == strtolower($arguments['uid'])) {
					// extract hostname or ip
					$name = preg_match('/^(\d{1,3}.){4}$/',$arguments['hostname']) ? $arguments['hostname'] : strstr($arguments['hostname'].'.', '.', true);

					// check whether hostname is already known
					$user = $this->User->findFirst(array(), $this->User->loginField .' = ?', array($name));
					if($user === false) {
						// create new user and log in
						$this->User->create(array('name' => $name, 'hostname' => $arguments['hostname'], 'role' => 'worker', 'password' => Random::base64(16), 'hash' => $hash_compare));
						Log::info("registered new RPC client: uid=".$arguments['uid']." hostname=".$arguments['hostname']);

						return $this->User->auth($hash_compare);
					} else {
						// update hash and hostname
						$this->User->hostname = $arguments['hostname'];
						$this->User->hash = $hash_compare;
						$this->User->save();
						Log::info('re-registered RPC client: name='.$name.' hostname='.$arguments['hostname'].' with hash='.$hash_compare);

						return $this->User->auth($hash_compare);
					}
				}
				throw new ActionNotAllowedException('BAD_LOGIN', 403);
			}
			
		}
		
		/**
		* get version string of XMLRPC API
		*
		* @return string version string
		*/
		public function getVersion() {
			return "3.0";
		}

		/**
		 * fetches list of projects
		 * 
		 * @param boolean read_only if true, finished projects get listed too (default: false)
		 * @param boolean list_encoding_profiles if true, list of encoding profiles for each project is returned (default: true)
		 * @return array project data
		 */
		public function getProjects($read_only = false, $list_encoding_profiles = true) {
			if($list_encoding_profiles) {
				$projects = $this->Project->findAll(array('Project','EncodingProfile'), array('read_only' => $read_only));
			} else {
				$projects = $this->Project->findAll(array(), array('read_only' => $read_only));
			}
			return is_array($projects) ? $projects : array();
		}

		/**
		 * returns list of states services can retrieve tickets for (merging, encoding, ....)
		 *
		 * @return array list of services
		 */
		public function getServices() {
			return array_keys($this->State->services);
		}

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
		public function ping($ticket_id = null, $log_message_delta = null, $progress = null) {
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
		}
	}
	
?>
