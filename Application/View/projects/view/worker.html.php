<?= $f = $workerGroupForm(); ?>
	<ul class="worker-groups clearfix">
		<?php foreach ($workerGroups as $index => $group): ?>
			<li>
				<?= $f->checkbox(
					'WorkerGroup[' . $index . '][worker_group_id]',
					$group['title'],
					isset($workerGroupAssignment[$group['id']]),
					['value' => $group['id']] +
						((isset($workerGroupAssignment[$group['id']]))?
							['data-association-destroy' => 'WorkerGroup[' . $index . '][_destroy]'] :
							[]),
					false
				); ?>
			</li>
		<?php $f->register('WorkerGroup[' . $index . '][_destroy]');
		endforeach; ?>
	</ul>
	
	<?= $f->submit('Save assignment'); ?> or <?= $this->linkTo('projects', 'view', $project, 'discard changes', array('class' => 'reset')); ?>
</form>