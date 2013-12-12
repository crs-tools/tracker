<?php
	
	class Controller_Export extends Controller_Application {
		
		public $requireAuth = true;
		
		public function index() {
			$this->wikiForm = $this->form('export', 'wiki', $this->project);
			$this->podcastForm = $this->form('export', 'podcast', $this->project);
			
			$this->projectProperties = $this->project
				->Properties
				->indexBy('name', 'value')
				->toArray();
			
			/*
			$profiles = $this->EncodingProfile->findAll(array(), array('project_id' => $this->Project->id), array(), null, null, 'slug, name');
			
			if (!empty($profiles)) {
				$this->View->assign('profiles', Model::indexByField($profiles, 'slug', 'name'));
			}
			*/
			
			return $this->render('export/index.tpl');
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
					// throw new TicketsExportException('can\'t acquire a valid token');
				}

				$result = $client->post($loginURL . '&lgtoken=' . urlencode($result['login']['token']));
				
				if (!isset($result['login']['result']) or $result['login']['result'] != 'Success') {
					// throw new TicketsExportException('username, password or token invalid');
				}
				
				$result = $client->get($baseURL . '&action=query&prop=info&intoken=edit&titles=Conference%20Recordings');
				
				if (!isset($result['query']['pages'])) {
					// throw new TicketsExportException('can\'t acquire valid page info');
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
					$this->flash('Exported tickets successfully');
				}
			} catch (TicketsExportException $e) {
				// $this->flash('Export failed' . (($e->getMessage())? ': ' . $e->getMessage() : ''), self::FLASH_WARNING);
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
		
	}
	
?>