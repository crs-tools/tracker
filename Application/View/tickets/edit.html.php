<?php if (empty($ticket)) {
	$this->title('Create new ticket | ');
} else {
	$this->title('Edit ticket ' . $ticket['title'] . ' | ');
} ?>

<?php if (!empty($ticket)):
	echo $this->render('tickets/view/_header', [
		'titlePrefix' => 'Edit ',
		'showDetails' => false,
		'currentAction' => 'edit'
	]);
else: ?>
	<div id="ticket-header">
		<h2 class="ticket"><span class="title">Create new ticket</span></h2>
		
		<ul class="ticket-header-bar right horizontal">
			<li class="ticket-header-bar-background-left"></li>
			<li class="action create current"><?= $this->linkTo('tickets', 'create', $project, '<span>create</span>', 'Create new ticket…'); ?></li>
			
			<?php if (User::isAllowed('import', 'index')): ?>
				<li class="action import"><?= $this->linkTo('import', 'index', $project, '<span>import</span>', 'Import tickets…'); ?></li>
			<?php endif; ?>
			<li class="ticket-header-bar-background-right"></li>
		</ul>
	</div>
<?php endif; ?>

<?= $f = $form(['id' => 'ticket-edit']); ?>
	<fieldset>
		<ul>
			<?php if (empty($ticket)): ?>
				<li><?= $f->input('fahrplan_id', 'Fahrplan ID', null, ['class' => 'narrow']); ?></li>
			<?php endif ?>
			
			<li><?= $f->input('title', 'Title', (!empty($ticket))? $ticket['title'] : '', ['class' => 'wide', 'disabled' => ($ticket['parent_id'] !== null)]); ?></li>
			
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
				Ticket::$priorities,
				(!empty($ticket))? $ticket['priority'] : '1'
			); ?></li>
			<li><?= $f->select(
				'handle_id', 'Assignee',
				['' => '–'] + $users,
				(!empty($ticket))? $ticket['handle_id'] : '',
				[
					'data-current-user-id' => User::getCurrent()['id'],
					'data-current-user-name' => User::getCurrent()['name']
				]
			); ?></li>
			<li class="checkbox">
				<?= $f->checkbox('needs_attention', 'Ticket needs attention', (!empty($ticket))? $ticket['needs_attention'] : false); ?>
			</li>
			<?php $f->register('comment'); ?>
		</ul>
	</fieldset>
	<fieldset>
		<legend>State</legend>
		<ul>
			<li>
				<div class="ticket-edit-state">
					<?php if (!empty($ticket)): ?>
						<?= $ticket['ticket_state']; ?><span class="description-color">  ⟶ </span>
					<?php endif ?>
				
					<label for="ticket-edit-state">State</label>
					<?= $f->select('ticket_state', null, $states, (!empty($ticket))? $ticket['ticket_state'] : null, ['id' => 'ticket-edit-state']) ?>
				</div>
			</li>
			<li class="checkbox"><?= $f->checkbox('failed', 'Current state failed', (!empty($ticket))? $ticket['failed'] : false); ?></li>
		</ul>
	</fieldset>
	<fieldset class="foldable">
		<legend>Properties</legend>
		<?= $this->render('shared/form/properties', [
			'f' => $f,
			'properties' => [
				'for' => (!empty($ticket))? $ticket->Properties : null,
				'field' => 'properties',
				'description' => 'property',
				'key' => 'name',
				'value' => 'value'
			]
		]); ?>
	</fieldset>
	<fieldset>
		<ul>
			<li>
				<?php if (empty($ticket)) {
					echo $f->submit('Create ticket') . ' or ';
					echo $this->linkTo('tickets', 'index', $project, 'discard ticket', ['class' => 'reset']);
				} else {
					echo $f->submit('Save ticket') . ' or ';
					echo $this->linkTo('tickets', 'view', $ticket, $project, 'discard changes', ['class' => 'reset']);
					echo $f->hidden('last_modified', $ticket['modified']);
				} ?>
			</li>
		</ul>
	</fieldset>
</form>