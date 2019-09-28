<?php if ($first): ?>
	<tr>
		<td><?= h(mb_ucfirst($state['ticket_type'])); ?></td>
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
	<?php if (!empty($skip)): ?>
		<td class="right"><?php if ($state['skippable_on_dependent']) {
			echo $f->checkbox('States[' . $index . '][skip_on_dependent]', null, $state['project_skip_on_dependent'], ['title' => 'This state will be skipped in dependent encoding profiles'], false);
		} ?></td>
	<?php endif; ?>
</tr>
<?php $f->register('States[' . $index . '][_destroy]'); ?>
