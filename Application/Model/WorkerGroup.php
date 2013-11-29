<?php
	
	class WorkerGroup extends Model {
		
		const TABLE = 'tbl_worker_group';
		
		public $hasMany = array(
			'Worker' => array()
		);
		
		public $hasAndBelongsToMany = array(
			'Project' => array(
				'foreign_key' => 'project_id',
				'self_key' => 'worker_group_id',
				'via' => 'tbl_project_worker_group'
			)
		);
		
	}
	
?>