<?php
	
	requires(
		'/Model/EncodingProfileVersion'
	);
	
	class EncodingProfile extends Model {
		
		const TABLE = 'tbl_encoding_profile';
		
		// TODO: hasOne LatestVersion
		
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
			// TODO: clean up if Database_Query_Abstract has support
			return self::findAll($join)
				->select('(' .
					EncodingProfileVersion::findAll(array())
						->except(array('select'))->select('COUNT(*)')
						->where('encoding_profile_id = ' . self::TABLE . '.id')
						->toString() .
				') AS versions_count');
		}
		
	}
	
?>