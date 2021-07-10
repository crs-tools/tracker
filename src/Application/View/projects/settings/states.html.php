<?php $this->title('States | '); ?>
<?= $this->render('projects/settings/_header'); ?>

<?php $type = null;
$first = false; ?>

<?= $f = $stateForm(['disabled' => $project['read_only']]); ?>
	<div class="project-settings-save">
		<fieldset>
			<?= $f->submit('Save changes'); ?>
		</fieldset>
	</div>
	<div class="project-settings-triggerstate">
		<fieldset>
			<?= $f->select('dependee_ticket_trigger_state',
				'Minimum required state for encoding tickets to activate dependent tickets',
				$encodingStates, (!empty($project))? $project['dependee_ticket_trigger_state'] : null) ?>
		</fieldset>
	</div>
	<div class="column-50">
		<table class="default">
			<thead>
				<tr>
					<th width="20%">Type</th>
					<th width="40%">State</th>
					<th width="5%">Service</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($states as $index => $state) {
					if ($state['ticket_type'] === 'encoding') {
						continue;
					}
					
					if ($type !== $state['ticket_type']) {
						$type = $state['ticket_type'];
						$first = true;
					} else {
						$first = false;
					}
					
					
					echo $this->render('projects/settings/states/_state', [
						'f' => $f,
						'first' => $first,
						'index' => $index,
						'state' => $state
					]);
				} ?>
			</tbody>
		</table>
	</div>
	<div class="column-50">
		<table class="default">
			<thead>
				<tr>
					<th width="20%">Type</th>
					<th width="35%">State</th>
					<th width="5%">Service</th>
					<th width="5%">
						<span aria-label="Select if the state will be skipped
in dependent encoding profiles" data-tooltip="true">Skip</span>
					</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($states as $index => $state) {
					if ($state['ticket_type'] !== 'encoding') {
						continue;
					}
					
					if ($type !== $state['ticket_type']) {
						$type = $state['ticket_type'];
						$first = true;
					} else {
						$first = false;
					}
					
					
					echo $this->render('projects/settings/states/_state', [
						'f' => $f,
						'first' => $first,
						'index' => $index,
						'state' => $state,
						'skip' => true
					]);
				} ?>
			</tbody>
		</table>
	</div>
</form>
