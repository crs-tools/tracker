<?php
	
	class EncodingProfileVersion extends Model {
		
		const TABLE = 'tbl_encoding_profile_version';
		
		public $belongsTo = [
			'EncodingProfile' => [
				'foreign_key' => ['encoding_profile_id']/*,
				'select' => 'name AS encoding_profile_name'*/
			],
		];
		
		public $hasMany = [
			'Ticket' => [
				'foreign_key' => ['encoding_profile_id']
			]
		];
		
		public $hasAndBelongsToMany = [
			'Project' => []
		];
		
	}
	
?>