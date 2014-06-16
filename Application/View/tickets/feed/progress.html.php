<div id="feed-stats-progress-bar" data-progress="<?= round($progress); ?>" class="progress-width<?= (($progress < 20)? ' narrow' : (($progress === 100.0)? ' complete' : ''))?>">
	<span class="label">
		<span><?= (($progress === 100.0)? 'complete' : (floor($progress) . '%')); ?></span>
	</span>
</div>