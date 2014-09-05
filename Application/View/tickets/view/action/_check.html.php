<li class="checkbox"><?= $f->checkbox('reset', 'Source material is flawed or cutting failed. Reset all encoding tasks.'); ?></li>
<li class="checkbox"><?= $f->checkbox('failed', 'This encoding failed or something is wrong with the metadata.'); ?></li>
<li><?= $f->textarea('comment', 'Comment', null, ['class' => 'wide hidden']); ?></li>

<?php if (isset($recordingProperties['Record.Language'])): ?>
	<li><p>Recording language was set to <strong><?= $languages[$recordingProperties['Record.Language']] ?></strong>.</p></li>
<?php endif; ?>

<li><?= $f->submit('Everything\'s fine'); ?></li>