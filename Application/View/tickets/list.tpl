<?php if (isset($json)) {
	$this->layout(false);
	
	if (empty($tickets)) {
		echo '[]';
	}
}

if (!empty($tickets)) {	
	if (!isset($json)) {
		echo '<ul class="tickets">';
	}
	
	foreach ($tickets as $i => $ticket) {
		$t = '<li data-id="' . $ticket['id'] . '"' . ((!empty($ticket['parent_id']))? ' class="' . ((!empty($simulateTickets))? 'no-properties' : 'child') . '"' : '') . '>';
			$t .= '<a class="link" href="' . $this->Request->getRootURL() .
				Router::reverse('tickets', 'view', $ticket->toArray() + array('project_slug' => $project['slug'])) .
				'">';
			/*
				title="' . (($ticket['fahrplan_id'] === 0)? $ticket['id'] : $ticket['fahrplan_id']) .
				' – ' . h($ticket['title']) . ((!empty($ticket['encoding_profile_name']))? ' (' . $ticket['encoding_profile_name'] . ')' : '')
				. (($ticket['failed'])? ' (' . $ticket['ticket_state'] . ' failed)' : (($ticket['needs_attention'])? ' (needs attention)' : '')) . '"
			*/
				
				$t .= '<span class="vid' . (($ticket['needs_attention'] and (empty($ticket['parent_id']) or !empty($simulateTickets)))? ' needs_attention' : '') . '">';
				
				if (empty($ticket['parent_id']) or isset($simulateTickets)) {
					$t .= h($ticket['fahrplan_id']);
				} else {
					$t .=  '&nbsp;';
				}
				
				$t .= '</span><span class="title"';
				
				if (empty($ticket['parent_id']) and mb_strlen($ticket['title']) > 40) {
					$t .= ' aria-label="' . h($ticket['title']) . '" data-tooltip="true"';
				}
				
				$t .= '>';
				
				if (!empty($ticket['encoding_profile_name'])) {
					$t .= $ticket['encoding_profile_name'];
				} elseif ($ticket['ticket_type'] == 'recording') {
					$t .= 'Recording';
				} elseif ($ticket['ticket_type'] == 'ingest') {
					$t .= 'Ingest';
				} else {
					$t .= h(str_shorten($ticket['title'], 40));
				}
				
				$t .= '</span><span class="state' . (($ticket['failed'])? ' failed' : '') . '">' . $ticket['ticket_state'] . (($ticket['failed'])? ' failed' : '');
				$t .= '</span><span class="day">';
				
				if (empty($ticket['parent_id']) and isset($ticket['fahrplan_day'])) {
					$t .= (!empty($ticket['fahrplan_day']))? ('Day ' . h($ticket['fahrplan_day'])) : '-'; 
				}
				
				$t .= '</span><span class="start">';
				
				if (empty($ticket['parent_id']) and isset($ticket['fahrplan_start'])) {
					$t .= h($ticket['fahrplan_start']);
				}
				
				$t .= '</span><span class="room">';
				
				if (empty($ticket['parent_id']) and isset($ticket['fahrplan_room'])) {
					$t .= h($ticket['fahrplan_room']);
				}
				
				$t .= '</span><span class="view"></span>';
			$t .= '</a><span class="other">';
				
				if (!empty($ticket['handle_id']) and isset($ticket['handle_name'])) {
					$t .= '<span class="assignee">' . $this->linkTo(
						'tickets', 'index', $project, array('?u=' . $ticket['handle_id']),
						$ticket['handle_name'],
						null,
						[
							'data-handle' => $ticket['handle_id']/*,
							'aria-label' => "Last seen: \nIdentitfication: 12392",
							'data-tooltip' => true*/
						]
					) . '</span>';
				}
				
				if (User::isAllowed('tickets', 'cut') and $ticket->isEligibleAction('cut')) {
					$t .= $this->linkTo('tickets', 'cut', $ticket, $project, '<span>cut</span>', 'Cut recording "' . $ticket['title'] . '"', array('class' => 'action'));
				}
				
				if (User::isAllowed('tickets', 'check') and $ticket->isEligibleAction('check')) {
					$t .= $this->linkTo('tickets', 'check', $ticket, $project, '<span>check</span>', 'Check "' . $ticket['title'] . '"', array('class' => 'action'));
				}
				
				if (User::isAllowed('tickets', 'edit')) {
					$t .= $this->linkTo('tickets', 'edit', $ticket, $project, '<span>edit</span>', 'Edit ticket "' . $ticket['title'] . '"', array('class' => 'edit'));
				}
			$t .= '</span>';
			
			if (empty($ticket['parent_id']) or isset($simulateTickets)) {
				$t .= $this->linkTo(
					'tickets', 'view', $ticket, $project,
					(isset($ticket['progress']))? ('<span style="width: ' . round($ticket['progress']) . '%;">' . (($ticket['progress'] != '0')? '<span></span>' : '') . '</span>') : '',
					(isset($ticket['progress']))? (round($ticket['progress']) . '%') : '',
					['class' => 'progress']
				);
			}
		$t .= '</li>';
		
		if (isset($json)) {
			$json[] = $t;
		} else {
			echo $t;
		}
	}
	
	if (isset($json)) {
		echo json_encode($json);
	} else {
		echo '</ul>';
	}
} ?>