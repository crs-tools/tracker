<?php
	
	requires(
		'/Model/Worker',
		'/Model/ProjectWorkerGroupFilter'
	);
	
	class WorkerGroup extends Model {
		
		const TABLE = 'tbl_worker_group';
		
		public $hasMany = [
			'Worker' => [
				'foreign_key' => ['worker_group_id'],
				'order_by' => 'last_seen DESC'
			]
		];
		
		public $hasAndBelongsToMany = [
			'Project' => [
				'foreign_key' => ['project_id'],
				'self_key' => ['worker_group_id'],
				'via' => 'tbl_project_worker_group'
			]
		];
		
		public static function worker_group_filter_count(Model_Resource $resource, Project $project) {
			$resource->andSelect(
				Database_Query::selectFrom(
					ProjectWorkerGroupFilter::TABLE,
					'COUNT(*)'
				)
					->where([
						'worker_group_id = ' . self::TABLE . '.id',
						'project_id' => $project['id']
					])
					->selectAs('filter_count')
			);
		}
		
	}
	
?>