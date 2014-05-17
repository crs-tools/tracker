<?php $this->title('Feed | '); ?>

<div id="feed-stats">
	<!-- TODO: show notice, if somebody assigned a ticket to the user -->
	
	<?php if (isset($progress)): ?>
		<div id="feed-stats-progress">
			<?= $this->render('tickets/feed/progress'); ?>
			<span class="description">Overall progress</span>
		</div>
	<?php endif; ?>
	
	<?= $this->render('tickets/feed/actions'); ?>
</div>

<ul id="feed">
	<?php foreach ($log as $entry) {
		echo $this->render('tickets/feed/entry', ['entry' => $entry]);
	} ?>
</ul>