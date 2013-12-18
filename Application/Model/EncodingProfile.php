<?php
	
	requires(
		'/Model/EncodingProfileVersion'
	);
	
	class EncodingProfile extends Model {
		
		const TABLE = 'tbl_encoding_profile';
		
		public $hasOne = array(
			'LatestVersion' => array(
				'class_name' => 'EncodingProfileVersion',
				'foreign_key' => 'encoding_profile_id',
				'order_by' => 'tbl_encoding_profile_version.revision DESC',
				'join' => false
			)
		);
		
		public $hasMany = array(
			'Versions' => array(
				'class_name' => 'EncodingProfileVersion',
				'foreign_key' => 'encoding_profile_id'
			)
		);
		
		public $acceptNestedEntriesFor = array(
			'Versions' => true // TODO: disable destroy
		);
		
		public static function findAllWithVersionCount(array $join = null) {			
			return self::findAll($join)
				->select(
					'*',
					EncodingProfileVersion::findAll(array())
						->select('COUNT(*)')
						->where('encoding_profile_id = ' . self::TABLE . '.id')
						->selectAs('versions_count')
				);
		}
		
	}
	
?>