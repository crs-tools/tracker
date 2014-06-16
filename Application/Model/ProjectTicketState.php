<?php
	
	class ProjectTicketState extends Model {
		
        const TABLE = 'tbl_project_ticket_state';
		
        public $primaryKey = array('project_id', 'ticket_type', 'ticket_state');
		
        public $hasMany = array(
            'Ticket' => array(
                'foreign_key' => array('project_id', 'ticket_type', 'ticket_state')
            )
        );
		
        public function nextState() {
            return self::getNextState($this['project_id'], $this['ticket_type'], $this['ticket_state']);
        }
		
        public function previousState() {
            return self::getPreviousState($this['project_id'], $this['ticket_type'], $this['ticket_state']);
        }
		
        public static function getNextState($project, $type, $state) {
			return Cache::get(
				Cache::ns('project.' . $project . '.states') .
					'.' . $type . '.' . $state . '.next',
				function() use ($project, $type, $state) {
		            $handle = Database::$Instance->query(
						'SELECT * FROM ticket_state_next(?, ?, ?)',
						[$project, $type, $state]
					);
		            $row = $handle->fetch();
					
					return ($row === false)? null : $row;
				}
			);
        }
		
        public static function getPreviousState($project, $type, $state) {
			return Cache::get(
				Cache::ns('project.' . $project . '.states') .
					'.' . $type . '.' . $state . '.previous',
				function() use ($project, $type, $state) {
		            $handle = Database::$Instance->query(
						'SELECT * FROM ticket_state_previous(?, ?, ?)',
						[$project, $ticket, $state]
					);
					
		            $row = $handle->fetch();
					return ($row === false)? null : $row;
				}
			);
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