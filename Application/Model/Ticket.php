<?php
	
	requires(
		'/Model/TicketProperties'
	);
	
	class Ticket extends Model {
		
		const TABLE = "tbl_ticket";
		
		const CLASS_RESOURCE = 'Ticket_Resource';
		
		public $hasMany = array(
			'Properties' => array(
				'class_name' => 'TicketProperties',
				'foreign_key' => 'ticket_id',
				'select' => 'name, value, SUBPATH(name, 0, 1) AS root'
			),
			'Comments' => array(
				'class_name' => 'Comment',
				'foreign_key' => 'ticket_id'
			),
			'Children' => array(
				'class_name' => 'Ticket',
				'foreign_key' => 'parent_id'
			)
		);
		
		public $belongsTo = array(
			'User' => array(
				'foreign_key' => 'handle_id',
				'select' => 'name as user_name'
			),
			'Worker' => array(
				'foreign_key' => 'handle_id'
			),
			'Parent' => array(
				'class_name' => 'Ticket',
				'foreign_key' => 'parent_id',
				'join' => false
			),
			'State' => array(
				'class_name' => 'ProjectTicketState',
				'primary_key' => array('project_id', 'ticket_type', 'ticket_state'),
				'foreign_key' => array('project_id', 'ticket_type', 'ticket_state')
			),
            'Project' => array(
                'foreign_key' => 'project_id'
            ),
            'EncodingProfileVersion' => array(
                'foreign_key' => 'encoding_profile_version_id'
            )
        );
		
		public $acceptNestedEntriesFor = array(
			'Properties' => true
		);
		
		public $scopes = array(
			'with_properties',
			'with_default_properties',
			'order_list'
			// TODO: with_progress
		);
		
		public function with_properties(Model_Resource $resource, array $arguments) {
			// $parent = $this->Parent;
			// OR ticket_id = tbl_ticket.parent_id
			
			foreach ($arguments as $property => $as) {
				$resource->join(
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
					array($property),
					'LEFT'
				);
			}
			
			return $resource;
		}
		
		public function with_default_properties(Model_Resource $resource, array $arguments) {
			return $this->with_properties($resource, [
				'Fahrplan.Start' => 'fahrplan_start',
				'Fahrplan.Date' => 'fahrplan_date',
				'Fahrplan.Day' => 'fahrplan_day',
				'Fahrplan.Room' => 'fahrplan_room'
			]);
		}
		
		public function order_list(Model_Resource $resource, array $arguments) {
			return $resource->orderBy(
				'fahrplan_date, fahrplan_start, fahrplan_room, fahrplan_id, parent_id DESC, title'
			);
			
			//to_timestamp((ticket_fahrplan_starttime(t.id))::double precision) AS time_start,
			//SELECT EXTRACT(EPOCH FROM (p.value::date + p2.value::time)::timestamp) INTO unixtime
		}
		
		// TODO: with_progress
		// $this->_fields[] = 'getTicketProgress(tbl_ticket.id) AS progress';
		
		public static function createMissingRecordingTickets($project) {
			Database::$Instance->query('SELECT create_missing_recording_tickets(?)', [$project]);
			return Database::$Instance->fetchRow()['create_missing_recording_tickets'];
		}
		
		public static function createMissingEncodingTickets($project, $encodingProfile = null) {
			Database::$Instance->query('SELECT create_missing_encoding_tickets(?, ?)', [$project, $encodingProfile]);
			return Database::$Instance->fetchRow()['create_missing_encoding_tickets'];
		}
		
		/*
		public $hasMany = array('Comment' => array('key' => 'ticket_id'));
		
		public $belongsTo = array(
			'Project' => array(),
			'Type' => array('join' => true, 'key' => 'type_id', 'fields' => 'name AS type_name'),
			'State' => array('join' => true, 'fields' => 'name AS state_name'),
			'EncodingProfile' => array('join' => true, 'fields' => 'name AS encoding_profile_name'),
			'User' => array('join' => true, 'fields' => 'name AS user_name')
		);
		
		public $validatePresenceOf = array('title' => true);
		public $validate = array('state' => array('message' => 'invalid state given'));
		
		public function getAsTable($conditions = null, array $params = array()) {
			$tickets = new Ticket_Query();
			$tickets->getProperties(array('Fahrplan.Start' => 'fahrplan_start', 'Fahrplan.Date' => 'fahrplan_date', 'Fahrplan.Day' => 'fahrplan_day', 'Fahrplan.Room' => 'fahrplan_room'));
			$tickets->getProgress();
			
			if ($conditions !== null) {
				$tickets->where($conditions, $params);
			}
			
			$tickets->orderBy('fahrplan_date, fahrplan_start, fahrplan_room DESC');
			
			return $tickets;
		}
		
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
		
		public function getChildren($id) {
			return $this->Ticket->findBySQL($this->getAsTable()->where(array('parent_id' => $id)), array(), array('User', 'State', 'EncodingProfile'));
		}
		
		public function getParent($id) {
			return $this->Ticket->findBySQL($this->getAsTable()->where(array('id' => $id)), array(), array('User', 'State'));
		}
		
		public function getExportable(array $properties = array()) {
			$tickets = new Ticket_Query();
			$tickets->getProperties(array('Record.Language' => 'record_language', 'Fahrplan.Slug' => 'fahrplan_slug'));
			
			if (!empty($properties)) {
				$tickets->getProperties($properties);
			}
			
			// TODO: should we use project_id here?
			$tickets->where('parent_id IS NULL AND NOT state_id = ? AND project_id = ?', array(1, $this->Project->id));
			$tickets->orderBy('fahrplan_id');
			$tickets->select('id, fahrplan_id, title');
			
			return $this->Ticket->findBySQL($tickets, array(), array());
		}
		
		public function getForExport($fetch = null, $conditions = null, array $params = array(), array $properties = array()) {
			$tickets = new Ticket_Query();
			$tickets->getProperties(array('Record.Language' => 'record_language', 'Fahrplan.Slug' => 'fahrplan_slug'));
			
			if (!empty($properties)) {
				$tickets->getProperties($properties);
			}
			
			if ($conditions !== null) {
				$tickets->where($conditions, $params);
			}
			
			return $this->Ticket->findBySQL($tickets, array(), $fetch);
		}
		
		public function getProgress($conditions = null, array $params = array()) {
			$query = Database_Query::selectFrom($this->table, 'SUM(getTicketProgress(tbl_ticket.id)) / COUNT(tbl_ticket.id) AS progress', $conditions, $params);
			$query->where('type_id = ? AND state_id != ?', array(1, $this->State->getIdByName('locked')));
			
			$this->Database->query($query);
			$result = $this->Database->fetchRow();
			
			return (float) $result['progress'];
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
		
		public static function sortByFahrplanStart(array $tickets) {
			usort($tickets, function($a, $b) {
				if (isset($a['fahrplan_date']) and isset($a['fahrplan_start'])) {
					$s = strtotime($a['fahrplan_date'] . ' ' . $a['fahrplan_start']);
					$t = strtotime($b['fahrplan_date'] . ' ' . $b['fahrplan_start']);
					
					if ($s < $t) {
						return -1;
					} elseif ($s > $t) {
						return 1;
					}
					
					if (isset($a['fahrplan_room'])) {
						$r = strcmp($a['fahrplan_room'], $b['fahrplan_room']);
						
						if ($r != 0) {
							return $r;
						}
					}
					
					if (isset($a['fahrplan_id'])) {
						if ($a['fahrplan_id'] < $b['fahrplan_id']) {
							return -1;
						} elseif ($a['fahrplan_id'] > $b['fahrplan_id']) {
							return 1;
						}
					}
					
					if ($a['parent_id'] === $b['parent_id']) {
						return 0;
					}

					if ($a['parent_id'] === null) {
						if ($a['id'] === $b['parent_id']) {
							return -1;
						}
					} else {
						if ($b['id'] === $a['parent_id']) {
							return 1;
						}
					}
				}
				
				return 0;
			});
			
			return $tickets;
		}
		
		public function expandRecordingTask($id, $byMinutes) {
			$by = 60 * $byMinutes;
			$properties = $this->Properties->findByObject($id, array('name' => 'Record.EndPadding'));
			
			if (!empty($properties['Record.EndPadding'])) {
				$by += (int) $properties['Record.EndPadding'];
			}
			
			$this->Properties->save(array('ticket_id' => $id, 'Record.EndPadding' => $by));
			
			if (!$this->Database->query(Database_Query::updateTable($this->table, array('state_id' => $this->State->getIdByName('recorded'), 'failed' => false, 'user_id' => null), array('id' => $id, 'type_id' => 1)))) {
				return false;
			}
			
			return true;
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
		
		protected function _validateState($value, $message) {
			// TODO: use Model State here
			return $this->Type->getRows(array('id' => $value)) >= 1;
		}
		*/
	}
	
?>