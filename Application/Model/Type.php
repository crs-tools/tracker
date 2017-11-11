<?php
	
	class Type extends Model {
		
		public $table = 'tbl_ticket_type';
		
		public $hasMany = array('Ticket' => array());
		
	}
	
?>