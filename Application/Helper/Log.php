<?php
	
	function logFormatEventMessage(Project $project, LogEntry $entry, View $view, $type = 'log') {
		$message = $entry->getEventMessage($type);
		
		if ($message === false) {
			return false;
		}
		
		return str_replace([
			'{user_name}',
			'{id}'
				
			/*,
			'{tickets}'*/
		], [
			$this->linkTo(
				'tickets', 'index', $project, ['?u=' . $entry['handle_id']],
				$entry['handle_name'],
				['data-handle' => $entry['handle_id']]
			),
			$this->linkTo(
				'tickets', 'view', ['id' => $entry['ticket_id']], $project,
				$entry['ticket_fahrplan_id'],
				['data-ticket-id' => $entry['ticket_fahrplan_id']]
			)
			/*,
			'<span data-tickets="' . Filter::specialChars(json_encode($ticketData)) . '">' . ((isset($entry['children']))? (count($entry['children']) + 1) . ' tickets'  : '1 ticket') . '</span>'*/
		], $message);
	}
	
?>