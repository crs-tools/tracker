<?php

class LogEntry extends Model {

        const TABLE = 'tbl_log';

        public $belongsTo = array(
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