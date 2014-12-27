<?php
	
	requires(
		'/Model/EncodingProfileProperties',
		'/Model/EncodingProfileVersion'
	);
	
	class EncodingProfile extends Model {
		
		const TABLE = 'tbl_encoding_profile';
		
		public $hasOne = [
			'LatestVersion' => [
				'class_name' => 'EncodingProfileVersion',
				'foreign_key' => ['encoding_profile_id'],
				'order_by' => 'tbl_encoding_profile_version.revision DESC',
				'join' => false
			]
		];
		
		public $hasMany = [
			'Properties' => [
				'class_name' => 'EncodingProfileProperties',
				'foreign_key' => ['encoding_profile_id']
			],
			'Versions' => [
				'class_name' => 'EncodingProfileVersion',
				'foreign_key' => ['encoding_profile_id']
			]
		];
		
		public $acceptNestedEntriesFor = [
			'Properties' => true,
			'Versions' => true // TODO: disable destroy
		];
		
		public static function with_version_count(Model_Resource $resource) {
			$resource->select(
				'*',
				EncodingProfileVersion::findAll()
					->select('COUNT(*)')
					->where('encoding_profile_id = ' . self::TABLE . '.id')
					->selectAs('versions_count')
			);
		}
		
	}
	
?>