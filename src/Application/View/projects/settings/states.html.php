<?php $this->title('States | '); ?>
<?= $this->render('projects/settings/_header'); ?>

<?php $type = null;
$typeRows = 0; ?>

<?= $f = $stateForm(['disabled' => $project['read_only']]); ?>
	<div class="project-settings-save">
		<fieldset>
			<?= $f->submit('Save changes'); ?>
		</fieldset>
	</div>
	<div class="project-settings-triggerstate">
		<fieldset>
			<?= $f->select('dependent_ticket_trigger_state',
				'Minimum required state for encoding tickets to activate dependent tickets',
				$encodingStates, (!empty($project))? $project['dependent_ticket_trigger_state'] : null) ?>
		</fieldset>
	</div>
		<?php foreach ($states as $index => $state): ?>
			<?php if ($type != $state['ticket_type']):
				$type = $state['ticket_type'];
				
				if ($typeRows > 1):
					$typeRows = 0; ?>
							</tbody>
						</table>
					</div>
				<?php endif;
				if ($typeRows == 0): ?>
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
				<?php endif;
				$typeRows++; ?>
				<tr>
					<td><?= h(mb_ucfirst($type)); ?></td>
			<?php else: ?>
				<tr>
					<td class="empty"></td>
			<?php endif; ?>
				<td><?=
					$f->checkbox(
						'States[' . $index . '][ticket_state]',
						$state['ticket_state'],
						$state['project_enabled'],
						['value' => $state['ticket_state']] +
							(($state['project_enabled'])?
								['data-association-destroy' => 'States[' . $index . '][_destroy]'] :
								[]),
						false
					) .
					$f->hidden('States[' . $index . '][ticket_type]', $state['ticket_type']);
				?></td>
				<td class="right"><?php if ($state['service_executable']) {
					echo $f->checkbox('States[' . $index . '][service_executable]', null, $state['project_service_executable'], [], false);
				} ?></td>
			</tr>
			<?php $f->register('States[' . $index . '][_destroy]'); ?>
		<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</form>
