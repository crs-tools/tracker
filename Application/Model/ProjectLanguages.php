<?php
	
	class ProjectLanguages extends Model/*_Properties*/ {
		
		const TABLE = 'tbl_project_language';
		
		public $primaryKey = array('project_id', 'language');
		
		const CREATE_IF_NOT_EXISTS = true;
		
		public $belongsTo = array('Project' => array());
			
		public function defaultScope(Model_Resource $resource) {
			$resource->orderBy('language');
		}
		
	}
	
?>