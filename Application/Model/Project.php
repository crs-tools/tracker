<?php
	
	requires(
		'Model',
		'/Model/ProjectProperties',
		'/Model/ProjectLanguages'
	);
	
	class Project extends Model {
		
		const TABLE = 'tbl_project';
		
		public $hasMany = [
			'Ticket' => ['foreign_key' => ['project_id']],
			'Properties' => [
				'class_name' => 'ProjectProperties',
				'foreign_key' => ['project_id'],
				'select' => 'name, value, SUBPATH(name, 0, 1) AS root'
			],
			'Languages' => [
				'class_name' => 'ProjectLanguages',
				'foreign_key' => ['project_id'],
				'select' => 'language, description'
			]
		];
		
		public $hasAndBelongsToMany = [
			'EncodingProfileVersion' => [
				'foreign_key' => ['encoding_profile_version_id'],
				'self_key' => ['project_id'],
				'via' => 'tbl_project_encoding_profile',
				// TODO: cleanup when Association has support (?)
				'select' => 'tbl_encoding_profile_version.*, tbl_project_encoding_profile.priority'
			],
			// TODO: move to 'through' => 'ProjectState'
			'States' => [
				'class_name' => 'TicketState',
				'foreign_key' => ['ticket_type' , 'ticket_state'],
				'self_key' => ['project_id'],
				'via' => 'tbl_project_ticket_state'
			],
			'WorkerGroup' => [
				'foreign_key' => ['worker_group_id'],
				'self_key' => ['project_id'],
				'via' => 'tbl_project_worker_group'
			]
		];
		
		public $acceptNestedEntriesFor = [
			'EncodingProfileVersion' => true,
			'Languages' => true,
			'Properties' => true,
			'States' => true,
			'WorkerGroup' => true
		];
	}
	
?>