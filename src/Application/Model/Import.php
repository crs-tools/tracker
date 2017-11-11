<?php
	
	class Import extends Model {
		
		const TABLE = 'tbl_import';
		
		public $belongsTo = [
			'User' => [
				'foreign_key' => ['user_id'],
				'select' => 'name AS user_name'
			]
		];
		
		public function without_xml(Model_Resource $resource) {
			$resource->select('id, user_id, url, version, rooms, created, finished');
		}
		
	}
	
?>