<?php

	requires (
		'String'
	);

	class TicketProperties extends Model {
		
		const TABLE = 'tbl_ticket_property';
		
		public $primaryKey = ['ticket_id', 'name'];
		
		const CREATE_IF_NOT_EXISTS = true;
		
		public $belongsTo = [
			'Ticket' => [
				'foreign_key' => ['ticket_id']
			]
		];
		
		// TODO: add some magic to handle changing Fahrplan.Id Property?
		
		public static function findUniqueValues($property, $projectId) {
			return TicketProperties::findAll()
				->withoutDefaultScope()
				->distinct()
				->join(Ticket::TABLE, [
					'id = ' . self::TABLE . '.ticket_id',
					'project_id' => $projectId
				])
				->where(['name' => $property])
				->orderBy('value');
		}
		
		public static function buildSlug(Model $project, $properties = []) {
			$parts = [
				$properties['Project.Slug']
			];

			if (isset($properties['Fahrplan.ID'])) {
				$parts[] = $properties['Fahrplan.ID'];
			}

			// add language if project has multiple languages
			if (count($project->Languages) > 0 && isset($properties['Record.Language'])) {
				$parts[] = $properties['Record.Language'];
			}

			// generate slug from ticket title (and ignore the one from the frab)
			 $parts[] = preg_replace([
				'/[.:"\']/',
				'/[^a-zA-Z_\-0-9]/',
				'/_+/'
			],[
				'',
				'_',
				'_'
			], iconv(
				'utf-8',
				'ascii//TRANSLIT//IGNORE',
				$properties['Fahrplan.Title']
			));

			return implode('-', $parts);
		}
		
		public function defaultScope(Model_Resource $resource) {
			$resource->orderBy('name');
		}
	}
	
?>