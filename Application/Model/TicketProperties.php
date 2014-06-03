<?php

    requires (
        'String'
    );

	class TicketProperties extends Model {
		
		const TABLE = 'tbl_ticket_property';
		
		public $primaryKey = ['ticket_id', 'name'];
		
		const CREATE_IF_NOT_EXISTS = true;
		
		public $belongsTo = ['Ticket' => ['foreign_key' => ['ticket_id']]];

        public static function buildSlug(Model $project, $properties = []) {
            $parts = array();

            if(isset($properties['Meta.Acronym'])) {
                array_push($parts, $properties['Meta.Acronym']);
            } else {
                array_push($parts, $project['slug']);
            }

            if(isset($properties['Fahrplan.ID'])) {
                array_push($parts, $properties['Fahrplan.ID']);
            }

            // add language if project has multiple languages
            if(count($project->Languages) > 0 && isset($properties['Record.Language'])) {
                array_push($parts, $properties['Record.Language']);
            }

            // generate slug from ticket title (and ignore the one from the frab)
             array_push($parts, preg_replace([
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
			)));

            return implode('-', $parts);
        }
		
		public function defaultScope(Model_Resource $resource) {
			$resource->orderBy('name');
		}
	}
	
?>