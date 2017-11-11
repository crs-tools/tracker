<?php
	
	class ProjectLanguages extends Model_Properties {
		
		public $table = 'tbl_project_language';
		public $objectField = 'project_id';
		
		public $nameField = 'language';
		public $valueField = 'description';
		
		public $belongsTo = array('Project' => array());
		
	}
	
?>