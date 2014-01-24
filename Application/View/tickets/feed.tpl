<?php if (isset($progress)) {
	$progressBar = '<div id="feed-stats-progress-bar" style="width: ' . round($progress, 2) . '%"' . (($progress < 20)? ' class="narrow"' : (($progress === 100.0)? ' class="complete"' : '')) . '>';
	$progressBar .= '<span class="label"><span>' . (($progress === 100.0)? 'complete' : (floor($progress) . '%')) . '</span></span></div>';
}

$actions = '<ul id="feed-stats-actions"><li><strong>' . $stats['cutting'] . '</strong> ' . $this->linkTo('tickets', 'index', $project, array('?t=cutting'), 'recording task' . (($stats['cutting'] != 1)? 's' : '') . '  to cut') . '</li>';
$actions .= '<li><strong>' . $stats['checking'] . '</strong> '. $this->linkTo('tickets', 'index', $project, array('?t=releasing'), 'encoding task' . (($stats['checking'] != 1)? 's' : '') . ' to check') . '</li>';
$actions .= '<li><strong>' . $stats['fixing'] . '</strong><span> ' . (($stats['fixing'] != 1)? 'tickets' : 'ticket') . ' failed</span></li></ul>';

if (isset($json)):
	$this->layout(false);
	
	$json = [
		'entries' => [],
		'actions' => $actions,
		'progress' => $progressBar
	];
else:
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
<?php endif;

// FIXME
$logFormatEventMessage = new ReflectionFunction('logFormatEventMessage');
$logFormatEventMessage = Closure::bind($logFormatEventMessage->getClosure(), $this, 'View');

foreach ($log as $entry) {
	$created = new DateTime($entry['created']);
	
	$e = '<li class="event event-' . strtolower(str_replace('.', '-', $entry['event'])) . '" data-timestamp="' . $created->getTimestamp() . '" data-id="' . $entry['id'] . '">';
	
	$message = $logFormatEventMessage($project, $entry, $this, 'single');
	
	if ($message !== false) {
		$e .= $message;
		
		/*
		if (isset($entry['comment_comment'])) {
			$e .= str_replace('.', (($entry['event'] != 'Comment.Add')? ' and commented' : '') . ':', $message);
			$e .= '<p class="comment">' . Filter::specialChars($entry['comment_comment']) . '</p>';
		} else {
			$e .= $message;
		}
		*/
		
		if ($entry->includesMessage()) {
			$lines = array_filter(explode("\n", $this->h($entry['comment'])));
	
			$e .= '<code>' . nl2br(implode('<br />', array_slice($lines, 0, 3)));
	
			if (count($lines) > 3) {
				$e .= ' ' . $this->linkTo('tickets', 'log', $ticket + array('entry' => $entry['id']) + $project + array('.txt'), 'more');
			}
		
			$e .= '</code>';
		}
	} else {
		$e .= '<em>' . $entry['event'] . '</em>';
	}
	
	$e .= '<span class="date">' . timeAgo($created) . '</span></li>';
	
	if (isset($json)) {
		$json['entries'][] = $e;
	} else {
		echo $e;
	}
}


if (isset($json)) {
	echo json_encode($json);
} else {
	echo '</ul>';
} ?> 