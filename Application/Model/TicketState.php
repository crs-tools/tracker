<?php
	
	class TicketState extends Model {
		
		const TABLE = 'tbl_ticket_state';
		
		public $primaryKey = ['ticket_type', 'ticket_state'];
		
		public $hasOne = [
			'ProjectTicketState' => [
				'foreign_key' => ['ticket_type', 'ticket_state'],
				'select' => '(ticket_state IS NOT NULL) AS project_enabled, service_executable AS project_service_executable'
			]	
		];
		
		public $hasMany = [
			'Ticket' => [
				'foreign_key' => ['ticket_type', 'ticket_state']
			]
		];
		
		protected static $_actions = [
			'cut' => 'cutting',
			'check' => 'checking'
		];
		
		public static function getStateByAction($action) {
			if (!isset(self::$_actions[$action])) {
				return false;
			}
			
			return self::$_actions[$action];
		}
		
		/*
		public $actions = array(
			'cut' => array(
				array('from' => 6, 'from_failed' => false, 'failed' => 7, 'to' => 9, 'to_failed' => true, 'state' => 7)
			),
			'check' => array(
				array('from' => 17, 'from_failed' => false, 'failed' => 14, 'to' => 19, 'to_failed' => true, 'state' => 18, 'reset' => true)
			),
			'release' => array(
				array('from' => 22, 'from_failed' => false, 'to' => 23)
			),
			'fix' => array(
				array('from' => 7, 'from_failed' => true, 'to' => 4, 'state' => 8)
			),
			'handle' => array(
				array('from' => 25, 'from_failed' => false, 'to' => 27, 'failed' => 28, 'to_failed' => false, 'state' => 26)
			)
		);
		
		public $services = array(
			'recording' => array(
				'from' => 2, // scheduled
				'to' => 4, // recorded
				'state' => 3 // recording
			),
			'merging' => array(
				'from' => 4, // recorded
				'to' => 6, // merged
				'state' => 5 // merging
			),
			'copying' => array(
				'from' => 9, // cut
				'to' => 11, // copied
				'state' => 10 // copying
			),
			'encoding' => array(
				'from' => 13, // ready to encode
				'to' => 15, // encoded
				'state' => 14 // encoding
			),
			'tagging' => array(
				'from' => 15, // encoded
				'to' => 17, // tagged
				'state' => 16 // tagging
			),
			'postprocessing' => array(
				'from' => 19, // checked
				'to' => 21, // postprocessed
				'state' => 20 // postprocessing
			),
			'releasing' => array(
				'from' => 22, // ready to release
				'to' => 24, // released
				'state' => 23 // releasing
			)
		);
		
		private $_states = array('byId' => array(), 'byName' => array());
		
		public function __construct() {
			parent::__construct();
			
			if (!$this->_states = $this->Cache->get('states')) {
				foreach ($this->findAll(array(), null, array(), 'id') as $state) {
					$this->_states['byId'][$state['id']] = $state;
					
					if (isset($this->_states['byName'][$state['name']])) {
						$this->_states['byName'][$state['name']] = array($this->_states['byName'][$state['name']], $state['id']);
					} else {
						$this->_states['byName'][$state['name']] = $state['id'];
					}
				}
				
				$this->Cache->set('states', $this->_states);
			}
		}
		
		public function getAction($action, $ticket) {
			if (!isset($this->actions[$action])) {
				return false;
			}
			
			if (!isset($ticket['state_id']) or !isset($ticket['failed'])) {
				return false;
			}
			
			foreach ($this->actions[$action] as $option) {
				if (($option['from'] == $ticket['state_id'] and ($ticket['failed'] xor !$option['from_failed'])) or ($option['state'] == $ticket['state_id'] and !$ticket['failed'])) {
					return $option;
				}
			}
			
			return false;
		}
		
		public function isEligibleAction($action, $ticket) {
			return $this->getAction($action, $ticket) !== false;
		}
		
		public function isResetable($ticket) {
			if (!isset($ticket['state_id'])) {
				return false;
			}
			
			// TODO: check if it's useful to reset released tickets (include 22-24?)
			return $ticket['state_id'] >= $this->getIdByName('encoded') and $ticket['state_id'] <= $this->getIdByName('postprocessed');
		}
		
		public function getService($service) {
			if (!isset($this->services[$service])) {
				return false;
			}
			
			return $this->services[$service];
		}
		
		public function getServiceByTicket($ticket) {
			if (!isset($ticket['state_id'])) {
				return false;
			}
			
			foreach ($this->services as $service) {
				if ($ticket['state_id'] == $service['from'] or $ticket['state_id'] == $service['state']) {
					return $service;
				}
			}
			
			return false;
		}
		
		public function getIdByName($name) {
			if (!isset($this->_states['byName'][$name])) {
				return false;
			}
			
			return $this->_states['byName'][$name];
		}
		
		public function getIdsByName(array $names) {
			$ids = array();
			
			foreach ($names as $name) {
				if (isset($this->_states['byName'][$name])) {
					if (is_array($this->_states['byName'][$name])) {
						$ids = array_merge($ids, $this->_states['byName'][$name]);
					} else {
						$ids[] = $this->_states['byName'][$name];
					}
				}
			}
			
			return $ids;
		}
		
		public function getNameById($id) {
			if (!isset($this->_states['byId'][$id])) {
				return false;
			}
			
			return $this->_states['byId'][$id]['name'];
		}
		
		public function getPublicNameById($id) {
			switch ($id) {
				case 1:
					return 'locked';
				case 2:
					return 'scheduled';
				case 3:
					return 'recording';
				case 4:
					return 'recorded';
				case 5: // merging
				case 6: // merged
				case 7: // cutting
				case 9: // cut
				case 10: // copying
					return 'processing';
				case 8:
					return 'fixing';
				case 11: // copied
					return 'ready';
				case 12: // material needed
					return null;
				case 13:
					return 'ready to encode';
				case 14: // encoding
				case 15: // encoded
				case 16: // tagging
				case 17: // tagged
				case 18: // checking
				case 19: // checked
					return 'encoding';
				case 20: //postprocessing';
				case 21: // postprocessed';
				case 22: // ready to release';
				case 23: // releasing';
					return 'releasing';
				case 24:
					return 'released';
				case 25:
					return 'open';
				case 26:
					return 'inprogress';
				case 27:
					return 'resolved';
				case 28:
					return 'wontfix';
				default:
					return null;
			}
		}
		
		public function getTypeById($id) {
			if (!isset($this->_states['byId'][$id])) {
				return false;
			}
			
			return $this->_states['byId'][$id]['ticket_type_id'];
		}
		
		protected function afterSave() {
			$this->Cache->remove('states');
			return true;	
		}
		*/
		
	}
	
?>