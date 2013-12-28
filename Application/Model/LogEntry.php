<?php

class LogEntry extends Model {

        const TABLE = 'tbl_log';

        public $belongsTo = array(
            'Handle' => array(
                'foreign_key' => 'handle_id',
                'select' => 'name AS handle_name'
            ),
            'Ticket' => array(
                'foreign_key' => 'ticket_id'
            ),
            'User' => array(
                'foreign_key' => 'handle_id',
                'select' => 'name AS user_name'
            )
        );

    }

?>