<?php
	
	requires(
		'Model',
		'/Model/ProjectProperties',
		'/Model/ProjectLanguages'
	);
	
	class Project extends Model {
		
		const TABLE = 'tbl_project';
		
		public $hasMany = array(
			'Ticket' => array('foreign_key' => 'project_id'),
			'Properties' => array(
				'class_name' => 'ProjectProperties',
				'foreign_key' => 'project_id',
				'select' => 'name, value'
			),
			'Languages' => array(
				'class_name' => 'ProjectLanguages',
				'foreign_key' => 'project_id',
				'select' => 'language, description'
			)
		);
		
		public $hasAndBelongsToMany = array(
			'EncodingProfileVersion' => array(
				'foreign_key' => 'encoding_profile_version_id',
				'self_key' => 'project_id',
				'via' => 'tbl_project_encoding_profile'
			),
			'TicketState' => array(
				'foreign_key' => array('ticket_type' , 'ticket_state'),
				'self_key' => 'project_id',
				'via' => 'tbl_project_ticket_state'
			),
			'WorkerGroup' => array(
				'foreign_key' => 'worker_group_id',
				'self_key' => 'project_id',
				'via' => 'tbl_project_worker_group'
			)
		);
		
		public $acceptNestedEntriesFor = array(
			'Properties' => true,
			'Languages' => true
		);
	}
	
?>