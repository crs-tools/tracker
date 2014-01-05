<?php if (empty($ticket)) {
	$this->title('Create new ticket | ');
} else {
	$this->title('Edit ticket ' . $ticket['title'] . ' | ');
}

/*if (!$referer = Request::get('ref') or !$this->isValidReferer($referer, true)) {
	$referer = false;
}*/ ?>


<div id="ticket-header">
	<?php if (!empty($ticket)): ?>
		<h2 class="ticket">
			<span class="fahrplan"><?= $this->h($ticket['fahrplan_id']); ?></span>
			<span class="title">Edit ticket <?php echo $this->linkTo('tickets', 'view', $ticket, $project, str_shorten($ticket['title'], 37)); ?></span>
		</h2>
	<?php else: ?>
		<h2 class="ticket"><span class="title">Create new ticket</span></h2>
	<?php endif; ?>

	<ul class="ticket-header-bar right horizontal">
		<li class="ticket-header-bar-background-left"></li>
			
		<?php if (!empty($ticket)): ?>
			<?php if (User::isAllowed('tickets', 'cut') and $ticket->isEligibleAction('cut')): ?>
				<li class="action cut"><?php echo $this->linkTo('tickets', 'cut', $ticket, $project, '<span>cut</span>', 'Cut lecture…'); ?></li>
			<?php endif;
			if (User::isAllowed('tickets', 'check') and $ticket->isEligibleAction('check')): ?>
				<li class="action check"><?php echo $this->linkTo('tickets', 'check', $ticket, $project, '<span>check</span>', 'Check ticket…'); ?></li>
			<?php endif;
			/*if (User::isAllowed('tickets', 'fix') and $this->State->isEligibleAction('fix', $ticket)): ?>
				<li class="action fix"><?php echo $this->linkTo('tickets', 'fix', $ticket + $project, '<span>fix</span>', 'Fix ticket…'); ?></li>
			<?php endif;
			if (User::isAllowed('tickets', 'handle') and $this->State->isEligibleAction('handle', $ticket)): ?>
				<li class="action handle"><?php echo $this->linkTo('tickets', 'handle', $ticket + $project, '<span>handle</span>', 'Handle ticket…'); ?></li>
			<?php endif;
			if (User::isAllowed('tickets', 'reset') and $this->State->isResetable($ticket)): ?>
				<li class="action reset"><?php echo $this->linkTo('tickets', 'reset', $ticket + $project, '<span>reset</span>', 'Reset encoding task'); ?></li>
			<?php endif;*/ ?>
			
			<li class="action current edit"><?php echo $this->linkTo('tickets', 'edit', $ticket, $project, '<span>edit</span>', 'Edit ticket…'); ?></li>
			
			<?php if (User::isAllowed('tickets', 'delete')): ?>
				<li class="action delete"><?php echo $this->linkTo('tickets', 'delete', $ticket, $project, '<span>delete</span>', 'Delete ticket', array('class' => 'confirm-ticket-delete')); ?></li>
			<?php endif; ?>
		<?php else: ?>
			<li class="action current create"><?php echo $this->linkTo('tickets', 'create', $project, '<span>create</span>', 'Create new ticket…'); ?></li>
		
			<?php if (User::isAllowed('import', 'index')): ?>
				<li class="action import"><?php echo $this->linkTo('import', 'index', $project, '<span>import</span>'); ?></li>
			<?php endif; ?>
		
			<?php if (User::isAllowed('export', 'index')): ?>
				<li class="action export"><?php echo $this->linkTo('export', 'index', $project, '<span>export</span>'); ?></li>
			<?php endif; ?>
		<?php endif; ?>
		<li class="ticket-header-bar-background-right"></li>
	</ul>
</div>

<?php /*if (empty($ticket)) {
	echo $f = $this->form('tickets', 'create', $project, array('id' => 'ticket-edit'));
} else {
	echo $f = $this->form('tickets', 'edit', $ticket + $project + (($referer)? array('?ref=' . $referer) : array()), array('id' => 'ticket-edit'));
} */ ?>
<?= $f = $form(array('id' => 'ticket-edit')); ?>
	<fieldset>
		<ul>
			<li><?php echo $f->input('title', 'Title', $ticket['title'], array('class' => 'wide')); ?></li>
			<?php if ($ticket['ticket_type'] == 'encoding'/*or (empty($ticket) and User::isAllowed('tickets', 'create_all'))*/): ?>
				<li><?php echo $f->select('encoding_profile_version_id', 'Encoding profile', $profiles->indexBy('id', 'encodingProfileTitle')->toArray(), $ticket['encoding_profile_version_id']); ?>
			<?php endif; ?>
			<li><?php echo $f->select('priority', 'Priority', array('0.5' => 'low', '0.75' => 'inferior', '1' => 'normal', '1.25' => 'superior', '1.5' => 'high'), (!empty($ticket))? $ticket['priority'] : '1'); ?>
			<li><?php echo $f->select('handle_id', 'Assignee', array('' => '–') + $users->toArray(), $ticket['handle_id']); ?></li>
			<li class="checkbox"><?php echo $f->checkbox('needs_attention', 'Ticket needs attention', $ticket['needs_attention']); ?></li>
			<?php $f->register('comment'); ?>
		</ul>
	</fieldset>
	<fieldset>
		<legend>State</legend>
		<ul>
			<li>
				<?php if (!empty($ticket)): ?>
			        <?php echo $ticket['ticket_state']; ?><span class="description-color">  ⟶ </span>
				<?php endif; ?>
				<label for="ticket-edit-state">State</label>
				<?php if (empty($ticket)): /* ?>
					<select name="ticket_state" id="ticket-edit-state">
						<?php foreach($types as $id => $name): ?>
							<?php if (!empty($states[$id])): ?>
								<optgroup label="<?php echo $name; ?>">
									<?php foreach ($states[$id] as $state) {
										echo $f->option($state['name'], $state['id'], Request::post('state') == $state['id'] or $ticket['state_id'] == $state['id']);
									} ?>
								</optgroup>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>
				<?php */ else: ?>
					<?php echo $f->select('ticket_state', null, $states->indexBy('ticket_state', 'ticket_state')->toArray(), $ticket['ticket_state'], array('id' => 'ticket-edit-state')) ?>
				<?php endif; ?>
			</li>
			<li class="checkbox"><?php echo $f->checkbox('failed', 'Current state failed', $ticket['failed']); ?></li>
		</ul>
	</fieldset>
	<?php if (!empty($tickets)): ?>
		<fieldset>
			<legend>Parent</legend>
			<ul>
				<li>
					<label for="ticket-edit-parent">Parent ticket</label>
					<select name="parent" id="ticket-edit-parent">
						<?php if ((empty($ticket) and User::isAllowed('tickets', 'create_all')) or !empty($ticket
							)): ?>
							<option></option>
						<?php endif;
						foreach ($tickets as $t) {
							// echo $f->option(($t['type_id'] == 3)? '–' : (($t['fahrplan_id'] === 0)? $t['id'] : $t['fahrplan_id']), $t['id'], Request::post('parent') == $t['parent_id'] or $ticket['parent_id'] == $t['id']); // TODO: better option selection
							echo $f->option((($t['ticket_type'] == 3)? '–' : (($t['fahrplan_id'] === 0)? $t['id'] : $t['fahrplan_id'])) . ' | ' . $t['title'], $t['id'], $ticket['parent_id'] == $t['id'], null, array('name' => 'parent'));
						} ?>
					</select>
				</li>
			</ul>
		</fieldset>
	<?php endif; ?>
	<fieldset class="foldable">
		<legend>Properties</legend>
		<?php echo $this->render('shared/form/properties.tpl', array(
			'f' => $f,
			'properties' => array(
				'for' => $ticket->Properties,
				'field' => 'properties',
				'description' => 'property',
				'key' => 'name',
				'value' => 'value'
			)
		)); ?>
	</fieldset>
	<fieldset>
		<ul>
			<li>
				<?php if (empty($ticket)) {
					echo $f->submit('Create ticket') . ' or ';
					echo $this->linkTo('tickets', 'index', $project, 'discard ticket', array('class' => 'reset'));
				} else {
					echo $f->submit('Save ticket') . ' or ';
					echo $this->linkTo('tickets', 'view', $ticket, $project, 'discard changes', array('class' => 'reset'));
				} ?>
			</li>
		</ul>
	</fieldset>
</form>