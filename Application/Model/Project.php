<?php
	
	requires(
		'Model',
		'/Model/ProjectProperties',
		'/Model/ProjectLanguages'
	);
	
	class Project extends Model {
		
		const TABLE = 'tbl_project';
		
		public $hasMany = [
			'Ticket' => ['foreign_key' => ['project_id']],
			'Properties' => [
				'class_name' => 'ProjectProperties',
				'foreign_key' => ['project_id']
			],
			'Languages' => [
				'class_name' => 'ProjectLanguages',
				'foreign_key' => ['project_id'],
				'select' => 'language, description'
			],
			// Small hack, belongs to two Models, but we use project only
			'WorkerGroupFilter' => [
				'class_name' => 'ProjectWorkerGroupFilter',
				'foreign_key' => ['project_id']
			]
		];
		
		public $hasAndBelongsToMany = [
			'EncodingProfileVersion' => [
				'foreign_key' => ['encoding_profile_version_id'],
				'self_key' => ['project_id'],
				'via' => 'tbl_project_encoding_profile',
				// TODO: cleanup when Association has support (?)
				'select' => 'tbl_encoding_profile_version.*, tbl_project_encoding_profile.priority'
			],
			// TODO: move to 'through' => 'ProjectState'
			'States' => [
				'class_name' => 'TicketState',
				'foreign_key' => ['ticket_type' , 'ticket_state'],
				'self_key' => ['project_id'],
				'via' => 'tbl_project_ticket_state'
			],
			'WorkerGroup' => [
				'foreign_key' => ['worker_group_id'],
				'self_key' => ['project_id'],
				'via' => 'tbl_project_worker_group'
			]
		];
		
		public $acceptNestedEntriesFor = [
			'EncodingProfileVersion' => true,
			'Languages' => true,
			'Properties' => true,
			'States' => true,
			'WorkerGroup' => true,
			'WorkerGroupFilter' => true
		];
		
		public function hasState($type, $state) {
			return ProjectTicketState::findAll()
				->where([
					'project_id' => $this['id'],
					'ticket_type' => $type,
					'ticket_state' => $state
				])
				->count() > 0;
		}
		
		public function queryFirstState($type) {
			return ProjectTicketState::findAll()
				->where([
					'project_id' => $this['id'],
					'ticket_type' => $type
				])
				->join(['State'])
				->orderBy(TicketState::TABLE . '.sort ASC')
				->except(['fields'])
				->select('ticket_state')
				->limit(1);
		}
		
		public function updateTicketStates() {
			Database::$Instance->query(
				'SELECT update_all_tickets_progress_and_next_state(?)',
				[$this['id']]
			);
		}
		
		public function updateEncodingProfilePriority($versionId, $priority) {
			return Database_Query::updateTable(
				'tbl_project_encoding_profile',
				['priority' => $priority],
				[
					'project_id' => $this['id'],
					'encoding_profile_version_id' => $versionId
				]
			)->execute() > 0;
		}
		
		public function updateEncodingProfileVersion($versionId, $newId) {
			$query = Database_Query::updateTable(
				'tbl_project_encoding_profile',
				['encoding_profile_version_id' => $newId],
				[
					'project_id' => $this['id'],
					'encoding_profile_version_id' => $versionId
				]
			);
			
			if ($query->execute() <= 0) {
				return false;
			}
			
			Ticket::findAll()
				->where([
					'project_id' => $this['id'],
					'encoding_profile_version_id' => $versionId
				])
				->update([
					'encoding_profile_version_id' => $newId
				]);
			
			return true;
		}
		
		public function removeEncodingProfileVersion($versionId) {
			$query = Database_Query::deleteFrom(
				'tbl_project_encoding_profile',
				[
					'project_id' => $this['id'],
					'encoding_profile_version_id' => $versionId
				]
			);
			
			if ($query->execute() <= 0) {
				return false;
			}
			
			Ticket::findAll()
				->where([
					'project_id' => $this['id'],
					'encoding_profile_version_id' => $versionId
				])
				->delete();
			
			return true;
		}

		public function hasEncodingProfilePublishingURL($ticket) {
			return (
				isset($this->Properties['Publishing.Base.Url']) and
				isset($ticket->EncodingProfile->Properties['EncodingProfile.Extension'])
			);
		}

		public function getEncodingProfilePublishingURL($ticket) {
			if (!$this->hasEncodingProfilePublishingURL($ticket)) {
				return '';
			}
			
			$resource = $ticket['fahrplan_id'] .
					'-' . $ticket->EncodingProfile['slug'] .
					'.' . $ticket->EncodingProfile->Properties['EncodingProfile.Extension']['value'];

			if (
				!isset($this->Properties['Publishing.Url.Secret']) or
				!isset($this->Properties['Publishing.Url.Lifetime'])
			) {
				// create default link
				return $this->Properties['Publishing.Base.Url']['value'] . $resource;
			}
			
			// create secure link
			return self::protectNginxUrl(
				$this->Properties['Publishing.Base.Url']['value'],
				$resource,
				time() + intval($this->Properties['Publishing.Url.Lifetime']['value']),
				$this->Properties['Publishing.Url.Secret']['value']
			);
		}

		private static function protectNginxUrl($base, $resource, $expire, $secret, $remoteIp = ''){
			return $base . $resource . '?' . http_build_query([
				'md5' => rtrim(strtr(base64_encode(
					hash('md5', sprintf(
						'%d%s%s %s',
						$expire, parse_url($base, PHP_URL_PATH) . $resource, $remoteIp, $secret
					), true)
				), '+/', '-_'), '='),
				'expires' => $expire
			]);
		}
		
	}
	
?>