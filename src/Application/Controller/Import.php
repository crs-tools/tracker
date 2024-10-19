<?php
	
	requires(
		'String',
		'HTTP/Client',
		
		'/Model/Ticket',
		'/Model/Import',
		
		'/Helper/Time'
	);
	
	class Controller_Import extends Controller_Application {
		
		protected $requireAuthorization = true;
		protected $projectReadOnlyAccess = [
			'index' => true,
			'download' => true
		];
		
		public function index(array $arguments) {
			$this->form('import', 'create', $this->project);
			
			$this->previousImports = Import::findAll(['User'])
				->scoped(['without_xml'])
				->where(['project_id' => $this->project['id']])
				->whereNot(['finished' => null])
				->orderBy('created DESC');
			
			if (!isset($_GET['all'])) {
				$this->previousImports
					->limit(5);
			}
			
			$this->unfinishedImport = Import::findAll()
				->scoped(['without_xml'])
				->where([
					'finished' => null,
					'user_id' => User::getCurrent()['id'],
					'project_id' => $this->project['id']
				])
				->orderBy('created DESC')
				->limit(1)
				->first();
			
			if ($this->unfinishedImport !== null) {
				$this->continueForm = $this->form(
					'import',
					'continue_import',
					$this->unfinishedImport,
					$this->project
				);
			}
			
			return $this->render('import/index');
		}
		
		public function download(array $arguments) {
			if (!$this->respondTo('xml')) {
				return Response::error(400);
			}
			
			$import = Import::findOrThrow($arguments['id']);
			
			$this->Response->addHeader(
				'Content-Disposition',
				'attachment; filename="schedule_' .
					$project['slug'] . '_' .
					(new DateTime($import['created']))->format('Y-m-d_H-i-s') .
					'.xml"'
			);
			
			$this->Response->setContent($import['xml']);
			return $this->Response;
		}
		
		public function create() {
			$this->form();
			
			if (!$this->form->wasSubmitted()) {
				return $this->redirect('import', 'index', $this->project);
			}
			
			$xml = $this->_getXMLAndParse($this->form->getValues());
			
			if ($xml === false) {
				return $this->Response;
			}
			
			$import = new Import([
				'project_id' => $this->project['id'],
				'user_id' => User::getCurrent()['id'],
				'xml' => $xml[0],
				'version' => $xml[2]
			]);
				
			$import->saveOrThrow($this->form->getValues());
			
			return $this->rooms([], $import, $xml[1]);
		}
		
		public function repeat(array $arguments) {
			$this->import = Import::findOrThrow($arguments['id']);
			$this->form();
			
			if ($this->form->wasSubmitted()) {
				$import = $this->import->duplicate(true);
				
				if ($this->form->getValue('source') === 'url') {
					$xml = $this->_getXMLAndParse($import->toArray());
					
					if ($xml === false) {
						return $this->Response;
					}
				}
				
				$import->saveOrThrow([
					'user_id' => User::getCurrent()['id'],
					'xml' => (isset($xml))? $xml[0] : $this->import['xml'],
					'version' => (isset($xml))? $xml[2] : $this->import['version'],
					'rooms' => ($this->form->getValue('apply_rooms'))?
						$this->import['rooms'] : null,
					'finished' => null
				]);
				
				return $this->redirect(
					'import',
					'review',
					$import,
					$this->project
				);
			}
			
			return $this->render('import/repeat');
		}
		
		public function continue_import(array $arguments) {
			$import = Import::findOrThrow($arguments['id']);
			$this->form();
			
			if (!$this->form->wasSubmitted()) {
				return $this->redirect('import', 'index', $this->project);
			}
			
			if (!$this->form->getValue('cancel')) {
				return $this->redirect(
					'import',
					($import['rooms'] === null)? 'rooms' : 'review',
					$import,
					$this->project
				);
			}
			
			if ($import->destroy()) {
				$this->flash('Unfinished import canceled');
			}
			
			return $this->redirect('import', 'index', $this->project);
		}
		
		public function rooms(array $arguments, Import $import = null, SimpleXMLElement $xml = null) {
			if ($import === null) {
				$import = Import::findOrThrow($arguments['id']);
				$xml = self::_toObject($import['xml']);
			}
			
			if (!$this->_isValidStep($import, 'rooms')) {
				return $this->Request;
			}
			
			$this->reviewForm = $this->form(
				'import', 'review', $import, $this->project
			);
			$this->rooms = self::_getRooms($xml);
			$this->selectedRooms = (!empty($import['rooms']))?
				json_decode($import['rooms'], true) : [];
			
			return $this->render('import/rooms');
		}
		
		public function review(array $arguments) {
			$import = Import::findOrThrow($arguments['id']);
			
			$this->form();
			
			if ($this->form->wasSubmitted()) {
				if ($this->form->getValue('cancel')) {
					if ($import->destroy()) {
						$this->flash('Import canceled');
					}
			
					return $this->redirect('import', 'index', $this->project);
				}
				
				$rooms = $this->form->getValue('rooms');
				
				$import->save([
					'rooms' => json_encode($rooms)
				]);
			}
			
			if (!$this->_isValidStep($import, 'review')) {
				return $this->Request;
			}
			
			$rooms = json_decode($import['rooms'], true);
			
			$xml = self::_toObject($import['xml']);
			$tickets = [];
			$dayChange = null;
			
			// TODO: check if fahrplan has tickets, skip everything else otherwise
			
			if (isset($xml->conference) and isset($xml->conference->day_change)) {
				// Legacy pentabarf date style
				$dayChange = (string) $xml->conference->day_change;
			} elseif (count($xml->xpath('day/room/event/date')) <= 0) {
				// TODO: delete Import? mark failed?
				$this->flash('Import failed, fahrplan contains insufficient date information');
				return $this->redirect('import', 'index', $this->project);
			}
			
			foreach ($xml->xpath('day/room/event') as $event) {
				// Skip rooms not selected
				if (empty($rooms[(string) $event->room])) {
					continue;
				}
				
				try {
					if (
						!isset($event->date) and
						isset($event->start) and
						$dayChange !== null
					) {
						$event->date = new SimpleXMLElement(
							'<date>' . self::_calculateDate($event, $dayChange) . '</date>'
						);
					}
					
					$ticket = Ticket::fromFahrplanEvent($event);
					
					if (isset($tickets[$ticket['fahrplan_id']])) {
						throw new TicketFahrplanException('duplicate fahrplan id');
					}
					
					$tickets[$ticket['fahrplan_id']] = $ticket;
				} catch (TicketFahrplanException $e) {
					// TODO: show error message, missing attribute or similar
					continue;
				}
			}
			
			$this->currentState = Ticket::findAll()
				->select('id, fahrplan_id, title') /* ,date */
				->where([
					'project_id' => $this->project['id'],
					'ticket_type' => 'meta'
				])
				->indexBy('id');
			
			// new, changed and deleted tickets
			$diff = [
				'new' => null,
				'changed' => [],
				'deleted' => []
			];
			
			foreach ($this->currentState as $ticket) {
				if (!isset($tickets[$ticket['fahrplan_id']])) {
					$ticket['_destroy'] = true;
					$diff['deleted'][] = $ticket;
					continue;
				}
				
				$changes = $ticket->diffWithProperties(
					$tickets[$ticket['fahrplan_id']]
				);
				
				if ($changes !== null) {
					$diff['changed'][] = $changes;
				}
				
				unset($tickets[$ticket['fahrplan_id']]);
			}
			
			$diff['new'] = $tickets;
			
			$this->applyForm = $this->form(
				'import', 'apply', $import, $this->project
			);
			
			$changes = [];
			
			foreach (['new', 'changed', 'deleted'] as $type) {
				foreach ($diff[$type] as $ticket) {
					$ticket = $ticket->toArray();
					
					if ($type === 'new') {
						$ticket['ticket_type'] = 'meta';
						
						$changes['+' . $ticket['fahrplan_id']] = $ticket;
						continue;
					}
					
					$changes['~' . $ticket['id']] = $ticket;
				}
			}
			
			$import->save([
				'changes' => json_encode($changes)
			]);
			
			$this->diff = $diff;
			return $this->render('import/review');
		}
		
		
		public function apply(array $arguments) {
			$import = Import::findOrThrow($arguments['id']);
			
			if (!$this->_isValidStep($import, 'apply')) {
				return $this->Request;
			}
			
			$this->form();
			
			if (!$this->form->wasSubmitted()) {
				return $this->redirect(
					'import', 'review', $import, $this->project
				);
			}
			
			$changes = json_decode($import['changes'], true);
			
			if ($this->form->getValue('selected')) {
				$changes = array_intersect_key(
					$changes,
					array_filter($this->form->getValue('selected'))
				);
			} else {
				$changes = [];
			}
			
			Database::$Instance->beginTransaction();
			
			if (!empty($changes)) {
				foreach ($changes as $ticket) {
					$ticket = new Ticket(array_merge(
						[
							'project_id' => $this->project['id'],
							'import_id' => $import['id']
						],
						$ticket
					));
					
					if (!empty($ticket['_destroy'])) {
						$ticket->destroy();
						continue;
					}
					
					// TODO: catch
					$ticket->saveOrThrow();
				}
			}
			
			$import['finished'] = new DateTime();
			
			if (Database::$Instance->commit() and $import->save()) {
				$this->flash('Finished import successfully');
			}
			
			Ticket::createMissingRecordingTickets($this->project['id']);
			Ticket::createMissingEncodingTickets($this->project['id']);
			
			return $this->redirect('import', 'index', $this->project);
		}
		
		private function _isValidStep(Import $import, $step) {
			if ($import['finished'] !== null) {
				$this->flash('Import was already finished');
				$this->redirect('import', 'index', $this->project);
				return false;
			}
			
			if ($step === 'rooms') {
				return true;
			}
			
			if ($import['rooms'] === null) {
				$this->flash('Please select rooms first');
				$this->redirect('import', 'rooms', $import, $this->project);
				return false;
			}
			
			if ($step === 'review') {
				return true;
			}
			
			if ($import['changes'] === null) {
				$this->flash('Please review changes');
				$this->redirect('import', 'review', $import, $this->project);
				return false;
			}
			
			return true;
		}
		
		private function _getXMLAndParse(array $import) {
			$response = self::_getXML($import);
			
			if ($response === null or $response->isFailed()) {
				$this->flash('Import failed (unknown reason)');
				$this->redirect('import', 'index', $this->project);
				return false;
			}
			
			if ($response->isNotFound()) {
				$this->flash('Import failed (file not found)');
				$this->redirect('import', 'index', $this->project);
				return false;
			}
			
			$xml = self::_toObject($response);
			
			if (!$xml instanceOf SimpleXMLElement) {
				$this->flash('Failed to parse XML (' . $xml . ')');
				$this->redirect('import', 'index', $this->project);
				return false;
			}
			
			// TODO: check basic structure? DTD?
			
			return [
				(string) $response,
				$xml,
				// TODO: version may not exit, enable null
				(string) $xml->version
			];
		}
		
		private static function _getXML(array $import) {
			if (empty($import['url'])) {
				return null;
			}
			
			$client = new HTTP_Client();
			$client->setUserAgent('FeM-Tracker/1.0 (http://fem.tu-ilmenau.de)');
			
			if (!empty($import['auth_type'])) {
				switch ($import['auth_type']) {
					case 'basic':
						$client->setAuthentication(
							(isset($import['auth_user']))? $import['auth_user'] : '',
							(isset($import['auth_password']))? $import['auth_password'] : ''
						);
						break;
					case 'header_authentication':
						$client->addHeader(
							'Authentication',
							(isset($import['auth_header_authentication']))? $import['auth_header_authentication'] : ''
						);
						break;
					case 'header_authorization':
						$client->addHeader(
							'Authorization',
							(isset($import['auth_header_authorization']))? $import['auth_header_authorization'] : ''
						);
						break;
				}
			}
			
			if (file_exists(ROOT . '../contribution/certs/cacert.pem')) {
				$client->setOption(
					CURLOPT_CAINFO,
					ROOT . '../contribution/certs/cacert.pem'
				);
			}
			
			return $client->get($import['url']);
		}
		
		private static function _toObject($string) {
			libxml_use_internal_errors(true);
			
			if ($xml = simplexml_load_string($string)) {
				return $xml;
			}
			
			$errors = libxml_get_errors();
			libxml_clear_errors();
			
			if (count($errors) <= 0) {
				return '';
			}
			
			return $errors[0]->message;
		}
		
		private static function _getRooms(SimpleXMLElement $xml) {
			$rooms = [];
			
			foreach ($xml->xpath('day/room') as $room) {
				$rooms[] = (string) $room->attributes()['name'];
			}
			
			return array_unique($rooms);
		}
		
		// Calculates a legacy (Pentabarf) date from date, start and day_change
		private static function _calculateDate(SimpleXMLElement $event, $dayChange) {
			$date = (string) current($event->xpath('ancestor::day/@date'));
			
			$dateTime = new DateTime($date . ' ' . (string) $event->start);
			$dayChangeDateTime = new DateTime($date . ' ' . $dayChange);
			
			if ($dateTime < $dayChange) {
				$dateTime->modify('+1 day');
			}
			
			return $dateTime->format(DateTime::ISO8601);
		}
	}
	
?>
