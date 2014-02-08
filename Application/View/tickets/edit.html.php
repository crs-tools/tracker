<?php if (empty($ticket)) {
	$this->title('Create new ticket | ');
} else {
	$this->title('Edit ticket ' . $ticket['title'] . ' | ');
} ?>

<?php if (!empty($ticket)):
	echo $this->render('tickets/view/header', [
		'titlePrefix' => 'Edit ',
		'showDetails' => false,
		'currentAction' => 'edit'
	]);
else: ?>
	<div id="ticket-header">
		<h2 class="ticket"><span class="title">Create new ticket</span></h2>

		<ul class="ticket-header-bar right horizontal">
			<li class="ticket-header-bar-background-left"></li>
			<li class="action create current"><?php echo $this->linkTo('tickets', 'create', $project, '<span>create</span>', 'Create new ticket…'); ?></li>
	
			<?php if (User::isAllowed('import', 'index')): ?>
				<li class="action import"><?php echo $this->linkTo('import', 'index', $project, '<span>import</span>'); ?></li>
			<?php endif; ?>
	
			<?php if (User::isAllowed('export', 'index')): ?>
				<li class="action export"><?php echo $this->linkTo('export', 'index', $project, '<span>export</span>'); ?></li>
			<?php endif; ?>
			<li class="ticket-header-bar-background-right"></li>
		</ul>
	</div>
<?php endif; ?>

<?= $f = $form(array('id' => 'ticket-edit')); ?>
	<fieldset>
		<ul>
			<li><?php echo $f->input('title', 'Title', $ticket['title'], array('class' => 'wide')); ?></li>
			
			<?php if (isset($profile)): ?>
				<li>
					<label>Encoding profile</label>
					<p><?= $profile['name'] . ' (r' . $profile['revision'] . ')' ?></p>
					<span class="description"><?= $profile['description']; ?></span>
				</li>
			<?php endif; ?>
			
			<li><?=$f->select(
				'priority', 'Priority',
				['0.5' => 'low', '0.75' => 'inferior', '1' => 'normal', '1.25' => 'superior', '1.5' => 'high'],
				(!empty($ticket))? $ticket['priority'] : '1'
			); ?></li>
			<li><?= $f->select(
				'handle_id', 'Assignee',
				['' => '–'] + $users->toArray(),
				$ticket['handle_id'],
				[
					'data-current-user-id' => User::getCurrent()['id'],
					'data-current-user-name' => User::getCurrent()['name']
				]
			); ?></li>
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
	<fieldset class="foldable">
		<legend>Properties</legend>
		<?php echo $this->render('shared/form/properties', array(
			'f' => $f,
			'properties' => array(
				'for' => (!empty($ticket))? $ticket->Properties->orderBy('name') : null,
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