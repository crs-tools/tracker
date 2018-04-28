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
		
		public function getFilteredTickets(array $projects, Model_Resource $tickets) {
			$filtersByProject = $this->_findWorkerGroupFiltersByProject($projects);
			$filteredTickets = [];
			
			foreach ($tickets as $ticket) {
				$properties = $ticket->MergedProperties->toArray();
				
				if (empty($filtersByProject[$ticket['project_id']])) {
					continue;
				}
				
				$filters = self::_evaluateFilters(
					$filtersByProject[$ticket['project_id']],
					$properties
				);
				
				if (!empty($filters)) {
					$filteredTickets[$ticket['parent_id']] = $filters;
				}
			}
			
			return $filteredTickets;
		}
		
		public function filterTickets(array $projects, Model_Resource $tickets) {
			$filtersByProject = $this->_findWorkerGroupFiltersByProject($projects);
			
			$tickets->filter(function(array $entry) use ($tickets, $filtersByProject) {
				if (empty($filtersByProject[$entry['project_id']])) {
					return true;
				}
				
				// TODO: not so beautiful hack, can we get a Model object as argument?
				$ticket = new Ticket();
				$ticket->init($entry);
				
				return (self::_evaluateFilters(
					$filtersByProject[$ticket['project_id']],
					$ticket->MergedProperties->toArray()
				) === []);
			});
		}
		
		private function _findWorkerGroupFiltersByProject(array $projects) {
			return (new Model_Resource_Grouped(
				ProjectWorkerGroupFilter::findAll()
					->where([
						'worker_group_id' => $this['id'],
						'project_id' => $projects
					]),
				'project_id'
			))
				->toArray();
		}
		
		private static function _evaluateFilters(array $filters, array $properties) {
			$valid = false;
			$unmatchedFilters = [];
			
			foreach ($filters as $filter) {
				if (
					isset($properties[$filter['property_key']]) and
					(string) $properties[$filter['property_key']]['value'] === $filter['property_value']
					or
					!isset($properties[$filter['property_key']]) and $filter['property_value'] === ''
				) {
					// TODO: for "NOT"/"NONE": set $filter here, break
					$valid = true;
					continue;
				}
				
				$unmatchedFilters[] = $filter;
				// TODO: for "AND" set $valid=false and break here
				// $valid = false;
				// break;
			}
			
			if ($valid) {
				return [];
			}
			
			return $unmatchedFilters;
		}
		
	}
	
?>