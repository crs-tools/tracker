<?php /*if ($this->respondTo('json')) {
	$this->layout(false);
	
	if (empty($tickets)) {
		echo '[]';
	}
}

if (!isset($referer) and (!$referer = Request::get('t') or !$this->isValidReferer($referer))) {
	$referer = 'index';
}*/

if (!empty($tickets)) {	
	/*if ($this->respondTo('json')) {
		$json = array();
	} else {*/
		echo '<ul class="tickets">';
	// }
	
	foreach ($tickets as $i => $ticket) {
		$t = '<li data-id="' . $ticket['id'] . '"' . ((!empty($ticket['parent_id']))? ' class="' . ((!empty($simulateTickets))? 'no-properties' : 'child') . '"' : '') . '>';
			$t .= '<a class="link" href="' . $this->Request->getRootURL() . Router::reverse('tickets', 'view', $ticket->toArray() + array('project_slug' => $project['slug']) + (($referer and $referer != 'index')? array('?ref=' . $referer) : array())) . '" title="' . (($ticket['fahrplan_id'] === 0)? $ticket['id'] : $ticket['fahrplan_id']) . ' – ' . $this->h($ticket['title']) . ((!empty($ticket['encoding_profile_name']))? ' (' . $ticket['encoding_profile_name'] . ')' : '') . (($ticket['failed'])? ' (' . $ticket['ticket_state'] . ' failed)' : (($ticket['needs_attention'])? ' (needs attention)' : '')) . '">';
				$t .= '<span class="vid' . (($ticket['needs_attention'] and (empty($ticket['parent_id']) or !empty($simulateTickets)))? ' needs_attention' : '') . '">';
				
				if (empty($ticket['parent_id']) or isset($simulateTickets)) {
					if ($ticket['fahrplan_id'] !== 0) {
						$t .=  $ticket['fahrplan_id'];
					} else {
						if ($ticket['type_id'] == 3 and empty($ticket['parent_id'])) {
							$t .=  '–';
						} else {
							$t .=  $ticket['id'];
						}
					}
				} else {
					$t .=  '&nbsp;';
				}
				
				$t .= '</span><span class="title">';
				
				if (empty($ticket['encoding_profile_name'])) {
					$t .= $this->h(str_shorten($ticket['title'], 40));
				} else {
					$t .= $ticket['encoding_profile_name'];
				}
				
				$t .= '</span><span class="state' . (($ticket['failed'])? ' failed' : '') . '">' . $ticket['ticket_state'] . (($ticket['failed'])? ' failed' : '');
				$t .= '</span><span class="day">';
				
				if (empty($ticket['parent_id'])) {
					$t .= (!empty($ticket['fahrplan_day']))? ('Day ' . $ticket['fahrplan_day']) : '-'; 
				}
				
				$t .= '</span><span class="start">';
				
				if (empty($ticket['parent_id'])) {
					$t .= $ticket['fahrplan_start'];
				}
				
				$t .= '</span><span class="room">';
				
				if (empty($ticket['parent_id'])) {
					$t .= $ticket['fahrplan_room'];
				}
				
				$t .= '</span><span class="view"></span>';
			$t .= '</a><span class="other">';
				
				if (!empty($ticket['handle_id'])) {
					$t .= '<span class="assignee">' . $this->linkTo('tickets', 'index', $project, array('?u=' . $ticket['handle_id']), $ticket['user_name'], array('data-user' => $ticket['user_id'])) . '</span>';
				}
				
				if (User::isAllowed('tickets', 'cut') and $ticket->isEligibleAction('cut')) {
					$t .= $this->linkTo('tickets', 'cut', $ticket, $project, /*(($referer)? array('?ref=' . $referer) : array()),*/ '<span>cut</span>', 'Cut recording "' . $ticket['title'] . '"', array('class' => 'action'));
				}
				
				if (User::isAllowed('tickets', 'check') and $ticket->isEligibleAction('check')) {
					$t .= $this->linkTo('tickets', 'check', $ticket, $project, /* (($referer)? array('?ref=' . $referer) : array()),*/ '<span>check</span>', 'Check "' . $ticket['title'] . '"', array('class' => 'action'));
				}
				
				/*if (User::isAllowed('tickets', 'fix') and $this->State->isEligibleAction('fix', $ticket)) {
					$t .= $this->linkTo('tickets', 'fix', $ticket + $project + (($referer)? array('?ref=' . $referer) : array()), '<span>fix</span>', 'Fix failed lecture "' . $ticket['title'] . '"', array('class' => 'action'));
				}
				
				if (User::isAllowed('tickets', 'handle') and $this->State->isEligibleAction('handle', $ticket)) {
					$t .= $this->linkTo('tickets', 'handle', $ticket + $project + (($referer)? array('?ref=' . $referer) : array()), '<span>handle</span>', 'Handle ticket "' . $ticket['title'] . '"', array('class' => 'action'));
				}*/
				
				if (User::isAllowed('tickets', 'edit')) {
					$t .= $this->linkTo('tickets', 'edit', $ticket, $project, (($referer)? array('?ref=' . $referer) : array()), '<span>edit</span>', 'Edit ticket "' . $ticket['title'] . '"', array('class' => 'edit'));
				}
			$t .= '</span>';
			
			if (empty($ticket['parent_id']) or isset($simulateTickets)) {
				$t .= $this->linkTo('tickets', 'view', $ticket, $project, (($referer and $referer != 'index')? array('?ref=' . $referer) : array()), '<span style="width: ' . round($ticket['progress']) . '%;">' . (($ticket['progress'] != '0')? '<span></span>' : '') . '</span>', round($ticket['progress']) . '% (' . (($ticket['fahrplan_id'] === 0)? $ticket['id'] : $ticket['fahrplan_id']) . ' – ' . $this->h($ticket['title']) . ')', array('class' => 'progress'));
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