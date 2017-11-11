<?php
	
	requires(
		'/Model/Ticket'
	);
	
	class Worker extends Model {
		
		const TABLE = 'tbl_worker';
		
		public static function assigned(Model_Resource $resource, $project) {
			$resource
				->join(Ticket::TABLE, [
					static::TABLE . '.id = handle_id',
					'project_id' => $project
				]);
		}
		
	}
	
?>