<?php $type = null; ?>

<?= $f = $stateForm(); ?>
	<table class="default">
		<thead>
			<tr>
				<th width="20%">Type</th>
				<th width="20%">State</th>
				<th></th>
				<th width="5%">Executable by workers</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($states as $index => $state): ?>
			<tr>
				<?php if ($type != $state['ticket_type']):
					$type = $state['ticket_type']; ?>
					<td><?= $this->h(mb_ucfirst($type)); ?></td>
				<?php else: ?>
					<td></td>
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
				<td></td>
				<td class="right"><?php if ($state['service_executable']) {
					echo $f->checkbox('States[' . $index . '][service_executable]', null, $state['project_service_executable'], [], false);
				} ?></td>
			</tr>
			<?php $f->register('States[' . $index . '][_destroy]'); ?>
		<?php endforeach; ?>
		</tbody>
	</table>
	
	
	<?= $f->submit('Save changes'); ?> or <?= $this->linkTo('projects', 'view', $project, 'discard changes', array('class' => 'reset')); ?>
</form>