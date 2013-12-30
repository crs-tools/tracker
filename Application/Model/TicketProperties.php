<?php

    requires (
        'String'
    );

	class TicketProperties extends Model/*_Properties*/ {
		
		const TABLE = 'tbl_ticket_property';
		
		public $primaryKey = array('ticket_id', 'name');
		
		const CREATE_IF_NOT_EXISTS = true;
		
		public $belongsTo = array('Ticket' => array('foreign_key' => 'ticket_id'));

        public static function buildSlug(Model $project, $properties = array()) {
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
            $slug = preg_replace(['/[.:"]/','/[^a-zA-Z_\-0-9]/','/_+/'],['','_','_'],iconv("utf-8","ascii//TRANSLIT",$properties['Fahrplan.Title']));
            array_push($parts, $slug);

            return implode('-', $parts);
        }
		
		/*
		public function getFilename($properties = array()) {
			$parts = array();

			// prepend project slug if active project available
			if($this->Project->current()) {
				$parts[] = $this->Project->current()->slug;
			}

			if(isset($properties['Fahrplan.ID'])) {
				$parts[] = $properties['Fahrplan.ID'];
			}

			// add language if project has multiple languages
			if($this->Project->current() and count($this->Project->current()->languages) > 0) {
				if(!isset($properties['Record.Language'])) {
					// error: language is not set, return empty string
					return '';
				} else {
					$parts[] = $properties['Record.Language'];
				}
			}

			if(isset($properties['Fahrplan.Slug'])) {
				$parts[] = $properties['Fahrplan.Slug'];
			}

			return implode('-', $parts);
		}
		*/
		
	}
	
?>