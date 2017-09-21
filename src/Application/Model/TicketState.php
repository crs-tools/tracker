<?php
	
	class TicketState extends Model {
		
		const TABLE = 'tbl_ticket_state';
		
		public $primaryKey = ['ticket_type', 'ticket_state'];
		
		public $hasOne = [
			'ProjectTicketState' => [
				'foreign_key' => ['ticket_type', 'ticket_state'],
				'select' => '(ticket_state IS NOT NULL) AS project_enabled,' .
					'service_executable AS project_service_executable,' .
					'skip_on_dependent AS project_skip_on_dependent'
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

		public function isSkippable() {
			return ($this['ticket_state'] === 'postencoded' ||
				$this['ticket_state'] === 'checking');
		}
		
		public function defaultScope(Model_Resource $resource) {
			$resource->orderBy('ticket_type, sort');
		}
	}
	
?>