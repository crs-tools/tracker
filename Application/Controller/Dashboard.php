<?php
	
	class Controller_Dashboard extends Controller_Application {
		
		public $requireAuth = true;
		
		public function index() {
			$this->View->render('projects/dashboard.tpl');
		}
		
		public function progress() {
			
		}
		
		public function actions() {
			$this->View->renderJSON(array(
				'action-count-cutting' => $this->Ticket->getRows(array('state_id' => $this->State->getIdByName('merged'), 'project_id' => $this->Project->id)),
				'action-count-checking' => $this->Ticket->getRows(array('state_id' => $this->State->getIdByName('tagged'), 'project_id' => $this->Project->id)),
				'action-count-fixing' => $this->Ticket->getRows(array('failed' => true, 'project_id' => $this->Project->id)),
				'contributor-cutting' => $this->LogEntry->getTopContributor('Action.Cut'), 
				'contributor-checking' => $this->LogEntry->getTopContributor('Action.Check'),
				'contributor-fixing' => $this->LogEntry->getTopContributor('Action.Fix') 
			));
		}
		
		public function fahrplan() {
			$fahrplan = array();
			// $current = Model::indexByField($this->Ticket->getCurrentRecordingTickets(array('project_id' => $this->Project->id), array(), 'fahrplan_id, title, time_start, time_end'), 'fahrplan_room');
			
			// var_dump()
			
			/*
			foreach (array('Saal 1' => 'schedule-room-1', 'Saal 2' => 'schedule-room-2', 'Saal 3' => 'schedule-room-3') as $room => $id) {
				$fahrplan[$id] = array(
					null,
					(isset($current[$room]))? $current[$room] : null,
					null
				);
			}
			*/
			
			/*
			foreach ($fahrplan as $type => $empty) {
				if (isset($tickets[$type])) {
					foreach ($tickets[$type] as $ticket) {
						// if (!isset($fahrplan[$type][$ticket['fahrplan_room']])) {
							$fahrplan[$type]['schedule-room-' . mb_substr($ticket['fahrplan_room'], -1)] = array(
								
							);
						// }
					}
				}
			}
			*/
			
			$this->View->renderJSON($fahrplan);
		}
		
	}
	
?>