<?php
	
	requires(
		'/Model/Worker'
	);
	
	class WorkerGroup extends Model {
		
		const TABLE = 'tbl_worker_group';
		
		public $hasMany = [
			'Worker' => [
				'foreign_key' => ['worker_group_id'],
				'order_by' => 'last_seen DESC'
			]
		];
		
		public $hasAndBelongsToMany = [
			'Project' => [
				'foreign_key' => ['project_id'],
				'self_key' => ['worker_group_id'],
				'via' => 'tbl_project_worker_group'
			]
		];
		
	}
	
?>