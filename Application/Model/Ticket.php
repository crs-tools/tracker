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
			'Source' => [
				'class_name' => 'Ticket',
				'foreign_key' => ['parent_id'],
				'where' => ['ticket_type' => ['recording', 'ingest']]
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
		
		public static $priorities = [
			'0.5' => 'low',
			'0.75' => 'inferior',
			'1' => 'normal',
			'1.25' => 'superior',
			'1.5' => 'high'
		];
		
		private static $_fahrplanPropertyMap = [
			'duration' => 'Fahrplan.Duration',
			'subtitle' => 'Fahrplan.Subtitle',
			'slug' => 'Fahrplan.Slug',
			'room' => 'Fahrplan.Room',
			'type' => 'Fahrplan.Type',
			'track' => 'Fahrplan.Track',
			'language' => 'Fahrplan.Language',
			'abstract' => 'Fahrplan.Abstract'
		];
		
		/*
			Scopes
		*/
		public static function filter_recording(Model_Resource $resource) {
			self::filter_state(
				$resource,
				'recording',
				['locked', 'scheduled', 'recording', 'recorded', 'preparing']
			);
		}
		
		public static function filter_cutting(Model_Resource $resource) {
			self::filter_state(
				$resource,
				'recording',
				['prepared', 'cutting', 'cut']
			);
		}
		
		public static function filter_encoding(Model_Resource $resource) {
			self::filter_state(
				$resource,
				'encoding',
				['ready to encode', 'encoding', 'encoded', 'postencoding']
			);
		}
		
		public static function filter_releasing(Model_Resource $resource) {
			self::filter_state(
				$resource,
				'encoding',
				[
					'postencoded',
					'checking',
					'checked',
					'postprocessing',
					'postprocessed',
					'ready to release',
					'releasing'
				]
			);
		}
		
		public static function filter_released(Model_Resource $resource) {
			self::filter_state($resource, 'encoding', ['released']);
		}
		
		public static function filter_state(Model_Resource $resource, $type, array $states) {
			$marks = substr(str_repeat('?,', count($states)), 0, -1);
			$resource->where(
				'(' . self::TABLE . '.ticket_type = ? AND ' .
				self::TABLE . '.ticket_state IN (' . $marks . ')) OR ' .
				'(child.ticket_type = ? AND child.ticket_state IN (' . $marks . '))',
				array_merge(
					[$type],
					$states,
					[$type],
					$states
				)
			);
		}
		
		public static function filter_handle(Model_Resource $resource, $handle) {			
			$resource->where(
				self::TABLE . '.handle_id = ? OR child.handle_id = ?',
				[$handle, $handle]
			);
		}
		
		public static function order_list(Model_Resource $resource) {
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
		
		public static function order_priority(Model_Resource $resource) {
			$resource->orderBy(
				'CASE WHEN child.id IS NULL
					THEN ticket_priority(id)
					ELSE ticket_priority(child.id)
				 END DESC,
				 COALESCE(parent_id, id), COALESCE(child.id, id),
				 parent_id DESC
			');
		}
		
		/*
		 * This join results in a query like this:
		 *
		 *	 SELECT
		 *		parent.id AS id,
		 *		parent.parent_id AS parent_id,
		 *		parent.ticket_type AS ticket_type,
		 *		parent.title AS title,
		 *
		 *		child.id AS child_id,
		 *		child.parent_id AS child_parent_id,
		 *		child.ticket_type AS child_ticket_type,
		 *		child.title AS child_title
		 *
		 *	FROM tbl_ticket parent
		 *	LEFT JOIN tbl_ticket child ON child.parent_id = parent.id
		 *
		 *	WHERE parent.fahrplan_id = 38
		 *	AND parent.project_id = 8;
		 *
		 * which gives results like these:
		 *
		 *  id   | parent_id | ticket_type | title                         | child_id | child_parent_id | child_ticket_type | child_title               
		 * ------+-----------+-------------+-------------------------------+----------+-----------------+-------------------+-------------------------------
		 *  1225 |           | meta        | Ticket                        |     1267 |            1225 | encoding          | Ticket (H.264-MP4 from DV HQ)
		 *  1225 |           | meta        | Ticket                        |     1268 |            1225 | encoding          | Ticket (WebM from DV)
		 *  1225 |           | meta        | Ticket                        |     1364 |            1225 | recording         | Ticket (Recording)
		 *  1364 |      1225 | recording   | Ticket (Recording)            |          |                 |                   | 
		 *  1267 |      1225 | encoding    | Ticket (H.264-MP4 from DV HQ) |          |                 |                   | 
		 *  1268 |      1225 | encoding    | Ticket (WebM from DV)         |          |                 |                   | 
		 * 
		 */
		public static function with_child(Model_Resource $resource) {
			$resource->leftJoin(
				[self::TABLE, 'child'],
				[self::TABLE . '.id = parent_id']
			);
		}
		
		public static function with_default_properties(Model_Resource $resource) {
			self::with_properties($resource, [
				'Fahrplan.Start' => 'fahrplan_start',
				'Fahrplan.Date' => 'fahrplan_date',
				'Fahrplan.Day' => 'fahrplan_day',
				'Fahrplan.Room' => 'fahrplan_room'
			]);
		}
		
		
		public function with_encoding_profile_name(Model_Resource $resource) {
			$resource->leftJoin(
				[EncodingProfileVersion::TABLE, 'encoding_profile_version'],
				[self::TABLE . '.encoding_profile_version_id = id']
			);
			$resource->leftJoin(
				[EncodingProfile::TABLE],
				['encoding_profile_version.encoding_profile_id = id'],
				[],
				'name AS encoding_profile_name'
			);
		}
		
		public static function with_progress(Model_Resource $resource) {
			$resource->andSelect(
				self::TABLE . '.*, progress'
			);
		}
		
		public static function with_properties(Model_Resource $resource, array $properties) {
			foreach ($properties as $property => $as) {
				$resource->leftJoin(
					[TicketProperties::TABLE, 'property_' . $as],
					'((ticket_id = ' .
						self::TABLE .
						'.id AND ' . 
						self::TABLE .
						'.parent_id IS NULL) OR (ticket_id = ' .
						self::TABLE .
						'.parent_id AND ' .
						self::TABLE .
						'.parent_id IS NOT NULL)) AND name = ?',
					[$property],
					'value AS ' . $as
				);
			}
		}
		
		public static function with_recording(Model_Resource $resource) {
			$resource->leftJoin(
				[self::TABLE, 'recording'],
				['(.ticket_type = ? AND id IS NULL) OR (.parent_id = parent_id AND ticket_type = ?) OR (.id = parent_id AND ticket_type = ?)'],
				['recording', 'recording', 'recording']
			);
		}
		
		public static function without_locked(Model_Resource $resource) {
			$resource->where(
				'ticket_state != ? AND (recording.id IS NULL or recording.ticket_state != ?)',
				['locked', 'locked']
			);
		}
		
		/*
			Import
		*/
		public static function fromFahrplanEvent(SimpleXMLElement $event, DateTime $date = null) {
			$attributes = $event->attributes(); // SimpleXMLElement
			$ticket = [
				'Properties' => []
			];
			
			if (empty($attributes['id'])) {
				throw new TicketFahrplanException('fahrplan id for event is missing or empty');
			}
			
			$ticket['fahrplan_id'] = (int) $attributes['id'];
			
			if (isset($attributes['guid'])) {
				$ticket['Properties']['Fahrplan.GUID'] = [
					'name' => 'Fahrplan.GUID',
					'value' => (string) $attributes['guid']
				];
			}
			
			if (!isset($event->title)) {
				throw new TicketFahrplanException('event title is missing');
			}
			
			$ticket['title'] = (string) $event->title;
			
			if (empty($event->date)) {
				throw new TicketFahrplanException('event date is missing or empty');
			}
			
			// TODO: move to $ticket['start_date']
			$ticket['Properties']['Fahrplan.DateTime'] = [
				'name' => 'Fahrplan.DateTime',
				'value' => (new DateTime((string) $event->date))->format(DateTime::ISO8601)
			];
			
			$day = $event->xpath('ancestor::day/@index');
			
			if (!empty($day)) {
				$ticket['Properties']['Fahrplan.Day'] = [
					'name' => 'Fahrplan.Day',
					'value' => (string) current($day)
				];
			}
			
			foreach (self::$_fahrplanPropertyMap as $key => $property) {
				if (isset($event->{$key})) {
					$ticket['Properties'][$property] = [
						'name' => $property,
						'value' => (string) $event->{$key}
					];
				}
			}
			
			if (isset($event->recording)) {
				if (isset($event->recording->license)) {
					$ticket['Properties']['Fahrplan.Recording.License'] = [
						'name' => 'Fahrplan.Recording.License',
						'value' => (string) $event->recording->license
					];
				}
				
				if (isset($event->recording->optout)) {
					$ticket['Properties']['Fahrplan.Recording.Optout'] = [
						'name' => 'Fahrplan.Recording.Optout',
						'value' => ((string) $event->recording->optout == 'true')?
							'1' : '0'
					];
				}
			}
			
			// Remove empty properties
			$ticket['Properties'] = array_filter(
				$ticket['Properties'],
				function($property) {
					return $property['value'] !== '';
				}
			);
			
			$persons = $event->xpath('persons/person');
			
			if (!empty($persons)) {
				$ticket['Properties']['Fahrplan.Persons'] = [
					'name' => 'Fahrplan.Persons',
					'value' => implode(',', $persons)
				];
			}
			
			return new static($ticket);
		}
		
		public function diffWithProperties(Ticket $ticket) {
			$changes = array_diff_assoc($ticket->_entry, $this->_entry);
			
			if (isset($ticket->_entry['Properties'])) {
				$changes['Properties'] = [];
				$properties = $ticket->_entry['Properties'];
			
				foreach ($this->Properties->indexBy('name') as $name => $property) {
					if (!isset($properties[$name])) {
						$changes['Properties'][] = [
							'name' => $name,
							'_previous' => $property['value'],
							'_destroy' => true
						];
						continue;
					}
					
					if ($properties[$name]['value'] === '') {
						continue;
					}
					
					// $property['value'] = str_replace("\r", '', $property['value']);
					
					if ($property['value'] !== $properties[$name]['value']) {
						$properties[$name]['_previous'] = $property['value'];
						$changes['Properties'][] = $properties[$name];
					}
					
					unset($properties[$name]);
				}
				
				$changes['Properties'] = array_merge(
					$changes['Properties'],
					array_values($properties)
				);
				
				uasort($changes['Properties'], function($a, $b) {
					return strcmp($a['name'], $b['name']);
				});
				
				if (empty($changes['Properties'])) {
					unset($changes['Properties']);
				}
			}
			
			if (empty($changes)) {
				return null;
			}
			
			return new static(
				array_merge($changes, $this->getPrimaryKeyFields())
			);
		}
		
		/*
			Database functions
		*/
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
		
		/*
			Statistics
		*/
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
				->select('SUM(progress) / COUNT(id) AS progress')
				->where([
					'project_id' => $project,
					'ticket_type' => 'meta',
					'ticket_state' => 'staged'
				])
				->fetchRow()['progress'];
		}
		
		public static function getRecordingDurationByProject($project) {
			return (int) Ticket::findAll()
		   		->select(
					'EXTRACT(epoch FROM SUM(' . TicketProperties::TABLE .
						'.value::INTERVAL)) AS duration'
				)
				->join(
		   			TicketProperties::TABLE,
		   			[
		   				Ticket::TABLE . '.id = ticket_id',
		   				'name' => 'Fahrplan.Duration'
		   			]
		   		)
		   		->where([
					'project_id' => $project,
					'ticket_type' => 'meta',
					'ticket_state' => 'staged',
				])
				->fetchRow()['duration'];
		}
		
		public function allProperties() {
			// TODO: implement
		}
		
		/*
			Actions
		*/
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
			
			if ($this['ticket_state'] === $state) {
				return true;
			}
			
			return $this['ticket_state_next'] === $state;
		}
		
		public function addComment($comment, $handle = null) {
			return Comment::create([
				'ticket_id' => $this['id'],
				'handle_id' => ($handle === null)? User::getCurrent()['id'] : $handle,
				'comment' => $comment
			]);
		}
		
		public function addLogEntry(array $entry, $handle = null) {
			return LogEntry::create(array_merge([
				'ticket_id' => $this['id'],
				'from_state' => $this['ticket_state'],
				'handle_id' => ($handle === null)? User::getCurrent()['id'] : $handle,
			], $entry));
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
		
		public function resetSource(Comment $comment = null) {
			if ($this['ticket_type'] === 'meta') {
				$parent = $this;
			} else {
				$parent = $this->Parent;
			}
			
			$source = $parent->Source;
			$oldState = $source['state'];
			$toState = null;
			
			if (
				$source['ticket_type'] === 'recording' and
				$this->Project->hasState('recording', 'cutting')
			) {
				$source['ticket_state'] = $toState = 'cutting';
			}
			
			// TODO: cutting/encoding failed otherwise?
			
			if (!$source->save(['failed' => true])) {
				return false;
			}
			
			$source->addLogEntry([
				'comment_id' => (isset($comment))? $comment['id'] : null,
				'event' => 'Source.failed',
				'from_state' => $oldState,
				'to_state' => $toState
			]);
			
			$encodingTickets = $parent
				->Children
				->where(['ticket_type' => 'encoding']);
			
			$encodingTickets->update([
				'ticket_state' => $this->Project->queryFirstState('encoding'), 
				'failed' => false,
				'handle_id' => null
			]);
			
			foreach ($encodingTickets as $ticket) {
				// TODO: handle from/to state via database trigger?
				$ticket->addLogEntry([
					'event' => 'Encoding.Source.failed' 
				]);
			}
			
			return true;
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
	}
	
	class TicketFahrplanException extends UnexpectedValueException {}
	
?>
