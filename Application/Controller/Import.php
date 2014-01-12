<?php
	
	requires(
		'String',
		'HTTP/Client',
		'/Model/Ticket'
	);
	
	class Controller_Import extends Controller_Application {
		
		public $requireAuthorization = true;
		
		const FAHRPLAN_FILES = 'Public/fahrplan/';
		
		/*
		public $beforeFilter = true;
		
		public function beforeFilter($arguments, $action) {
			if ($this->Project->read_only) {
				$this->flash('You can\'t import tickets to this project because it\'s locked');
				$this->View->redirect('tickets', 'index', array('project_slug' => $this->Project->slug));
				return false;
			}
			
			return true;
		}
		*/
		
		public function index() {
			$this->form('import', 'rooms', $this->project);
			
			if (file_exists(ROOT . 'Public/fahrplan/')) {
				$files = $this->_getFiles();
				$this->files = (!empty($files))? array('' => '') + $files : array();
			}
			
			$this->source = $this->project->Properties->where(['name' => 'Fahrplan.XML'])->first();
			
			return $this->render('import/index.tpl');
		}
		
		public function rooms() {
			$this->reviewForm = $this->form('import', 'review', $this->project);
			$roomForm = $this->form();
			
			if (!$xml = $this->_loadXML($roomForm)) {
				return $this->redirect('import', 'index', $this->project);
			}
			
			$rooms = $xml->xpath('day/room');
			$this->rooms = [];
			
			foreach ($rooms as $room) {
				$this->rooms[(string) $room->attributes()['name']] = true;
			}
			
			requiresSession();
			
			$_SESSION['import'] = [
				'step' => 'review',
				'create_recording_tickets' => $roomForm->getValue('create_recording_tickets'),
				'create_encoding_tickets' => $roomForm->getValue('create_encoding_tickets'),
				'xml' => $xml->asXML()
			];
			
			return $this->render('import/rooms.tpl');
		}
		
		public function review() {
			if (!isset($_SESSION['import']) or $_SESSION['import']['step'] != 'review') {
				unset($_SESSION['import']);
				return $this->redirect('import', 'index', $this->project);
			}
			
			$this->form();
			$this->applyForm = $this->form('import', 'apply', $this->project);
			
			$rooms = $this->form->getValue('rooms');
			
			if (!$this->form->wasSubmitted()) {
				return $this->redirect('import', 'index', $this->project);
			}
			
			// TODO: better solution?
			$xml = simplexml_load_string($_SESSION['import']['xml']);
			
			$tickets = Ticket::findAll()
				->where([
					'ticket_type' => 'meta',
					'project_id' => $this->project['id']
				])
				->indexBy('fahrplan_id');
			$unchangedTickets = $tickets->toArray();
			
			$events = $xml->xpath('day/room/event');
			
			$this->tickets = array(
				'step' => 'apply',
				'new' => [],
				'changed' => [],
				'deleted' => [],
				'create_recording_tickets' => $_SESSION['import']['create_recording_tickets'],
				'create_encoding_tickets' => $_SESSION['import']['create_encoding_tickets']
			);
			
			foreach ($events as $event) {
				if (!$rooms[(string) $event->room]) {
					continue;
				}
				
				$properties = array();
				$attributes = $event->attributes();
				
				if (!isset($attributes['id'])) {
					// TODO: warning
					continue;
				}
				
				$properties['Fahrplan.ID'] = (int) $attributes['id'];
				
				if (isset($attributes['guid'])) {
					$properties['Fahrplan.GUID'] = (string) $attributes['guid'];
				}
				
				if (!isset($event->date)) {
					// TODO: import without date is now unsupported
					continue;
				}
				
				$properties['Fahrplan.Date'] = (new DateTime($event->date))->format('Y-m-d');
				$properties['Fahrplan.Start'] = (string) $event->start;
				
				$properties['Fahrplan.Day'] = (string) current($event->xpath('ancestor::day/@index'));
				$properties['Fahrplan.Duration'] = (string) $event->duration;
				$properties['Fahrplan.Room'] = (string) $event->room;
				
				$properties['Fahrplan.Title'] = (string) $event->title;
				$properties['Fahrplan.Subtitle'] = (string) $event->subtitle;
				$properties['Fahrplan.Slug'] = (string) $event->slug;
				
				$properties['Fahrplan.Type'] = (string) $event->type;
				$properties['Fahrplan.Track'] = (string) $event->track;
				$properties['Fahrplan.Language'] = (string) $event->language;
				
				if (isset($event->recording)) {
					$properties['Fahrplan.Recording.License'] = (string) $event->recording->license;
					
					if ((string) $event->recording->optout == 'true') {
						$properties['Fahrplan.Recording.Optout'] = '1';
					}
				}
				
				$properties['Fahrplan.Abstract'] = (string) $event->abstract;
				
				$properties['Fahrplan.Person_list'] = implode(', ', $event->xpath('persons/person'));
				
				foreach ($properties as $property => $value) {
					if (empty($value) and $value !== false) {
						unset($properties[$property]);
					}
				}
				
				if (!isset($tickets[$properties['Fahrplan.ID']])) {
					if (isset($this->tickets['new'][$properties['Fahrplan.ID']])) {
						// TODO: warning duplicate fahrplan id
						continue;
					}
					
					$this->tickets['new'][$properties['Fahrplan.ID']] = $properties;
					continue;
				}
				
				$ticketProperties = $tickets[$properties['Fahrplan.ID']]
					->Properties
					->indexBy('name', 'value')
					->toArray();
				
				$this->tickets['changed'][$properties['Fahrplan.ID']] = array(
					'properties' => $properties,
					'diff' => []
				);
				
				foreach (array_merge($properties, $ticketProperties) as $key => $value) {
					$property = [
						'fahrplan' => (isset($properties[$key]))? $properties[$key] : null,
						'database' => (isset($ticketProperties[$key]))? $ticketProperties[$key] : null
					];
					
					if ($property['fahrplan'] !== null and $property['database'] !== null and
						strcmp($property['fahrplan'], $property['database']) === 0) {
						continue;
					}
					
					$this->tickets['changed'][$properties['Fahrplan.ID']]['diff'][$key] = $property;
				}
				
				if (empty($this->tickets['changed'][$properties['Fahrplan.ID']]['diff'])) {
					// remove ticket from list, so it hides from array_diff below
					unset($unchangedTickets[$properties['Fahrplan.ID']]);
					unset($this->tickets['changed'][$properties['Fahrplan.ID']]);
				}
			}
			
			$this->tickets['deleted'] = (empty($tickets))? array() :
				array_diff_key($unchangedTickets, $this->tickets['changed']);
			// TODO: array_fill_key true
			
			if (empty($this->tickets['new']) and empty($this->tickets['changed']) and empty($this->tickets['deleted'])) {
				if (!$this->_createMissingTickets($this->tickets)) {
					$this->flash('Fahrplan has not changed since last update');
				} else {
					$this->flash('Added missing child tickets');
				}
				
				return $this->redirect('import', 'index', $this->project);
			}
			
			requiresSession();
			// TODO: use form token to support multiple imports?
			// TODO: should we add a project wide lock?
			$_SESSION['import'] = $this->tickets;
			
			return $this->render('import/review.tpl');
		}
		
		
		public function apply() {
			if (!isset($_SESSION['import']) or $_SESSION['import']['step'] != 'apply') {
				unset($_SESSION['import']);
				return $this->redirect('import', 'index', $this->project);
			}
			
			$ticketsChanged = 0;
			$tickets = $this->form()->getValue('tickets');
			
			Database::$Instance->beginTransaction();
			
			if (isset($tickets['new'])) {
				// remove unchecked entries
				$tickets['new'] = array_filter($tickets['new']);
				
				foreach (array_intersect_key($_SESSION['import']['new'], $tickets['new']) as $fahrplanID => $properties) {
					$ticketProperties = array();
					
					foreach ($properties as $key => $value) {
						$ticketProperties[] = ['name' => $key, 'value' => $value];
					}
					
					if (Ticket::create(array(
						'project_id' => $this->project['id'],
						'fahrplan_id' => $properties['Fahrplan.ID'],
						'title' => $properties['Fahrplan.Title'],
						'ticket_type' => 'meta',
						'ticket_state' => 'staging',
						'properties' => $ticketProperties
					))) {
						$ticketsChanged++;
					}
				}
			}
			
			if (isset($tickets['change'])) {
				$tickets['change'] = array_filter($tickets['change']);
				
				foreach (array_intersect_key($_SESSION['import']['changed'], $tickets['change']) as $fahrplanID => $changed) {
					$ticket = Ticket::findBy([
						'fahrplan_id' => $fahrplanID,
						'ticket_type' => 'meta',
						'project_id' => $this->project['id']
					]);
					
					$ticket['title'] = $changed['properties']['Fahrplan.Title'];
					$properties = array();
					
					foreach($changed['diff'] as $key => $property) {
						if ($property['fahrplan'] === null) {
							$properties[] = array(
								'name' => $key,
								'_destroy' => true
							);
							continue;
						}
						
						$properties[] = array(
							'name' => $key,
							'value' => $property['fahrplan']
						);
					}
					
					if (!empty($properties)) {
						$ticket['properties'] = $properties;
					}
					
					if ($ticket->save()) {
						$ticketsChanged++;
					}
				}
			}
			
			if (isset($tickets['delete'])) {
				$tickets['delete'] = array_filter($tickets['delete']);
				
				foreach (array_intersect_key($_SESSION['import']['deleted'], $tickets['delete']) as $fahrplanID => $ticket) {
					if (Ticket::delete($ticket['id'])) {
						$ticketsChanged++;
					}
				}
			}
			
			Database::$Instance->commit();
			
			$this->_createMissingTickets($_SESSION['import']);
			
			unset($_SESSION['import']);
			
			$this->flash('Updated ' . $ticketsChanged . ' ticket' . (($ticketsChanged > 1)? 's' : ''));
			return $this->redirect('import', 'index', $this->project);
		}
		
		public function _getFiles() {
			$files = array();
			
			foreach (new DirectoryIterator(ROOT . 'Public/fahrplan/') as $file) {
				if (mb_substr($file->getFilename(), -4) != '.xml') {
					continue;
				}
				
				$files[$file->getFilename()] = mb_substr($file->getFilename(), 0, -4);
			}
			
			natsort($files);
			$files = array_reverse($files,true);
			
			return $files;
		}
		
		public function _loadXML(Form $form) {
			if ($file = $form->getvalue('file')) {
				$files = $this->_getFiles();
				
				if (!isset($files[$file])) {
					$this->flash('Invalid file');
					return false;
				}
				
				$path = ROOT . self::FAHRPLAN_FILES . $file;
				
				if (!isset($path) or !is_readable($path)) {
					$this->flash('Could not read file');
					return false;
				}
				
				libxml_use_internal_errors(true);
				
				if (!$xml = simplexml_load_file(realpath($path))) {
					$errors = libxml_get_errors();
					$this->flash('Could not parse XML' . ((count($errors) > 0)? (': ' . $errors[0]->message) : ''));
					
					libxml_clear_errors();
					
					return false;
				}
				
				return $xml;
			}
			
			$client = new HTTP_Client();
			$client->setUserAgent('FeM-Tracker/1.0 (http://fem.tu-ilmenau.de)');
			
			if (file_exists(ROOT . 'Contribution/certs/cacert.pem')) {
				$client->setOption(CURLOPT_CAINFO, ROOT . 'Contribution/certs/cacert.pem');
			}
			
			$response = $client->get($form->getValue('url'));
			
			if ($response->isFailed()) {
				$this->flash('Request failed');
				return false;
			}
			
			if ($response->isNotFound()) {
				$this->flash('Request failed: file not found');
				return false;
			}
			
			if (!$xml = $response->toObject('application/xml')) {
				$this->flash('Could not parse XML');
				return false;
			}
			
			return $xml;
		}
		
		public function _createMissingTickets($tickets) {
			$result = 0;
			
			if ($tickets['create_encoding_tickets']) {
				$result |= (Ticket::createMissingEncodingTickets($this->project['id']) > 0);
			}
			
			if ($tickets['create_recording_tickets']) {
				$result |= (Ticket::createMissingRecordingTickets($this->project['id']) > 0);
			}
			
			return (bool) $result;
		}
		
	}
	
?>