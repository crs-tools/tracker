<?php
	
	requires(
		'String'
	);
	
	class LogEntry extends Model {
		
		const TABLE = 'tbl_log';
		
		const MESSAGE_TYPE_LOG = 'log';
		const MESSAGE_TYPE_SINGLE = 'single';
		const MESSAGE_TYPE_MULTIPLE = 'multiple';

		public $belongsTo = [
			'Handle' => [
				'foreign_key' => ['handle_id'],
				'select' => 'name AS handle_name'
			],
			'Ticket' => [
				'foreign_key' => ['ticket_id'],
				'select' => 'title AS ticket_title, fahrplan_id AS ticket_fahrplan_id, parent_id, ticket_type, encoding_profile_version_id',
				'join_assoications' => ['Parent' => ['join' => true]]
			],
			'User' => [
				'foreign_key' => ['handle_id'],
				'select' => 'name AS user_name'
			]
		];
		
		protected static $_messages = [
			'RPC.assignNextUnassignedForState' => [
				'log' => '{to_State} started.',
				'single' => '{user_name} started {to_state} ticket {id}.',
				'multiple' => '{user_name} started {to_state} {tickets}.',
				'message' => false
			],
			'RPC.addLog' => [
				'log' => '',
				'single' => '',
				'multiple' => false,
				'message' => true
			],
			'RPC.ping' => [
				'log' => 'Issued command.',
				'single' => 'Issued command to {user_name} regarding ticket {id}:',
				'multiple' => false,
				'message' => true
			],
			'RPC.setTicketNextState' => [
				'log' => 'Set state to {to_state}.',
				'single' => '{user_name} set state to {to_state} for {id}.',
				'multiple' => '{user_name} set state to {to_state} for {tickets}.',
				'message' => false
			],
			'RPC.setTicketFailed' => [
				'log' => '{from_State} failed.',
				'single' => '{user_name} failed {from_state} ticket {id}.',
				'multiple' => '{user_name} failed {from_state} {tickets}.',
				'message' => false
			],
			'RPC.setTicketDone' => [
				'log' => '{from_State} finished.',
				'single' => '{user_name} finished {from_state} ticket {id}.',
				'multiple' => '{user_name} finished {from_state} {tickets}.',
				'message' => false
			],
			'RPC.setTicketProperties' => [
				'log' => 'Properties changed.',
				'single' => '{user_name} altered properties of ticket {id}.',
				'multiple' => '{user_name} altered properties of {tickets}.',
				'message' => false
			],
			
			'Comment.Add' => [
				'single' => '{user_name} commented on {id}.',
				'multiple' => false,
				'message' => false
			],
			
			'Action.cut' => [
				'log' => 'Recording cut.',
				'single' => '{user_name} cut recording ticket {id}.',
				'multiple' => '{user_name} cut {tickets}.',
				'message' => false
			],
			'Action.cut.start' => [
				'log' => 'Started cutting.',
				'single' => '{user_name} started cutting recording ticket {id}.',
				'multiple' => '{user_name} started cutting {tickets}.',
				'message' => false
			],
			'Action.cut.failed' => [
				'log' => 'Cutting failed.',
				'single' => '{user_name} failed to cut {id}.',
				'multiple' => '{user_name} failed to cut {tickets}.',
				'message' => false
			],
			'Action.cut.abort' => [
				'log' => 'Cutting aborted.',
				'single' => '{user_name} aborted cutting {id}.',
				'multiple' => '{user_name} aborted cutting {tickets}.',
				'message' => false
			],
			'Action.check' => [
				'log' => 'Encoding checked.',
				'single' => '{user_name} checked encoding ticket {id}.',
				'multiple' => '{user_name} checked {tickets}.',
				'message' => false
			],
			'Action.check.start' => [
				'log' => 'Started checking.',
				'single' => '{user_name} started checking encoding ticket {id}.',
				'multiple' => '{user_name} started checking {tickets}.',
				'message' => false
			],
			'Action.check.failed' => [
				'log' => 'Encoding check failed.',
				'single' => '{user_name} failed to check encoding ticket {id}.',
				'multiple' => '{user_name} failed to check {tickets}.',
				'message' => false
			],
			'Action.check.abort' => [
				'log' => 'Checking aborted.',
				'single' => '{user_name} aborted checking {id}.',
				'multiple' => '{user_name} aborted checking {tickets}.',
				'message' => false
			],
			'Action.reset' => [
				'log' => 'Ticket reset.',
				'single' => '{user_name} reset {id} to state {to_state}',
				'multiple' => '{user_name} reset {tickets}',
				'message' => false
			],
			
			'Source.failed' => [
				'log' => 'Marked as failed while checking encoding task.',
				'single' => '{user_name} marked {id} as failed.',
				'multiple' => '{user_name} marked {tickets} as failed.',
				'message' => false
			],
			
			'Encoding.Source.failed' => [
				'log' => 'Source failed, encoding task was reset.',
				'single' => '{user_name} reset {id}, source failed.',
				'multiple' => '{user_name} reset {tickets}, source failed.',
				'message' => false
			]
			
			/*
			'RPC.Log' => [
				'single' => '',
				'multiple' => '',
				'message' => false
			],
			'Action.Fix' => [
				'single' => '{user_name} fixed ticket {id}.',
				'multiple' => '',
				'message' => false
			],
			'Encoding.Reset' => [
				'single' => '{user_name} reset encoding task {id}.',
				'multiple' => '',
				'message' => false
			],
			'Recording.Reset' => [
				'single' => '{user_name} reset recording task {id}.',
				'multiple' => '',
				'message' => false
			],
			'RPC.State.Next' => [
				'single' => '{user_name} set ticket {id} as {to_state}.',
				'multiple' => '{user_name} set {tickets} as {to_state}.',
				'message' => false
			],
			'Action.Cut.Expand' => [
				'single' => '{user_name} expanded ticket {id} recording time.',
				'multiple' => '',
				'message' => false
			],
			'Encoding.Parent.Reset' => [
				'single' => '{user_name} reset ticket {id} while recording task was beeing reset.',
				'multiple' => '{user_name} reset {tickets} while recording tasks were beeing reset.',
				'message' => false
			],
			'Created' => [
				'single' => '{user_name} created ticket {id}.',
				'multiple' => '{user_name} created {tickets}.',
				'message' => false
			]
			*/
			
			/*
			'RPC.Log' => '',
			'Comment.Add' => 'Comment added.',
			'Action.Fix' => 'Fixed.',
			'Encoding.Reset' => 'Encoding task has been reset.',
			'Recording.Reset' => 'Task has been reset.',
			'RPC.State.Next' => 'State changed to {to_state}.',
			'Action.Cut.Expand' => 'Expanded recording time.',
			'RPC.Ping.Command' => 'Issued command.',
			'Encoding.Parent.Reset' => 'Task has been reset while recording task was beeing reset.',
			'Created' => 'Ticket created.'
			*/
		];
		
		public function getEventMessage($type = self::MESSAGE_TYPE_LOG) {
			$toState = ($this['to_state'] !== null)? $this['to_state'] : 'unknown state';
			$fromState = ($this['from_state'] !== null)? $this['from_state'] : 'unknown state';
			
			if (!isset(self::$_messages[$this['event']][$type])) {
				Log::info('Log message for event ' . $this['event'] . ' (' . $type . ') missing.');
				return false;
			}
			
			return str_replace(
				['{to_state}', '{to_State}', '{from_state}', '{from_State}'],
				[$toState, mb_ucfirst($toState), $fromState, mb_ucfirst($fromState)],
				self::$_messages[$this['event']][$type]
			);
		}
		
		public function includesMessage() {
			return isset(self::$_messages[$this['event']]) and
				self::$_messages[$this['event']]['message'];
		}
		
		public function isSupportingMerge() {
			return isset(self::$_messages[$this['event']]) and
				self::$_messages[$this['event']]['multiple'] !== false;
		}
		
		/*
			Scopes
		*/
		public static function include_in_feed(Model_Resource $resource) {
			$resource->whereNot([
				'event' => 'RPC.addLog'
			]);
		}
		
		public static function with_title(Model_Resource $resource) {
			$resource->join(
				Ticket::TABLE,
				[Ticket::TABLE . '.parent_id = id'],
				[],
				'title AS parent_title, needs_attention',
				'LEFT'
			);
			
			$resource->join(
				EncodingProfileVersion::TABLE,
				[Ticket::TABLE . '.encoding_profile_version_id = id'],
				[],
				false,
				'LEFT'
			);
			
			$resource->join(
				EncodingProfile::TABLE,
				[EncodingProfileVersion::TABLE . '.encoding_profile_id = id'],
				[],
				'name as encoding_profile_name',
				'LEFT'
			);
		}
		
		/*
		if (!empty($log)) {
			foreach ($log as $entry) {
				if (!isset($entries[$entryIndex])) {
					$entryIndex++;
					$entries[$entryIndex] = $entry;
					continue;
				}
				
				if (
					$entries[$entryIndex]['event'] !== $entry['event']
					or $entries[$entryIndex]['from_state_id'] !== $entry['from_state_id']
					or $entries[$entryIndex]['to_state_id'] !== $entry['to_state_id']
					or $entries[$entryIndex]['event'] == 'RPC.Ping.Command'
					or $entries[$entryIndex]['event'] == 'Comment.Add'
				) {
					$entryIndex++;
					$entries[$entryIndex] = $entry;
					continue;
				}
			
				if (!isset($entries[$entryIndex]['children'])) {
					$entries[$entryIndex]['children'] = array();
				}
			
				$entries[$entryIndex]['children'][] = $entry;
			}
		}
		*/

	}

?>
