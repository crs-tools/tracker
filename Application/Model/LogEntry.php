<?php
	
	requires(
		'String'
	);
	
	class LogEntry extends Model {
		
        const TABLE = 'tbl_log';

        public $belongsTo = array(
            'Handle' => array(
                'foreign_key' => 'handle_id',
                'select' => 'name AS handle_name'
            ),
            'Ticket' => array(
                'foreign_key' => 'ticket_id'
            ),
            'User' => array(
                'foreign_key' => 'handle_id',
                'select' => 'name AS user_name'
            )
        );
		
		protected static $_messages = [
			'RPC.assignNextUnassignedForState' => '{to_State} started.',
			'RPC.ping' => 'Issued command.',
			'RPC.setTicketFailed' => '{from_State} failed.',
			'RPC.setTicketDone' => '{from_State} finished.',
			'RPC.setTicketProperties' => 'Properties changed.',
			
			'Action.cut' => 'Recording cut.',
			'Action.cut.failed' => 'Cutting failed.',
			
			'Action.check' => 'Encoding checked.',
			'Action.check.failed' => 'Encoding check failed.',
			
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
		
		public function getMessage() {
			$toState = ($this['to_state'] !== null)? $this['to_state'] : 'unknown state';
			$fromState = ($this['from_state'] !== null)? $this['from_state'] : 'unknown state';
			
			if (!isset(self::$_messages[$this['event']])) {
				Log::info('Log message for event ' . $this['event'] . ' missing.');
				return false;
			}
			
			return str_replace(
				array('{to_state}', '{to_State}', '{from_state}', '{from_State}'),
				array($toState, mb_ucfirst($toState), $fromState, mb_ucfirst($fromState)),
				self::$_messages[$this['event']]
			);
		}
		
		public static function createForTicket(Ticket $ticket, array $entry) {
			return static::create(array_merge([
				'ticket_id' => $ticket['id'],
				'from_state' => $ticket['ticket_state'],
			], $entry));
		}

    }

?>