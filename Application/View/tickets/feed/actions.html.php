<ul id="feed-stats-actions">
	<?php // TODO: hide when all are 0 and progress is complete ?>
	<li>
		<strong><?= $stats['cutting']; ?></strong>
		<?= $this->linkTo('tickets', 'index', $project, array('?t=cutting'), 'recording task' . (($stats['cutting'] != 1)? 's' : '') . '  to cut'); ?>
	</li>
	<li>
		<strong><?= $stats['checking']; ?></strong>
		<?= $this->linkTo('tickets', 'index', $project, array('?t=releasing'), 'encoding task' . (($stats['checking'] != 1)? 's' : '') . ' to check'); ?>
	</li>
	<li>
		<strong><?= $stats['fixing']; ?></strong>
		<span><?= (($stats['fixing'] != 1)? 'tickets' : 'ticket'); ?> failed</span>
	</li>
</ul>