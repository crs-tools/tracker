<?php
	
	class ProjectProperties extends Model_Properties {
		
		public $table = 'tbl_project_property';
		public $objectField = 'project_id';
		
		public $belongsTo = array('Project' => array());
		
	}
	
?>