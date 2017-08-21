<?php
	
	class ProjectTicketState extends Model {
		
		const TABLE = 'tbl_project_ticket_state';
		
		public $primaryKey = ['project_id', 'ticket_type', 'ticket_state'];
		
		public $hasMany = [
			'Ticket' => [
				'foreign_key' => ['project_id', 'ticket_type', 'ticket_state']
			]
		];
		
		public $belongsTo = [
			'State' => [
				'class_name' => 'TicketState',
				'foreign_key' => ['ticket_type', 'ticket_state'],
				'select' => 'sort'
			]
		];
		
		// TODO: use Ticket::queryNextState / queryPreviousState?
		public static function getNextState($project, $type, $state, $ticket) {
			$handle = Database::$Instance->query(
				'SELECT * FROM ticket_state_next(?, ?, ?, ?)',
				[$project, $type, $state, $ticket]
			);
			$row = $handle->fetch();
			
			return ($row === false)? null : $row;
		}
		
		public static function getPreviousState($project, $type, $state, $ticket) {
			$handle = Database::$Instance->query(
				'SELECT * FROM ticket_state_previous(?, ?, ?, ?)',
				[$project, $type, $state, $ticket]
			);
			
			$row = $handle->fetch();
			return ($row === false)? null : $row;
		}
		
		public static function getCommenceState($project, $type, $ticket) {
			$handle = Database::$Instance->query(
				'SELECT ticket_state_commence(?, ?, ?)',
				[$project, $type, $ticket]
			);
			
			return $handle->fetch()['ticket_state_commence'];
		}
		
		public static function createAll($project) {
			return (new Database_Query(self::TABLE))
				->insertFrom(TicketState::findAll()->select(
					// TODO: this needs better support
					Database::$Instance->quote($project) .
					' AS project_id, ticket_type, ticket_state, service_executable'
				))
				->execute();
		}
	}
	
?>