<?php
	
	class ProjectProperties extends Model {
		
		const TABLE = 'tbl_project_property';
		
		public $primaryKey = array('project_id', 'name');
		
		const CREATE_IF_NOT_EXISTS = true;
		
		public $belongsTo = array('Project' => array());
		
		public function defaultScope(Model_Resource $resource) {
			$resource->orderBy('name');
		}
		
	}
	
?>