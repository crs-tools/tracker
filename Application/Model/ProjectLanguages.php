<?php
	
	class ProjectLanguages extends Model {
		
		const TABLE = 'tbl_project_language';
		
		public $primaryKey = ['project_id', 'language'];
		
		const CREATE_IF_NOT_EXISTS = true;
		
		public $belongsTo = [
			'Project' => [
				'foreign_key' => ['project_id']
			]
		];
		
		public function defaultScope(Model_Resource $resource) {
			$resource->orderBy('language');
		}
		
	}
	
?>