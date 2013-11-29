<?php
	
	class TicketState extends Model {
		
		const TABLE = 'tbl_ticket_state';
		
		public $primaryKey = array('ticket_type', 'ticket_state');
		
		public $hasMany = array(
			'Tickets' => array(
				'foreign_key' => array('ticket_type', 'ticket_state')
			)
		);
		
	}
	
?>