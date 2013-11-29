<?php
	
	class Worker extends Model {
		
		const TABLE = 'tbl_worker';
		
		public $belongsTo = array(
			'WorkerGroup' => array()
		);
		
	}
	
?>