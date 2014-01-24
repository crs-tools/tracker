<li class="event log right">
	<span class="title"><?php if (($message = $entry->getEventMessage()) !== false) {
		echo $message;
	} else {
		echo '<em>' . $entry['event'] . '</em>';
	} ?></span>
	<?php if (!empty($entry['comment'])): ?>
		<code>
			<?php $lines = array_filter(explode("\n", $this->h($entry['comment'])));
			echo nl2br(implode('<br />', array_slice($lines, 0, 3)));
			
			if (count($lines) > 3) {
				echo ' ' . $this->linkTo('tickets', 'log', $ticket, ['entry' => $entry['id']], $project, array('.txt'), 'more');
			} ?>
		</code>
	<?php endif; ?>

	<span class="description">by <?= $entry['handle_name']; ?></span>
	<span class="date">
		<?= timeAgo($entry['created']); ?>
	</span>
	
	<span class="spine"></span>
</li>