<?php if (isset($progress)) {
	$progressBar = '<div id="feed-stats-progress-bar" style="width: ' . round($progress, 2) . '%"' . (($progress < 20)? ' class="narrow"' : (($progress === 100.0)? ' class="complete"' : '')) . '>';
	$progressBar .= '<span class="label"><span>' . (($progress === 100.0)? 'complete' : (floor($progress) . '%')) . '</span></span></div>';
}

$actions = '<ul id="feed-stats-actions"><li><strong>' . $stats['cutting'] . '</strong> ' . $this->linkTo('tickets', 'index', $project, array('?t=cutting'), 'recording task' . (($stats['cutting'] != 1)? 's' : '') . '  to cut') . '</li>';
$actions .= '<li><strong>' . $stats['checking'] . '</strong> '. $this->linkTo('tickets', 'index', $project, array('?t=releasing'), 'encoding task' . (($stats['checking'] != 1)? 's' : '') . ' to check') . '</li>';
$actions .= '<li><strong>' . $stats['fixing'] . '</strong><span> ' . (($stats['fixing'] != 1)? 'tickets' : 'ticket') . ' to fix</span></li></ul>';

/*if ($this->respondTo('json')):
	$this->layout(false);
else:*/
	$this->title('Feed | ');
?>

<div id="feed-stats">
	<!-- TODO: show notice, if somebody assigned a ticket to the user -->
	
	<?php if (!empty($progressBar)): ?>
		<div id="feed-stats-progress">
			<?php echo $progressBar; ?>
			<span class="description">Overall progress</span>
		</div>
	<?php endif; ?>
	
	<?php echo $actions; ?>
</div>

<ul id="feed">
<?php //endif;

/*if ($this->respondTo('json')) {
	$json = array(
		'entries' => array(),
		'actions' => $actions,
		'progress' => $progressBar
	);
}*/

if (!empty($log)) {
	foreach ($log as $entry) {
		$e = '<li class="event event-' . strtolower(str_replace('.', '-', $entry['event'])) . '" data-timestamp="' . Date::fromString($entry['created'])->getTimestamp() . '" data-id="' . $entry['id'] . '">';
	
		if (!isset($messages[$entry['event']]['feed_message']) or (isset($entry['children']) and !isset($messages[$entry['event']]['feed_message_multiple']))) {
			$e .= '<em>' . $entry['event'] . '</em>';
		} else {
			$toState = (isset($entry['to_state_name']))? $entry['to_state_name'] : 'unknown state';
			$fromState = (isset($entry['from_state_name']))? $entry['from_state_name'] : 'unknown state';
			
			$ticketData = array(array(
				'id' => $entry['ticket_id'],
				'fahrplanId' => $entry['ticket_fahrplan_id']
			));
				
			if (isset($entry['children'])) {
				foreach ($entry['children'] as $child) {
					$ticketData[] = array(
						'id' => $child['ticket_id'],
						'fahrplanId' => $child['ticket_fahrplan_id']
					);
				}
			}
			
			$message = str_replace(
				array('{to_state}', '{to_State}', '{from_state}', '{from_State}', '{user_name}', '{id}', '{tickets}'),
				array(
					$toState,
					mb_ucfirst($toState),
					$fromState,
					mb_ucfirst($fromState),
					$this->linkTo('tickets', 'index', $project + array('?u=' . $entry['user_id']), $entry['user_name'], array('data-user' => $entry['user_id'])),
					$this->linkTo('tickets', 'view', array('id' => $entry['ticket_id']) + $project, $entry['ticket_fahrplan_id'], array('data-ticket-id' => $entry['ticket_fahrplan_id'])),
					'<span data-tickets="' . Filter::specialChars(json_encode($ticketData)) . '">' . ((isset($entry['children']))? (count($entry['children']) + 1) . ' tickets'  : '1 ticket') . '</span>'
				),
				(isset($entry['children']))? $messages[$entry['event']]['feed_message_multiple'] : $messages[$entry['event']]['feed_message']
			);
			
			if (isset($entry['comment_comment'])) {
				$e .= str_replace('.', (($entry['event'] != 'Comment.Add')? ' and commented' : '') . ':', $message);
				$e .= '<p class="comment">' . Filter::specialChars($entry['comment_comment']) . '</p>';
			} else {
				$e .= $message;
			}
		
			if ($messages[$entry['event']]['feed_include_log']) {
				$lines = array_filter(explode("\n", Filter::specialChars($entry['comment'])));
		
				$e .= '<code>' . nl2br(implode('<br />', array_slice($lines, 0, 3)));
		
				if (count($lines) > 3) {
					$e .= ' ' . $this->linkTo('tickets', 'log', $ticket + array('entry' => $entry['id']) + $project + array('.txt'), 'more');
				}
			
				$e .= '</code>';
			}
		}
	
		$e .= '<span class="date">' . Date::fromString($entry['created'], null, 'H:i') . '</span></li>';
	
		if (isset($json)) {
			$json['entries'][] = $e;
		} else {
			echo $e;
		}
	}
}

if (isset($json)) {
	echo json_encode($json);
} else {
	echo '</ul>';
} ?> 