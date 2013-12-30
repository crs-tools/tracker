<?php
	
	requires(
		'/Model/Project',
		'/Model/Ticket'
	);
	
	class Controller_API extends Controller {
		
		protected $beforeAction = ['setProject' => true];
		
		protected function setProject($action, array $arguments) {
			if (!isset($arguments['project_slug'])) {
				return;
			}
			
			$this->project = Project::findBy(['slug' => $arguments['project_slug']]);
		}
		
		public function tickets_fahrplan() {
			$tickets = Ticket::findAll()
				->select('id, fahrplan_id, title')
				->where([
					'project_id' => $this->project['id'],
					'ticket_type' => 'meta'
				])
				->scoped([
					'with_default_properties',
					'order_list'
				]);
			
			return $this->_respond($tickets);
		}
		
		protected function _respond($data) {
			if ($data instanceOf Model_Resource) {
				$data = $data->toArray();
			}
			
			if ($this->respondTo('json')) {
				$this->Response->setContent(json_encode($data));
				return $this->Response;
			}
			
			return Response::error(400);
		}
		
	}
	
?>