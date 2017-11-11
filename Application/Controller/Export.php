<?php
	
	class Controller_Export extends Controller_Application {
		
		public $requireAuth = true;
		
		public function __construct($action, $arguments) {
			parent::__construct($action, $arguments);
			
			$this->View->assign('projectProperties', $this->ProjectProperties->findByObject($this->Project->id));
		}
		
		public function index() {
			$profiles = $this->EncodingProfile->findAll(array(), array('project_id' => $this->Project->id), array(), null, null, 'slug, name');
			
			if (!empty($profiles)) {
				$this->View->assign('profiles', Model::indexByField($profiles, 'slug', 'name'));
			}
			
			$this->View->render('export/index.tpl');
		}
		
		public function wiki() {			
			$this->View->assign('profiles', $this->EncodingProfile->findAll(array(), array('project_id' => $this->Project->id)));
			
			// TODO: this should be more clean
			$this->View->assign('tickets', $this->Ticket->getExportable());
			$this->View->assign('encodings', Model::groupByFields($this->Ticket->findAll(array(), 'parent_id IS NOT NULL AND state_id = ?', array(23), 'fahrplan_id', null, 'fahrplan_id, encoding_profile_id'), array('fahrplan_id', 'encoding_profile_id')));
			
			if ($this->View->respondTo('txt')) {
				$this->View->contentType('text/plain', true);
				return $this->View->render('tickets/export/wiki.tpl');
			}
			
			if (!Request::isPostRequest()) {
				return $this->View->redirect('tickets', 'export', array('project_slug' => $this->Project->slug));
			}
			
			$content = $this->View->render('tickets/export/wiki.tpl', false);
			
			$baseURL = Request::post('url') . 'api.php?format=json';
			$loginURL = $baseURL . '&action=login&lgname=' . urlencode(Request::post('user')) . '&lgpassword=' . urlencode(Request::post('password'));
			
			try {
				$client = new HTTP_Client();
				$client->setUserAgent('FeM-Tracker/1.0 (http://fem.tu-ilmenau.de)');

				$result = $client->post($loginURL);
				
				if (!isset($result['login']['result']) or $result['login']['result'] != 'NeedToken') {
					throw new TicketsExportException('can\'t acquire a valid token');
				}

				$result = $client->post($loginURL . '&lgtoken=' . urlencode($result['login']['token']));
				
				if (!isset($result['login']['result']) or $result['login']['result'] != 'Success') {
					throw new TicketsExportException('username, password or token invalid');
				}
				
				$result = $client->get($baseURL . '&action=query&prop=info&intoken=edit&titles=Conference%20Recordings');
				
				if (!isset($result['query']['pages'])) {
					throw new TicketsExportException('can\'t acquire valid page info');
				}
				
				$result = current($result['query']['pages']);
				$editToken = $result['edittoken'];
				
				$raw = $client->get(Request::post('url') . 'index.php?title=Conference%20Recordings&action=raw');
				$content = preg_replace('/<!-- <released-lectures>(.*)<!-- <\/released-lectures> -->/si', $content, $raw);
				
				$result = $client->post(Request::post('url') . 'index.php?action=submit&title=Conference%20Recordings', array(
					'wpTextbox1' => $content,
					'wpEditToken' => $editToken,
					'wpSummary' => 'Ticket status update',
					'wpAutoSummary' => md5(''),
					'wpSection' => '',
					'wpStarttime' => '',
					'wpEdittime' => '',
					'wpScrolltop' => '',
					'wpSave' => 'Save page'
				));
				
				if (empty($result)) {
					$this->View->flash('Exported tickets successfully');
				}
			} catch (TicketsExportException $e) {
				$this->View->flash('Export failed' . (($e->getMessage())? ': ' . $e->getMessage() : ''), View::flashWarning);
			}
			
			return $this->View->redirect('export', 'index', array('project_slug' => $this->Project->slug));
		}
		
		public function podcast(array $arguments = array()) {
			if (Request::isPostRequest()) {
				if (!$this->EncodingProfile->findFirst(array(), array('project_id' => $this->Project->id, 'slug' => Request::post('profile')))) {
					throw new EntryNotFoundException();
				}
				
				return $this->View->redirect('export', 'podcast', array('project_slug' => $this->Project->slug, 'profile_slug' => Request::post('profile'), '.xml'));
			}
			
			if (empty($arguments['profile_slug']) or !$profile = $this->EncodingProfile->findFirst(array(), array('project_id' => $this->Project->id, 'slug' => $arguments['profile_slug']))) {
				throw new EntryNotFoundException();
			}
			
			$this->View->assign('profile', $profile);
			$this->View->assign('tickets', $this->Ticket->getExportable(array('Fahrplan.Abstract' => 'fahrplan_abstract', 'Fahrplan.Subtitle' => 'fahrplan_subtitle')));
			$this->View->assign('encodings', Model::groupByField($this->Ticket->findAll(array(), 'parent_id IS NOT NULL AND state_id = ? AND encoding_profile_id = ?', array(23, $profile['id']), null, null, 'fahrplan_id, modified'), 'fahrplan_id'));
			
			$this->View->contentType('application/rss+xml', true);
			$this->View->render('export/podcast.tpl');
		}
		
		public function feedback() {
			if (Request::isPostRequest()) {
				try {
					$client = new HTTP_Client();
					$client->setUserAgent('FeM-Tracker/1.0 (http://fem.tu-ilmenau.de)');
					$client->setAuthentication(Request::post('user'), Request::post('password'));
					
					$comments = $client->get(Request::post('url') . 's/comments');
					
					if (!empty($comments)) {
						foreach ($comments as $comment) {
							if (isset($comment['uid'])) {
								if (!$parent = $this->Ticket->find($comment['uid'], array(), array('project_id' => $this->Project->id))) {
									Log::info('Misc ticket id ' . $comment['fahrplan_id'] . ' not found in feedback tracker import');
									continue;
								}
							} else {
								if (!$parent = $this->Ticket->findFirst(array(), array('fahrplan_id' => $comment['fahrplan_id'], 'project_id' => $this->Project->id))) {
									Log::info('Ticket id #' . $comment['fahrplan_id'] . ' not found in feedback tracker import');
									continue;
								}
								
								if (!$this->Ticket->create(array(
										'parent_id' => $parent['id'],
										'project_id' => $parent['project_id'],
										'title' => Text::truncate($comment['comment'], 50),
										'fahrplan_id' => $parent['fahrplan_id'],
										'type_id' => 3,
										'priority' => 1.0,
										'state_id' => $this->State->getIdByName('open')
									))) {
									continue;
								}
								
								if ($this->Comment->create(array(
										'ticket_id' => $this->Ticket->id,
										'user_id' => 1, // FIXME: hardcoded user id, very, very bad
										'origin_user_name' => $comment['author'],
										'comment' => $comment['comment']
									))) {
									Log::info('Successfully imported comment from feedback tracker');	
								}
							}
						}
					}
					
					$tickets = $this->Ticket->findAll(array('Comment' => array('fields' => 'created, comment, user_id, origin_user_name'), 'EncodingProfile' => array('fields' => 'name AS encoding_profile_name, slug AS encoding_profile_slug')), array('project_id' => $this->Project->id), array(), null, null, 'id, fahrplan_id, state_id, type_id, title, created, modified');
					$data = array();
					
					foreach ($tickets['ticket'] as $ticket) {
						if (!isset($data['tickets'][$ticket['fahrplan_id']])) {
							$data['tickets'][$ticket['fahrplan_id']] = array('title' => '', 'state' => '', 'profiles' => array(), 'feedback' => array());
						}
						
						switch ($ticket['type_id']) {
							case 1:
								$data['tickets'][$ticket['fahrplan_id']]['title'] = $ticket['title'];
								$data['tickets'][$ticket['fahrplan_id']]['state'] = $this->State->getPublicNameById($ticket['state_id']);
								break;
							case 2:
								$data['tickets'][$ticket['fahrplan_id']]['profiles'][$ticket['encoding_profile_slug']] = array(
									// 'url' => ,
									'state' => $this->State->getPublicNameById($ticket['state_id'])
								);
								
								if (!isset($data['profiles'][$ticket['encoding_profile_slug']])) {
									$data['profiles'][$ticket['encoding_profile_slug']] = array(
										'name' => $ticket['encoding_profile_name']
									);
								}
								break;
							case 3:
								$data['tickets'][$ticket['fahrplan_id']]['feedback'][$ticket['id']] = array(
									'state' => $this->State->getPublicNameById($ticket['state_id']),
									'modified' => Date::fromString($ticket['modified'], null, 'c')->toString(),
									'comments' => array()
								);
								
								if (isset($ticket['comment'])) {
									foreach ($ticket['comment'] as $comment) {
										$data['tickets'][$ticket['fahrplan_id']]['feedback'][$ticket['id']]['comments'][] = array(
											'created' => Date::fromString($ticket['created'], null, 'c')->toString(),
											'is_feedback' => isset($comment['origin_user_name']),
											'author' => $comment['origin_user_name'],
											'comment' => $comment['comment']
										);
									}
								}
								break;
						}
					}
					
					$client->post(Request::post('url') . 's/tickets', array('data' => json_encode($data)));
					
					$this->View->flash('Synced with feedback tracker');
					return $this->View->redirect('export', 'index', array('project_slug' => $this->Project->slug));
				} catch (TicketsExportException $exception) {
				
				}
			}
		}
		
	}
	
	class TicketsExportException extends Exception {}
	
?>