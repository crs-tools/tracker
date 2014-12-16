<?php
	
	class ProjectWorkerGroupFilter extends Model {
		
		const TABLE = 'tbl_project_worker_group_filter';
		
		public $primaryKey = [
			'project_id',
			'worker_group_id',
			'property_key'
		];
		
		const CREATE_IF_NOT_EXISTS = true;
		
	}
	
?>