<?php
	
	class Comment extends Model {
		
		public $table = 'tbl_comment';
		
		public $belongsTo = array('Ticket' => array(), 'User' => array('join' => true, 'fields' => 'name AS user_name'));
		
		public $validatePresenceOf = array('comment' => true);
		
	}
	
?>