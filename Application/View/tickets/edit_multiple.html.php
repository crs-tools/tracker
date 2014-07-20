<?php $this->title('Edit ' . $tickets->getRows() . ' ' . $ticketType . ' tickets | '); ?>

<div id="ticket-header">
	<h2 class="ticket">
		<span class="title">
			Edit <?= $this->linkTo(
				'tickets', 'search', $project, ['?id=' . implode(',', $tickets->pluck('id'))],
				$tickets->getRows() . ' ' . $ticketType . ' tickets'
			); ?>
		</span>
	</h2>
</div>

<?= $f = $form(['id' => 'ticket-edit-multiple']); ?>
	<fieldset>
		<ul>
			<li><?=$f->select(
				'priority', 'Priority',
				Ticket::$priorities,
				'1'
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
				<?= $f->checkbox('needs_attention', 'Tickets needs attention', false, [], false); ?>
				<?php $f->register('needs_attention', ['0', '1']); ?>
			</li>
		</ul>
	</fieldset>
	<fieldset>
		<legend>State</legend>
		<ul>
			<li>
				<?php if (!empty($ticket)): ?>
					<?= $ticket['ticket_state']; ?><span class="description-color">  ⟶ </span>
				<?php endif ?>
				
				<label for="ticket-edit-state">State</label>
				<?= $f->select('ticket_state', null, $states, null, ['id' => 'ticket-edit-state']); ?>
			</li>
			<li class="checkbox">
				<?= $f->checkbox('failed', 'Selected state failed', false, [], false); ?>
				<?php $f->register('failed', ['0', '1']); ?>
			</li>
		</ul>
	</fieldset>
	<fieldset class="foldable">
		<legend>Properties</legend>
		<?= $this->render('shared/form/properties', [
			'f' => $f,
			'properties' => [
				'for' => $properties,
				'field' => 'properties',
				'description' => 'property',
				'key' => 'name',
				'value' => 'value'
			]
		]); ?>
	</fieldset>
	<fieldset class="ticket-edit-multiple-exclude">
		<ul>
			<li>
				<?php echo $f->submit('Save tickets') . ' or ';
				echo $this->linkTo('tickets', 'index', $project, 'discard changes', ['class' => 'reset']); ?>
			</li>
		</ul>
	</fieldset>
</form>