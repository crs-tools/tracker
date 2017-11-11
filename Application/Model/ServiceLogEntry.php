<?php
	
	class ServiceLogEntry extends Model {
		
		public $table = 'tbl_log_service';
		
		public $belongsTo = array('Ticket' => array(), 'User' => array('join' => true, 'fields' => 'name AS user_name'));
		
		public function create(array $data = array(), $save = true, $clear = true) {
			if (empty($data['user_id'])) {
				$data['user_id'] = $this->User->get('id');
			}
			
			return parent::create($data, $save, $clear);
		}
		
	}
	
?>