<?php
	
	$message = $entry->getEventMessage(LogEntry::MESSAGE_TYPE_SINGLE);
	
	if ($message !== false) {
		$message = str_replace([
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
				[
					'data-ticket-id' => $entry['ticket_fahrplan_id'],
					'aria-label' => $entry->Ticket->getTitle(
						$entry['parent_title'],
						$entry['encoding_profile_name']
					),
					'data-tooltip' => true,
				]
			)
			/*,
			'<span data-tickets="' . Filter::specialChars(json_encode($ticketData)) . '">' . ((isset($entry['children']))? (count($entry['children']) + 1) . ' tickets'  : '1 ticket') . '</span>'*/
		], $message);
	}
	
	$created = new DateTime($entry['created']);
?>

<li
	class="event event-<?= strtolower(str_replace('.', '-', $entry['event'])); ?><?= ($entry['needs_attention'])? ' needs_attention' : '' ?>"
	data-timestamp="<?= $created->getTimestamp(); ?>"
	data-id="<?= $entry['id']; ?>"
>
	<?php if ($message !== false): ?>
		<?= $message; ?>
		
		<?php /* if (isset($entry['comment_comment'])) {
			$e .= str_replace('.', (($entry['event'] != 'Comment.Add')? ' and commented' : '') . ':', $message);
			$e .= '<p class="comment">' . Filter::specialChars($entry['comment_comment']) . '</p>';
		} else {
			$e .= $message;
		} */ ?>
		
		<?php if ($entry->includesMessage()) {
			$lines = array_filter(explode("\n", h($entry['comment'])));
			
			echo '<code>' . nl2br(implode('<br />', array_slice($lines, 0, 3)));
			
			if (count($lines) > 3) {
				echo ' ' . $this->linkTo(
					'tickets', 'log',
					$entry->Ticket->toArray() +
						$project->toArray() +
						['entry' => $entry['id'], '.txt'],
					'more'
				);
			}
			
			echo '</code>';
		} ?>
	<?php else: ?>
		<em><?= $entry['event']; ?></em>
	<?php endif; ?>
	
	<span class="date"><?= timeAgo($created); ?></span>
</li>
