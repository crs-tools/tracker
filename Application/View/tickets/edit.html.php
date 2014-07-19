<?php if (!empty($ticket)) {
	$this->title('Edit ticket ' . $ticket['title'] . ' | ');
	$class = 'edit';
} elseif(!empty($tickets)) {
	$this->title('Edit '.$tickets->getRows().' tickets | ');
	$class = 'mass';
} else {
	$this->title('Create new ticket | ');
	$class = 'create';
} ?>

<?php if (!empty($ticket)):
	echo $this->render('tickets/view/_header', [
		'titlePrefix' => 'Edit ',
		'showDetails' => false,
		'currentAction' => 'edit'
	]);
else: ?>
	<div id="ticket-header">
		<h2 class="ticket"><span class="title">
			<?= empty($tickets) ? 'Create new ticket' : 'Edit '.$tickets->getRows().' tickets' ?>
		</span></h2>
		
		<ul class="ticket-header-bar right horizontal">
			<li class="ticket-header-bar-background-left"></li>
			<li class="action create <?= empty($tickets) ? 'current' : '' ?>"><?php echo $this->linkTo('tickets', 'create', $project, '<span>create</span>', 'Create new ticket…'); ?></li>
			
			<?php if (User::isAllowed('import', 'index')): ?>
				<li class="action import"><?php echo $this->linkTo('import', 'index', $project, '<span>import</span>'); ?></li>
			<?php endif; ?>
			<li class="ticket-header-bar-background-right"></li>
		</ul>
	</div>
<?php endif; ?>

<?= $f = $form(array('id' => 'ticket-edit', 'class' => $class)); ?>
	<fieldset>
		<ul>
			<?php if (empty($ticket) && empty($tickets)): ?>
				<li><?php echo $f->input('fahrplan_id', 'Fahrplan ID', null, ['class' => 'narrow']); ?></li>
			<?php endif ?>
			
			<li>
				<?php echo $f->input('title', 'Title', (!empty($ticket))? $ticket['title'] : '', array('class' => 'wide')); ?>
				<?php if(!empty($tickets)): ?>
					<span class="description">Not changed if empty</span>
				<?php endif ?>
			</li>
			
			<?php if (isset($profile)): ?>
				<li>
					<label>Encoding profile</label>
					<p>
						<?php if (User::isAllowed('encodingprofiles', 'view')) {
							echo $this->linkTo('encodingprofiles', 'view', $profile, $profile['name']);
						} else {
							echo $profile['name'];
						}
						
						echo ' (r' . $profile['revision'] . ')'; ?>
					</p>
					<span class="description"><?= $profile['description']; ?></span>
				</li>
			<?php endif; ?>
			
			<li><?=$f->select(
				'priority', 'Priority',
				$priorities,
				(!empty($ticket))? $ticket['priority'] : (empty($tickets) ? '1' : '_')
			); ?></li>
			<li><?= $f->select(
				'handle_id', 'Assignee',
				$users,
				(!empty($ticket))? $ticket['handle_id'] : (empty($tickets) ? '' : '_'),
				[
					'data-current-user-id' => User::getCurrent()['id'],
					'data-current-user-name' => User::getCurrent()['name']
				]
			); ?></li>
			<li class="checkbox"><?= $f->checkbox(
				'needs_attention',
				'Ticket needs attention',
				(!empty($ticket))? $ticket['needs_attention'] : false,
				(!empty($tickets))? ['disabled', 'data-multi' => true] : []
			); ?></li>
			<?php
			if (!empty($tickets)) {
				$f->register('change_needs_attention', null, Form::FIELD_BOOL);
			}
			
			$f->register('comment');
			?>
		</ul>
	</fieldset>
	<fieldset>
		<legend>State</legend>
		<ul>
			<li>
				<?php if (!empty($ticket)): ?>
					<?php echo $ticket['ticket_state']; ?><span class="description-color">  ⟶ </span>
				<?php endif ?>
				
				<label for="ticket-edit-state">State</label>
				<?php echo $f->select('ticket_state', null, $states, (!empty($ticket))? $ticket['ticket_state'] : '', array('id' => 'ticket-edit-state')) ?>
			</li>
			<li class="checkbox"><?= $f->checkbox(
				'failed',
				'Current state failed',
				(!empty($ticket))? $ticket['failed'] : false,
				(!empty($tickets))? ['disabled', 'data-multi' => true] : []
			); ?></li>
			<?php
			if (!empty($tickets)) {
				$f->register('change_failed', null, Form::FIELD_BOOL);
			}
			?>
		</ul>
	</fieldset>
	<?php if (empty($ticket) && empty($tickets)): ?>
		<fieldset>
			<legend>Children</legend>
			<ul>
				<li class="checkbox"><?= $f->checkbox('create_recording_tickets', 'Create recording ticket', true); ?></li>
				<li class="checkbox"><?= $f->checkbox('create_encoding_tickets', 'Create tickets for encoding profiles', true); ?></li>
			</ul>
		</fieldset>
	<?php endif; ?>
	<fieldset class="foldable">
		<legend>Properties</legend>
		<?php echo $this->render('shared/form/properties', array(
			'f' => $f,
			'properties' => array(
				'for' => (!empty($ticket))? $ticket->Properties : ( (!empty($tickets))? $properties : null ),
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
				<?php if (!empty($ticket)) {
					echo $f->submit('Save ticket') . ' or ';
					echo $this->linkTo('tickets', 'view', $ticket, $project, 'discard changes', array('class' => 'reset'));
				} elseif (!empty($tickets)) {
					echo $f->submit('Preview changes') . ' or ';
					echo $this->linkTo('tickets', 'index', $project, 'discard ticket', array('class' => 'reset'));
				} else {
					echo $f->submit('Create ticket') . ' or ';
					echo $this->linkTo('tickets', 'index', $project, 'discard ticket', array('class' => 'reset'));
				} ?>
			</li>
		</ul>
	</fieldset>
</form>