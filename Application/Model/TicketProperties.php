<?php
	
	class TicketProperties extends Model/*_Properties*/ {
		
		const TABLE = 'tbl_ticket_property';
		
		public $primaryKey = array('ticket_id', 'name');
		
		const CREATE_IF_NOT_EXISTS = true;
		
		public $belongsTo = array('Ticket' => array('foreign_key' => 'ticket_id'));
		
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
		
		public static function delayToMilliseconds($delay) {
			return ((float)$delay) * 1000;
		}
		
		public static function millisecondsToDelay($milliseconds) {
			return $milliseconds / 1000;
		}
		*/
		
	}
	
?>