<?php

class ProjectTicketState extends Model {

        const TABLE = 'tbl_project_ticket_state';

        public $primaryKey = array('project_id', 'ticket_type', 'ticket_state');

        public $hasMany = array(
            'Ticket' => array(
                'foreign_key' => array('project_id', 'ticket_type', 'ticket_state')
            )
        );

        public function getNextState() {
            Database::$Instance->query('SELECT * FROM ticket_state_next(?, ?, ?)', [$this['project_id'], $this['ticket_type'], $this['ticket_state']]);
            return Database::$Instance->fetchRow();
        }

        public function getPreviousState() {
            Database::$Instance->query('SELECT * FROM ticket_state_previous(?, ?, ?)', [$this['project_id'], $this['ticket_type'], $this['ticket_state']]);
            return Database::$Instance->fetchRow();
        }
}

?>