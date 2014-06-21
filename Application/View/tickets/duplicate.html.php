<?php $this->title('Duplicate ticket ' . $ticket['title'] . ' | '); ?>

<?= $this->render('tickets/view/header', [
	'titlePrefix' => 'Duplicate ',
	'showDetails' => false,
	'currentAction' => 'duplicate'
]); ?>

<?= $f = $form(array('id' => 'ticket-edit')); ?>
	<fieldset>
		<ul>
			<li>
				<label></label>
				<p>
					When duplicating a ticket all properties will be copied,<br />
					comments and log entries are ignored.
				</p>
			</li>
			<li>
				<?= $f->input('fahrplan_id', 'New Fahrplan ID', null, ['class' => 'narrow']); ?>
				<span class="description">You have to set a new Fahrplan ID because it's unique inside a project.</span>
			</li>
			<li>
				<?= $f->select('ticket_state', 'State', $states->indexBy('ticket_state', 'ticket_state')->toArray(), $ticket['ticket_state']); ?>
			</li>
		</ul>
	</fieldset>
	<fieldset>
		<legend>Children</legend>
		<ul>
			<li>
				<?= $f->select('duplicate_recording_ticket', 'Recording ticket', [
					'' => 'Create new recording ticket',
					'1' => 'Duplicate recording ticket'
				]); ?>
			</li>
			<li>
				<label>Encoding tickets</label>
				<p>New encoding tickets will be created for the duplicated ticket.</p>
			</li>
		</ul>
	</fieldset>
	<fieldset>
		<ul>
			<li>
				<?= $f->submit('Create duplicate') . ' or ' .
					$this->linkTo('tickets', 'view', $ticket, $project, 'discard changes', ['class' => 'reset']); ?>
			</li>
		</ul>
	</fieldset>
</form>