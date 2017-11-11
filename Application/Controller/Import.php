<?php
	
	class Controller_Import extends Controller_Application {
		
		public $requireAuth = true;
		public $beforeFilter = true;
		
		public function beforeFilter($arguments, $action) {
			if ($this->Project->read_only) {
				$this->View->flash('You can\'t import tickets to this project because it\'s locked');
				$this->View->redirect('tickets', 'index', array('project_slug' => $this->Project->slug));
				return false;
			}
			
			return true;
		}
		
		public function index() {
			if (file_exists(ROOT . 'Public/fahrplan/')) {
				$files = $this->_getFiles();
				$this->View->assign('files', (!empty($files))? array('' => '') + $files : array());
			}
			
			$this->View->assign('projectProperties', $this->ProjectProperties->findByObject($this->Project->id));
			$this->View->render('import/index.tpl');
		}
		
		public function review() {
			if (!Request::isPostRequest()) {
				return $this->View->redirect('import', 'index', array('project_slug' => $this->Project->slug));
			}
			
			try {
				if (Request::post('file')) {
					$files = $this->_getFiles();
					
					if (!isset($files[Request::post('file')])) {
						throw new TicketsImportException('invalid file supplied');
					}
					
					$path = ROOT . 'Public/fahrplan/' . Request::post('file');
					
					if (!is_readable($path)) {
						throw new TicketsImportException('XML file not readable');
					}
					
					$xml = simplexml_load_file($path);
				} else {
					$curl = curl_init(Request::post('url'));
					
					curl_setopt_array($curl, array(
						CURLOPT_USERAGENT => 'FeM-Tracker/1.0 (http://fem.tu-ilmenau.de)',
						CURLOPT_RETURNTRANSFER => true
					));
					
					if (!$xml = curl_exec($curl)) {
						throw new TicketsImportException('Request to XML URL failed');
					}
					
					$xml = simplexml_load_string($xml);
				}
				
				if (!is_object($xml)) {
					throw new TicketsImportException('Couldn\'t parse fahrplan XML');
				}
				
				$version = (string)current($xml->xpath('conference/release'));
				$day_change = (string)current($xml->xpath('conference/day_change'));
				$events = $xml->xpath('day/room/event');
				$events_array = array();
				
				if (!is_array($events)) {
					throw new TicketsImportException('No events found in XML');
				}
				
				foreach ($events as $event) {
					$new_event = array();

					$id = (int)intval(current($event->xpath('@id')));
					$new_event['Fahrplan.ID'] = $id;

					$time_date = (string)current($event->xpath('ancestor::day/@date'));
					$time_start = (string)current($event->xpath('start'));
					
					$dateTime_start = new DateTime($time_date . ' ' . $time_start);
					if($dateTime_start < new DateTime($time_date . ' ' . $day_change)) { // same "day" but next day of week
						$time_date = $dateTime_start->modify('+1 day')->format('Y-m-d');
					}
					
					$new_event['Fahrplan.Date'] = $time_date;
					$new_event['Fahrplan.Start'] = $time_start;
					$new_event['Fahrplan.Day'] = (string)current($event->xpath('ancestor::day/@index'));
					$new_event['Fahrplan.Duration'] = (string)current($event->xpath('duration'));
					$new_event['Fahrplan.Room'] = (string)current($event->xpath('room'));
					
					if ((int)substr($new_event['Fahrplan.Room'], 5) > 10) {
						continue;
					}

					$new_event['Fahrplan.Slug'] = (string)current($event->xpath('slug'));
					$new_event['Fahrplan.Title'] = (string)current($event->xpath('title'));
					$new_event['Fahrplan.Subtitle'] = (string)current($event->xpath('subtitle'));
					$new_event['Fahrplan.Track'] = (string)current($event->xpath('track'));
					$new_event['Fahrplan.Type'] = (string)current($event->xpath('type'));
					$new_event['Fahrplan.Language'] = (string)current($event->xpath('language'));

					$new_event['Fahrplan.Abstract'] = (string)current($event->xpath('abstract'));
					$new_event['Fahrplan.Person_list'] = implode(', ', $event->xpath('persons/person'));

					foreach ($new_event as $key => $value) { // delete empty properties
						if (strlen($value) == 0) {
							unset($new_event[$key]);
						}
					}

					if (!array_key_exists($id, $events_array)) { // check for duplicates
						$events_array[$id] = $new_event;
					} else {
						Log::debug('duplicate event with id: ' . $id);
					}
				}
				$tickets_added = array();
				$tickets_updated = array();

				$tickets = $this->Ticket->indexByField($this->Ticket->findAll(array(), array('type_id' => 1, 'project_id' => $this->Project->id)), 'fahrplan_id');

				if(!$tickets) {
					$tickets = array();
				}

				foreach ($events_array as $id => $event) {
					if (!array_key_exists($id, $tickets)) {
						$tickets_added[$id] = $event;
					} else {
						$ticket = $tickets[$id];
						
						$ticket_properties = $this->Properties->findByObject($ticket['id'], 'name ~ ?', array('Fahrplan.*'));
						if ($ticket_properties === false) {
							$ticket_properties = array();
						}
						
						$all_properties = array_merge($event, $ticket_properties);
						
						$ticket['properties'] = array();
						$updated = false;
						foreach ($all_properties as $key => $value) {
							$property = array();
							$property['fahrplan'] = array_key_exists($key, $event)? $event[$key] : null;
							$property['database'] = array_key_exists($key, $ticket_properties)? $ticket_properties[$key] : null;
							$property['equals'] = (strcmp($property['fahrplan'], $property['database']) == 0)? true : false;
							$ticket['properties'][$key] = $property;
							$updated |= !$property['equals'];
						}
						
						// remove ticket from list, so it hides from array_diff below
						if ($updated) {
							$tickets_updated[$id] = $ticket;
						} else {
							unset($tickets[$id]);
						}
						
					}
				}
				
				$tickets_deleted = array_diff_key($tickets, $tickets_added, $tickets_updated);
				
				if (empty($tickets_added) and empty($tickets_updated) and empty($tickets_deleted)) {
					$this->View->flash('No changes found');
					return $this->View->redirect('import', 'index', array('project_slug' => $this->Project->slug));
				}
				
				$_SESSION['import'] = $tickets = array(
					'added' => $tickets_added,
					'updated' => $tickets_updated,
					'deleted' => $tickets_deleted
				);
				
				$this->View->assign('tickets', $tickets);
				return $this->View->render('import/review.tpl');
			} catch (TicketsImportException $e) {
				$this->View->flashNow('Import failed. ' . $e->getMessage(), View::flashWarning);
			}
		}
		
		public function apply() {
			if (!isset($_SESSION['import'])) {
				return $this->View->redirect('import', 'index', array('project_slug' => $this->Project->slug));
			}
			
			$this->Database->beginTransaction();
			
			$count = 0;
			$checked_add = Request::post('ticket_add');
			$checked_update = Request::post('ticket_update');
			$checked_delete = Request::post('ticket_delete');

			if ($checked_add) {
				$to_add = array_intersect_key($_SESSION['import']['added'], $checked_add);
				foreach ($to_add as $id => $event) {
					$this->Ticket->clear();
					try {
						$this->Ticket->project_id = $this->Project->id;
						$this->Ticket->fahrplan_id = $id;

						$this->Ticket->title = $event['Fahrplan.Title'];
						$this->Ticket->priority = 1.0;
						$this->Ticket->state_id = 2; // state_id = 2: recording ticket set to scheduled
						$this->Ticket->type_id = $this->State->getTypeById($this->Ticket->state_id);
						
						if (!$this->Ticket->save()) {
							continue;
						}
						
						$this->Properties->clear();
						$this->Properties->set($event);
						$this->Properties->ticket_id = $this->Ticket->id;
						$this->Properties->save();
						$count++;
					} catch (SqlException $e) {
						Log::warn('import failed (action = insert) for event with id: ' . $id . "\n" . $e->getMessage());
					}
				}
			}
			
			if ($checked_update) {
				$to_update = array_intersect_key($_SESSION['import']['updated'], $checked_update);
				foreach ($to_update as $id => $event) {
					$ticket = $this->Ticket->findFirst(array(), array('fahrplan_id' => $id, 'type_id' => 1, 'project_id' => $this->Project->id));
					$this->Properties->clear();
					if (!$ticket) {
						Log::warn('Ticket ' . $id . 'not found');
						continue;
					}
					try {
						$properties = array();
						foreach($event['properties'] as $key => $value) {
							$properties[$key] = $value['fahrplan'];
						}

						$this->Ticket->title = $properties['Fahrplan.Title'];
						if (!$this->Ticket->save()) {
							continue;
						}
						
						$this->Properties->findByObject($ticket['id'],'name ~ ?', array('Fahrplan.*'));
						$this->Properties->ticket_id = $ticket['id'];
						$this->Properties->set($properties);
						$this->Properties->save();

						$count++;
					} catch (SqlException $e) {
						Log::warn('import failed (action = update) for event with id: ' . $id . "\n" . $e->getMessage());
					}
				}
			}
						
			if ($checked_delete) {
				$to_delete = array_intersect_key($_SESSION['import']['deleted'], $checked_delete);
				foreach ($to_delete as $id => $event) {
					$ticket = $this->Ticket->findFirst(array(), array('fahrplan_id' => $id, 'type_id' => 1, 'project_id' => $this->Project->id));
					if (!$ticket) {
						Log::warn('Ticket ' . $id . 'not found');
						continue;
					}
					try {
						$this->Ticket->delete();
						$count++;
					} catch (SqlException $e) {
						Log::warn('import failed (action = delete) for event with id: ' . $id . "\n" . $e->getMessage());
					}
				}
			}
			
			$this->Database->commit();
			
			unset($_SESSION['import']);
			
			$this->View->flash('Updated ' . $count . ' ticket' . (($count > 1)? 's' : ''));
			$this->View->redirect('import', 'index', array('project_slug' => $this->Project->slug));
		}
		
		public function _getFiles() {
			$files = array();
			
			foreach (new DirectoryIterator(ROOT . 'Public/fahrplan/') as $file) {
				if (mb_substr($file->getFilename(), -4) == '.xml') {
					$files[$file->getFilename()] = mb_substr($file->getFilename(), 0, -4);
				}
			}
			
			natsort($files);
			$files = array_reverse($files,true);
			
			return $files;
		}
		
	}
	
	class TicketsImportException extends Exception {}
	
?>