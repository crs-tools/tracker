<div id="feed-stats-progress-bar" style="width: <?= round($progress, 2); ?>%"<?= (($progress < 20)? ' class="narrow"' : (($progress === 100.0)? ' class="complete"' : ''))?>>
	<span class="label">
		<span><?= (($progress === 100.0)? 'complete' : (floor($progress) . '%')); ?></span>
	</span>
</div>