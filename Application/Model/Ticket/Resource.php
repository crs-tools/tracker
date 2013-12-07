<?php
	
	requires(
		'Model/Resource',
		'/Model/TicketProperties'
	);
	
	class Ticket_Resource extends Model_Resource {
		
		public function withProperties(array $properties) {
			// $parent = $this->Parent;
			// OR ticket_id = tbl_ticket.parent_id
			
			foreach ($properties as $property => $as) {
				$this->join(
					TicketProperties::TABLE,
					'value AS ' . $as,
					'ticket_id = ' . $this->_table . '.id AND name = ?',
					array($property),
					'LEFT'
				);
			}
			
			return $this;
		}
		
		public function withDefaultProperties() {
			return $this->withProperties(array(
				'Fahrplan.Start' => 'fahrplan_start',
				'Fahrplan.Date' => 'fahrplan_date',
				'Fahrplan.Day' => 'fahrplan_day',
				'Fahrplan.Room' => 'fahrplan_room'
			));
			
			//to_timestamp((ticket_fahrplan_starttime(t.id))::double precision) AS time_start,
			//SELECT EXTRACT(EPOCH FROM (p.value::date + p2.value::time)::timestamp) INTO unixtime
		}
		
		/*
			$tickets->getProgress();	
			$tickets->orderBy('fahrplan_date, fahrplan_start, fahrplan_room DESC');
		*/
		
	}
	
?>