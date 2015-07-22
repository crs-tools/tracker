<?php $this->title('Worker groups | '); ?>
<?= $this->render('projects/settings/_header'); ?>

<?= $f = $workerGroupForm(['disabled' => $project['read_only']]); ?>
	<div class="project-settings-save">
		<fieldset>
			<?= $f->submit('Save assignment'); ?>
		</fieldset>
	</div>
		
	<ul class="worker-groups clearfix">
		<?php foreach ($workerGroups as $index => $group): ?>
			<li>
				<?= $f->checkbox(
					'WorkerGroup[' . $index . '][worker_group_id]',
					h($group['title']) . 
						(($group['paused'])? ' <em>(paused)</em>' : ''),
					isset($workerGroupAssignment[$group['id']]),
					['value' => $group['id']] +
						((isset($workerGroupAssignment[$group['id']]))?
							['data-association-destroy' => 'WorkerGroup[' . $index . '][_destroy]'] :
							[]),
					false
				); ?>
				
				<?= $this->linkTo(
					'projects', 'edit_filter', $group, $project,
					($group['filter_count'] > 0)?
						(($group['filter_count'] === 1)?
							'Edit 1 filter' : 'Edit ' . $group['filter_count'] . ' filters') 
						: 'Add filter'
				); ?>
				
				<?php if (User::isAllowed('workers', 'queue')) {
					echo $this->linkTo('workers', 'queue', $group, 'Queue');
				} ?>
			</li>
		<?php $f->register('WorkerGroup[' . $index . '][_destroy]');
		endforeach; ?>
	</ul>
</form>