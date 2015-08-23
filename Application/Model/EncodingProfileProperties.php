<?php
	
	class EncodingProfileProperties extends Model {
		
		const TABLE = 'tbl_encoding_profile_property';
		
		public $primaryKey = ['encoding_profile_id', 'name'];
		
		const CREATE_IF_NOT_EXISTS = true;
		
		public $belongsTo = [
			'EncodingProfile' => [
				'foreign_key' => ['encoding_profile_id']
			]
		];
		
		public function defaultScope(Model_Resource $resource) {
			$resource->orderBy('name');
		}

	}
	
?>
