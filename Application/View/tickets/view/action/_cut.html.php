<?php if ($languages->getRows()): ?>
	<li>
		<?= $f->select('language', 'Language', array('' => '') + $languages->toArray()); ?>
		<span class="description">
			<?php if (isset($parentProperties['Fahrplan.Language'])): ?>
				Fahrplan language is set as <strong><?= (isset($languages[$parentProperties['Fahrplan.Language']]))?
					$languages[$parentProperties['Fahrplan.Language']] :
					$parentProperties['Fahrplan.Language']; ?></strong>.
			<?php else: ?>
				Fahrplan language not set.
			<?php endif; ?>
		</span>
	</li>
<?php endif; ?>

<li class="checkbox"><?= $f->checkbox('delay', 'There is a noticable audio delay.', !empty($properties['Record.AVDelay'])); ?></li>
<li>
	<?= $f->input('delay_by', 'Delay audio by', (!empty($properties['Record.AVDelay']))? delayToMilliseconds($properties['Record.AVDelay']) : ''); ?>
	<span class="description">Delay is specified in milliseconds and can be negative.</span>
</li>

<li class="checkbox"><?= $f->checkbox('expand', 'The content extends the given timeline.'); ?></li>
<li><?= $f->select('expand_left', 'Expand left by', actionExpandOptions()); ?></li>
<li><?= $f->select('expand_right', 'Expand right by', actionExpandOptions()); ?></li>

<li class="checkbox"><?= $f->checkbox('failed', 'I\'m unable to cut this lecture, because something is broken.', $ticket['failed']); ?></li>
<li><?= $f->textarea('comment', 'Comment', null, array('class' => 'wide hidden')); ?></li>

<li><?= $f->submit('I finished cutting'); // Closing <li> is in parent template ?>