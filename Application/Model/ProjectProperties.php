<?php
	
	class ProjectProperties extends Model/*_Properties*/ {
		
		const TABLE = 'tbl_project_property';
		
		public $primaryKey = array('project_id', 'name');
		
		const CREATE_IF_NOT_EXISTS = true;
		
		public $belongsTo = array('Project' => array());
		
	}
	
?>