<?php
	
	requires(
		'/Model/TicketProperties',
		'/Model/TicketState',
		'/Model/ProjectTicketState',
		
		'/Model/LogEntry'
	);
	
	class Ticket extends Model {
		
		const TABLE = "tbl_ticket";
		
		const CLASS_RESOURCE = 'Ticket_Resource';
		
		public $hasOne = [
			'RecordingTicket' => [
				'class_name' => 'Ticket',
				'foreign_key' => ['parent_id'],
				'where' => ['ticket_type' => 'recording']
			]
		];
		
		public $hasMany = [
			'Children' => [
				'class_name' => 'Ticket',
				'foreign_key' => ['parent_id']
			],
			'Comments' => [
				'class_name' => 'Comment',
				'foreign_key' => ['ticket_id']
			],
			'LogEntries' => [
				'class_name' => 'LogEntry',
				'foreign_key' => ['ticket_id']
			],
			'Properties' => [
				'class_name' => 'TicketProperties',
				'foreign_key' => ['ticket_id'],
				'select' => 'name, value, SUBPATH(name, 0, 1) AS root'
			]
		];
		
		public $belongsTo = [
			'EncodingProfileVersion' => [
				'foreign_key' => ['encoding_profile_version_id']
			],
            'Handle' => [
                'foreign_key' => ['handle_id'],
                'select' => 'name AS handle_name'
            ],
			'Parent' => [
				'class_name' => 'Ticket',
				'foreign_key' => ['parent_id'],
				'join' => false
			],
            'Project' => [
                'foreign_key' => ['project_id']
            ],
			'State' => [
				'class_name' => 'ProjectTicketState',
				'primary_key' => ['project_id', 'ticket_type', 'ticket_state'],
				'foreign_key' => ['project_id', 'ticket_type', 'ticket_state']
			],
			'User' => [
				'foreign_key' => ['handle_id'],
				'select' => 'name as user_name'
			],
			'Worker' => [
				'foreign_key' => ['handle_id'],
                'select' => 'name as worker_name'
			]
		];
		
		public $acceptNestedEntriesFor = [
			'Properties' => true
		];
		
		public $scopes = [
			'filter_recording',
			'filter_cutting',
			'filter_encoding',
			'filter_releasing',
			'filter_released',
			'filter_state',
			'filter_handle',
			
			'order_list',
			'order_priority',
			
			'with_child',
			'with_default_properties',
			'with_encoding_profile_name',
			'with_progress',
			'with_properties',
			'with_recording',
			'without_locked'
		];
		
		public static function filter_recording(Model_Resource $resource, array $arguments) {
			self::filter_state($resource, [
				'type' => 'recording',
				'states' => ['locked', 'scheduled', 'recording', 'recorded', 'preparing']
			]);
		}
		
		public static function filter_cutting(Model_Resource $resource, array $arguments) {
			self::filter_state($resource, [
				'type' => 'recording',
				'states' => ['prepared', 'cutting', 'cut']
			]);
		}
		
		public static function filter_encoding(Model_Resource $resource, array $arguments) {
			self::filter_state($resource, [
				'type' => 'encoding',
				'states' => ['ready to encode', 'encoding', 'encoded', 'postencoding']
			]);
		}
		
		public static function filter_releasing(Model_Resource $resource, array $arguments) {
			self::filter_state($resource, [
				'type' => 'encoding',
				'states' => [
					'postencoded',
					'checking',
					'checked',
					'postprocessing',
					'postprocessed',
					'ready to release',
					'releasing'
				]
			]);
		}
		
		public static function filter_released(Model_Resource $resource, array $arguments) {
			self::filter_state($resource, [
				'type' => 'encoding',
				'states' => ['released']
			]);
		}
		
		public static function filter_state(Model_Resource $resource, array $arguments) {
			$marks = substr(str_repeat('?,', count($arguments['states'])), 0, -1);
			$resource->where(
				'(' . self::TABLE . '.ticket_type = ? AND ' .
				self::TABLE . '.ticket_state IN (' . $marks . ')) OR ' .
				'(child.ticket_type = ? AND child.ticket_state IN (' . $marks . '))',
				array_merge(
					[$arguments['type']],
					$arguments['states'],
					[$arguments['type']],
					$arguments['states']
				)
			);
		}
		
		public static function filter_handle(Model_Resource $resource, array $arguments) {
			if (!isset($arguments['handle'])) {
				return;
			}
			
			$resource->where(
				self::TABLE . '.handle_id = ? OR child.handle_id = ?',
				[$arguments['handle'], $arguments['handle']]
			);
		}
		
		public static function order_list(Model_Resource $resource, array $arguments) {
			$resource
				->andSelect(
					'COALESCE(' . self::TABLE . '.parent_id, ' .
						self::TABLE . '.id) AS sort_id'
				)
				->orderBy(
					'fahrplan_date, fahrplan_start, fahrplan_room,
					 sort_id, parent_id DESC, ticket_type, title'
				);
				// to_timestamp((ticket_fahrplan_starttime(t.id))::double precision) AS time_start,
				// SELECT EXTRACT(EPOCH FROM (p.value::date + p2.value::time)::timestamp) INTO unixtime
		}
		
		public static function order_priority(Model_Resource $resource, array $arguments) {
			$resource->orderBy(
				'CASE WHEN child.id IS NULL
					THEN ticket_priority(id)
					ELSE ticket_priority(child.id)
				 END DESC,
				 COALESCE(parent_id, id), COALESCE(child.id, id),
				 parent_id DESC
			');
		}
		
		public static function with_child(Model_Resource $resource, array $arguments) {
			$resource->leftJoin(
				[self::TABLE, 'child'],
				null,
				[self::TABLE . '.id = parent_id']
			);
		}
		
		public static function with_default_properties(Model_Resource $resource, array $arguments) {
			self::with_properties($resource, [
				'Fahrplan.Start' => 'fahrplan_start',
				'Fahrplan.Date' => 'fahrplan_date',
				'Fahrplan.Day' => 'fahrplan_day',
				'Fahrplan.Room' => 'fahrplan_room'
			]);
		}
		
		
		public function with_encoding_profile_name(Model_Resource $resource, array $arguments) {
			$resource->leftJoin(
				[EncodingProfileVersion::TABLE, 'encoding_profile_version'],
				null,
				[self::TABLE . '.encoding_profile_version_id = id']
			);
			$resource->leftJoin(
				[EncodingProfile::TABLE],
				'name AS encoding_profile_name',
				['encoding_profile_version.encoding_profile_id = id']
			);
		}
		
		public static function with_progress(Model_Resource $resource, array $arguments) {
			$resource->andSelect(
				self::TABLE . '.*, ticket_progress(' . self::TABLE . '.id) AS progress'
			);
		}
		
		public static function with_properties(Model_Resource $resource, array $arguments) {
			foreach ($arguments as $property => $as) {
				$resource->leftJoin(
					TicketProperties::TABLE,
					'value AS ' . $as,
					'((ticket_id = ' .
						self::TABLE .
						'.id AND ' . 
						self::TABLE .
						'.parent_id IS NULL) OR (ticket_id = ' .
						self::TABLE .
						'.parent_id AND ' .
						self::TABLE .
						'.parent_id IS NOT NULL)) AND name = ?',
					[$property]
				);
			}
		}
		
		public static function with_recording(Model_Resource $resource, array $arguments) {
			$resource->leftJoin(
				[self::TABLE, 'recording'],
				null,
				['(' . self::TABLE . '.ticket_type = ? AND id IS NULL) OR (' . self::TABLE . '.parent_id = parent_id AND ticket_type = ?) OR (' . self::TABLE . '.id = parent_id AND ticket_type = ?)'],
				['recording', 'recording', 'recording']
			);
		}
		
		public static function without_locked(Model_Resource $resource, array $arguments) {
			$resource->where('ticket_state != ? AND (recording.id IS NULL or recording.ticket_state != ?)', ['locked', 'locked']);
		}
		
		public static function createMissingRecordingTickets($project) {
			$handle = Database::$Instance->query(
				'SELECT create_missing_recording_tickets(?)',
				[$project]
			);
			
			return $handle->fetch()['create_missing_recording_tickets'];
		}
		
		public static function createMissingEncodingTickets($project, $encodingProfile = null) {
			$handle = Database::$Instance->query(
				'SELECT create_missing_encoding_tickets(?, ?)',
				[$project, $encodingProfile]
			);
			
			return $handle->fetch()['create_missing_encoding_tickets'];
		}
		
		public function isEligibleAction($action) {
			switch ($action) {
				case 'edit':
				case 'delete':
					return true;
					break;
				case 'duplicate':
					return ($this['ticket_type'] === 'meta');
					break;
			}
			
			if (!$state = TicketState::getStateByAction($action)) {
				return false;
			}
			
			if ($this['ticket_state'] == $state) {
				return true;
			}
			
			return (ProjectTicketState::getNextState(
				$this['project_id'],
				$this['ticket_type'],
				$this['ticket_state']
			)['ticket_state'] == $state);
		}
		
		public function expandRecording(array $expand) {
			if ($this['ticket_type'] != 'recording') {
				return false;
			}
			
			$existingProperties = $this->Properties->indexBy('name', 'value');
			$properties = [];
			
			if ($expand[0] > 0) {
				if (isset($existingProperties['Record.StartPadding'])) {
					$expand[0] += (int) $existingProperties['Record.StartPadding'];
				}
				
				$properties[] = [
					'name' => 'Record.StartPadding',
					'value' => $expand[0]
				];
			}
			
			if ($expand[1] > 0) {
				if (isset($existingProperties['Record.EndPadding'])) {
					$expand[1] += (int) $existingProperties['Record.EndPadding'];
				}
				
				$properties[] = [
					'name' => 'Record.EndPadding',
					'value' => $expand[1]
				];
			}
			
			return $this->save([
				'properties' => $properties,
				'ticket_state' => $this->queryPreviousState('preparing'),
				'handle_id' => null,
				'failed' => false
			]);
		}
		
		public function queryPreviousState($state = null) {
			return (new Database_Query(''))
				->select('ticket_state')
				->from(
					'ticket_state_previous(?, ?, ?)',
					'previous_state',
					[
						$this['project_id'],
						$this['ticket_type'],
						($state === null)? $this['ticket_state'] : $state
					]
				);
		}
		
		public function queryNextState($state = null) {
			return (new Database_Query(''))
				->select('ticket_state')
				->from(
					'ticket_state_next(?, ?, ?)',
					'next_state',
					[
						$this['project_id'],
						$this['ticket_type'],
						($state === null)? $this['ticket_state'] : $state
					]
				);
		}
		
		public static function countByNextState($project, $ticketType, $ticketState) {
			return Ticket::findAll()
				->where([
					'ticket_type' => $ticketType,
					'project_id' => $project
				])
				->where(
					'(SELECT next.ticket_state FROM ticket_state_next(?, ticket_type, ticket_state) AS next) = ?',
					[$project, $ticketState]
				)
				->count();
		}
		
		public static function getTotalProgress($project) {
			return (float) self::findAll()
				->select('SUM(ticket_progress(id)) / COUNT(id) AS progress')
				->where([
					'project_id' => $project,
					'ticket_type' => 'meta',
					'ticket_state' => 'staged'
				])
				->fetchRow()['progress'];
		}
		
		/*
		public function findAbandonedByState($state, $timeout = null, $limit = null) {
			$query = 'SELECT
						t.*,
						age(u.last_seen) as not_seen_for
					FROM
						tbl_ticket t
					JOIN
						tbl_user u ON u.id = t.user_id
					LEFT JOIN
						tbl_ticket p ON p.id = t.parent_id
					WHERE
						t.state_id = :state_id AND
						t.project_id = :project_id AND
						t.failed IS NOT TRUE AND
						p.failed IS NOT TRUE AND
						u.role = :role AND
						AGE(u.last_seen) > :timeout
					ORDER BY
						not_seen_for DESC';
			if(!empty($limit)) {
				$query .= ' LIMIT '.$limit;
			}
			return $this->findBySQL($query, ['state_id' => $state, 'project_id' => $this->Project->current(]->id, 'role' => 'worker', 'timeout' => $timeout), []);
		}
		
		public function resetRecordingTask($id) {
			if (!$this->Database->query(Database_Query::updateTable($this->table, ['state_id' => $this->State->getIdByName('cutting'], 'failed' => true, 'user_id' => null), ['id' => $id, 'type_id' => 1]))) {
				return false;
			}
			
			$this->LogEntry->create(array(
				'ticket_id' => $id,
				'to_state_id' => $this->State->getIdByName('cutting'),
				'event' => 'Recording.Reset'
			));
			
			if (!$this->Database->query(Database_Query::updateTable($this->table, ['state_id' => $this->State->getIdByName('material needed'], 'failed' => false, 'user_id' => null), ['parent_id' => $id, 'type_id' => 2]))) {
				return false;
			}
			
			$this->Database->query(Database_Query::selectFrom($this->table, 'id', ['parent_id' => $id, 'state_id' => $this->State->getIdByName('material needed'], 'failed' => false)));
			
			foreach ($this->Database->fetch() as $encodingTask) {
				$this->LogEntry->create(array(
					'ticket_id' => $id,
					'to_state_id' => $this->State->getIdByName('material needed'),
					'event' => 'Encoding.Parent.Reset'	
				));
			}
			
			return true;
		}
		
		public function resetEncodingTask($id) {
			if (!$this->Database->query(Database_Query::updateTable($this->table, ['state_id' => $this->State->getIdByName('ready to encode'], 'failed' => false, 'user_id' => null), ['id' => $id, 'type_id' => 2]))) {
				return false;
			}
			
			$this->LogEntry->create(array(
				'ticket_id' => $id,
				'to_state_id' => $this->State->getIdByName('material needed'),
				'event' => 'Encoding.Reset'
			));
			
			return true;
		}
		
		public function afterCreate() {
			$this->LogEntry->create(array(
				'ticket_id' => $this->id,
				'to_state_id' => $this->state_id,
				'event' => 'Created'
			));
			
			return true;
		}
		*/
	}
	
?>