<?php
	
	class LogMessage extends Model {
		
		public $table = 'tbl_log_message';
		
		public $primaryKey = 'event';
		
		public $hasMany = array('LogEntry' => array('key' => 'event'));
		
	}
	
?>