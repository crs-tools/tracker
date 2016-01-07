<?php if ($sameUser): ?>
	<li class="warning"></li>
	<li>
		<label></label>
		<p>
			<strong>You've already cut this ticket, you may want to <?= $this->linkTo('tickets', 'uncheck', $ticket, $project, 'leave this to another user', 'reset state and remove assignee'); ?>.</strong>
		</p>
	</li>
<?php endif; ?>

<li class="checkbox"><?= $f->checkbox('reset', 'Source material is flawed or cutting failed. Reset all encoding tasks.'); ?></li>
<li class="checkbox"><?= $f->checkbox('failed', 'This encoding failed or something is wrong with the metadata.'); ?></li>
<li><?= $f->textarea('comment', 'Comment', null, ['class' => 'wide hidden']); ?></li>
<?php if (isset($recordingProperties['Record.Language'])): ?>
	<li><p>Recording language was set to
		<strong><?= (isset($languages[$recordingProperties['Record.Language']['value']]))?
			$languages[$recordingProperties['Record.Language']['value']] : $recordingProperties['Record.Language']['value']; ?>
		</strong>.
	</p></li>
<?php endif; ?>

<?php if (isset($project->Properties['Publishing.Base.Url']) and isset($ticket->EncodingProfile->Properties['EncodingProfile.Extension'])): ?>
	<li><p>The file maybe available <?= $this->a(
		$project->Properties['Publishing.Base.Url']['value'] .
			$ticket['fahrplan_id'] .
			'-' . $ticket->EncodingProfile['slug'] .
			'.' . $ticket->EncodingProfile->Properties['EncodingProfile.Extension']['value'],
		'as download'
	); ?>.</p></li>
<?php endif; ?>

<li><?= $f->submit('Everything\'s fine'); // Closing <li> is in parent template ?>