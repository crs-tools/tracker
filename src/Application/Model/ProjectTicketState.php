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
		
		public static function getCommenceState($ticket) {
			$handle = Database::$Instance->query(
				'SELECT ticket_state_commence(?)',
				[$ticket]
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