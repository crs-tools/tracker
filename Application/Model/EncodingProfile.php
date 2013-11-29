<?php
	
	class EncodingProfile extends Model {
		
		const TABLE = 'tbl_encoding_profile';
		
		public $hasMany = array(
			'Version' => array(
				'class_name' => 'EncodingProfileVersion',
				'foreign_key' => 'encoding_profile_id'
			)
		);
		
	}
	
?>