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
		
		public $hasMany = array(
			'Children' => array(
				'class_name' => 'Ticket',
				'foreign_key' => 'parent_id'
			),
			'Comments' => array(
				'class_name' => 'Comment',
				'foreign_key' => 'ticket_id'
			),
			'LogEntries' => array(
				'class_name' => 'LogEntry',
				'foreign_key' => 'ticket_id'
			),
			'Properties' => array(
				'class_name' => 'TicketProperties',
				'foreign_key' => 'ticket_id',
				'select' => 'name, value, SUBPATH(name, 0, 1) AS root'
			)
		);
		
		public $belongsTo = array(
			'EncodingProfileVersion' => array(
				'foreign_key' => 'encoding_profile_version_id'
			),
            'Handle' => array(
                'foreign_key' => 'handle_id',
                'select' => 'name AS handle_name'
            ),
			'Parent' => array(
				'class_name' => 'Ticket',
				'foreign_key' => 'parent_id',
				'join' => false
			),
            'Project' => array(
                'foreign_key' => 'project_id'
            ),
			'State' => array(
				'class_name' => 'ProjectTicketState',
				'primary_key' => array('project_id', 'ticket_type', 'ticket_state'),
				'foreign_key' => array('project_id', 'ticket_type', 'ticket_state')
			),
			'User' => array(
				'foreign_key' => 'handle_id',
				'select' => 'name as user_name'
			),
			'Worker' => array(
				'foreign_key' => 'handle_id',
                'select' => 'name as worker_name'
			)
		);
		
		public $acceptNestedEntriesFor = array(
			'Properties' => true
		);
		
		public $scopes = array(
			'filter_recording',
			'filter_cutting',
			'filter_encoding',
			'filter_releasing',
			'filter_handle',
			
			'order_list',
			
			'with_child',
			'with_default_properties',
			'with_progress',
			'with_properties',
			'with_recording',
			'without_locked'
		);
		
		public static function filter_recording(Model_Resource $resource, array $arguments) {
			$resource->where(
				'(' . self::TABLE . '.ticket_type = ? AND ' .
				self::TABLE . '.ticket_state IN (?,?,?,?,?)) OR ' .
				'(child.ticket_type = ? AND child.ticket_state IN (?,?,?,?,?))',
				[
					'recording',
					'locked', 'scheduled', 'recording', 'recorded', 'preparing',
					'recording',
					'locked', 'scheduled', 'recording', 'recorded', 'preparing',
				]
			);
		}
		
		public static function filter_cutting(Model_Resource $resource, array $arguments) {
			$resource->where(
				'(' . self::TABLE . '.ticket_type = ? AND ' .
				self::TABLE . '.ticket_state IN (?,?,?)) OR ' .
				'(child.ticket_type = ? AND child.ticket_state IN (?,?,?))',
				[
					'recording',
					'prepared', 'cutting', 'cut',
					'recording',
					'prepared', 'cutting', 'cut'
				]
			);
		}
		
		public static function filter_encoding(Model_Resource $resource, array $arguments) {
			$resource->where(
				'(' . self::TABLE . '.ticket_type = ? AND ' .
				self::TABLE . '.ticket_state IN (?,?,?,?,?)) OR ' .
				'(child.ticket_type = ? AND child.ticket_state IN (?,?,?,?,?))',
				[
					'encoding',
					'ready to encode', 'encoding', 'encoded', 'postencoding', 'postencoded',
					'encoding',
					'ready to encode', 'encoding', 'encoded', 'postencoding', 'postencoded'
				]
			);
		}
		
		public static function filter_releasing(Model_Resource $resource, array $arguments) {
			$resource->where(
				'(' . self::TABLE . '.ticket_type = ? AND ' .
				self::TABLE . '.ticket_state IN (?,?,?,?,?,?,?)) OR ' .
				'(child.ticket_type = ? AND child.ticket_state IN (?,?,?,?,?,?,?))',
				[
					'encoding',
					'postencoded', 'checking', 'checked', 'postprocessing', 'postprocessed', 'ready to release', 'releasing',
					'encoding',
					'postencoded', 'checking', 'checked', 'postprocessing', 'postprocessed', 'ready to release', 'releasing'
				]
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
		
		public function order_list(Model_Resource $resource, array $arguments) {
			$resource->orderBy(
				'fahrplan_date, fahrplan_start, fahrplan_room, fahrplan_id, parent_id DESC, title'
			);
			
			//to_timestamp((ticket_fahrplan_starttime(t.id))::double precision) AS time_start,
			//SELECT EXTRACT(EPOCH FROM (p.value::date + p2.value::time)::timestamp) INTO unixtime
		}
		
		public static function with_child(Model_Resource $resource, array $arguments) {
			$resource->join(
				[self::TABLE, 'child'],
				null,
				[self::TABLE . '.id = parent_id'],
				[],
				'LEFT'
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
		
		public static function with_progress(Model_Resource $resource, array $arguments) {
			$resource->select(self::TABLE . '.*, ticket_progress(' . self::TABLE . '.id) AS progress');
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
			Database::$Instance->query('SELECT create_missing_recording_tickets(?)', [$project]);
			return Database::$Instance->fetchRow()['create_missing_recording_tickets'];
		}
		
		public static function createMissingEncodingTickets($project, $encodingProfile = null) {
			Database::$Instance->query('SELECT create_missing_encoding_tickets(?, ?)', [$project, $encodingProfile]);
			return Database::$Instance->fetchRow()['create_missing_encoding_tickets'];
		}
		
		public function isEligibleAction($action) {
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
			
			$existingProperties = $this->properties->indexBy('name', 'value');
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
		
		/*
		public function findUnassignedByState($state, $limit = null) {
			$query = 'SELECT
						t.*,
						getTicketPriority(t.id) * e.priority as priority_product,
						(SELECT count(l.id) FROM tbl_log l WHERE l.ticket_id = t.id AND l.event = \'Script.Ticket.setTicketFailed\' AND l.user_id = :user_id) as fail_count
					FROM
						tbl_ticket t
					LEFT JOIN
						tbl_ticket p ON p.id = t.parent_id
					LEFT JOIN
						tbl_encoding_profile e ON e.id = t.encoding_profile_id
					WHERE
						t.user_id IS NULL AND
						t.state_id = :state_id AND
						t.project_id = :project_id AND
						t.failed IS NOT TRUE AND
						p.failed IS NOT TRUE AND
						(e.id IS NULL OR e.approved IS TRUE)
					ORDER BY
						fail_count ASC,
						priority_product DESC';
			if(!empty($limit)) {
				$query .= ' LIMIT '.$limit;
			}
			return $this->findBySQL($query, array('user_id' => $this->User->id, 'state_id' => $state, 'project_id' => $this->Project->current()->id), array());
		}
		
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
			return $this->findBySQL($query, array('state_id' => $state, 'project_id' => $this->Project->current()->id, 'role' => 'worker', 'timeout' => $timeout), array());
		}
		
		public function getTimeline($id) {
			$log = $this->LogEntry->findByTicketId($id, array('LogMessage', 'User'), 'created DESC');
			$comments = $this->Comment->findAll(array('User'), array('ticket_id' => $id), array(), 'created DESC');
			
			$timeline = array();
			$i = 0;
			
			if (!empty($comments)) {
				foreach ($comments as $comment) {
					// TODO: is there a better way to compare the dates than cast the dates to Date objects?
					while (isset($log[$i]) and strtotime($log[$i]['created']) >= strtotime($comment['created'])) {
						$log[$i]['type'] = 'log';
						$timeline[] = $log[$i];
						$i++;
					}

					$comment['type'] = 'comment';
					$timeline[] = $comment;
				}
			}
			
			if (!empty($log)) {
				for (;$i < count($log); $i++) {
					$log[$i]['type'] = 'log';
					$timeline[] = $log[$i];
				}
			}
			
			return $timeline;
		}
		
		public function resetRecordingTask($id) {
			if (!$this->Database->query(Database_Query::updateTable($this->table, array('state_id' => $this->State->getIdByName('cutting'), 'failed' => true, 'user_id' => null), array('id' => $id, 'type_id' => 1)))) {
				return false;
			}
			
			$this->LogEntry->create(array(
				'ticket_id' => $id,
				'to_state_id' => $this->State->getIdByName('cutting'),
				'event' => 'Recording.Reset'
			));
			
			if (!$this->Database->query(Database_Query::updateTable($this->table, array('state_id' => $this->State->getIdByName('material needed'), 'failed' => false, 'user_id' => null), array('parent_id' => $id, 'type_id' => 2)))) {
				return false;
			}
			
			$this->Database->query(Database_Query::selectFrom($this->table, 'id', array('parent_id' => $id, 'state_id' => $this->State->getIdByName('material needed'), 'failed' => false)));
			
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
			if (!$this->Database->query(Database_Query::updateTable($this->table, array('state_id' => $this->State->getIdByName('ready to encode'), 'failed' => false, 'user_id' => null), array('id' => $id, 'type_id' => 2)))) {
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