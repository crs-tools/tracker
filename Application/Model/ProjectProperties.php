<?php
	
	class ProjectProperties extends Model {
		
		const TABLE = 'tbl_project_property';
		
		public $primaryKey = ['project_id', 'name'];
		
		const CREATE_IF_NOT_EXISTS = true;
		
		public $belongsTo = [
			'Project' => [
				'foreign_key' => ['project_id']
			]
		];
		
		public function defaultScope(Model_Resource $resource) {
			$resource
				->orderBy('name')
				->indexBy('name');
		}
		
	}
	
?>