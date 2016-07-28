<?php
	// FIXME
	$logFormatEventMessage = new ReflectionFunction('logFormatEventMessage');
	$logFormatEventMessage = Closure::bind($logFormatEventMessage->getClosure(), $this, 'View');
	
	$message = $logFormatEventMessage($project, $entry, $this, 'single');
	
	$created = new DateTime($entry['created']);
?>

<li class="event event-<?= strtolower(str_replace('.', '-', $entry['event'])); ?>" data-timestamp="<?= $created->getTimestamp(); ?>" data-id="<?= $entry['id']; ?>">
	<?php if ($message !== false): ?>
		<?= $message; ?>
		
		<?php /* if (isset($entry['comment_comment'])) {
			$e .= str_replace('.', (($entry['event'] != 'Comment.Add')? ' and commented' : '') . ':', $message);
			$e .= '<p class="comment">' . Filter::specialChars($entry['comment_comment']) . '</p>';
		} else {
			$e .= $message;
		} */ ?>
		
		<?php /* if ($entry->includesMessage()) {
			$lines = array_filter(explode("\n", h($entry['comment'])));

			echo '<code>' . nl2br(implode('<br />', array_slice($lines, 0, 3)));

			if (count($lines) > 3) {
				#TODO FIXME $ticket is null, using "+" fails:
				echo ' ' . $this->linkTo('tickets', 'log', $ticket + array('entry' => $entry['id']) + $project + array('.txt'), 'more');
			}

			echo '</code>';
		} */ ?>
		
	<?php else: ?>
		<em><?= $entry['event']; ?></em>
	<?php endif; ?>
	
	<span class="date"><?= timeAgo($created); ?></span>
</li>
