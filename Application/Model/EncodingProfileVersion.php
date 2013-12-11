<?php
	
	class EncodingProfileVersion extends Model {
		
		const TABLE = 'tbl_encoding_profile_version';
		
		public $belongsTo = array(
			'EncodingProfile' => array(
				'foreign_key' => 'encoding_profile_id'/*,
				'select' => 'name AS encoding_profile_name'*/
			),
		);
		
		public $hasMany = array(
			'Ticket' => array('foreign_key' => 'encoding_profile_id')
		);
		
		public $hasAndBelongsToMany = array(
			'Project' => array()
		);
		
	}
	
?>