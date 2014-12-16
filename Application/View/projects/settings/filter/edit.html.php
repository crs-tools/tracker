<?php $hasFilter = ($workerGroupFilter->getRows() > 0);
$this->title(($hasFilter)? 'Edit Filter | ' : 'Add Filter | '); ?>
<?= $this->render('projects/settings/_header'); ?>

<?php echo $f = $form(['disabled' => $project['read_only']]); ?>
	<fieldset>
		<h2><?= ($hasFilter)? 'Edit' : 'Add'; ?> filter for <em><?= h($workerGroup['title']); ?></em> assignment</h2>
		
		<ul>
			<li>
				<?= $f->select('evaluation', 'Evaluation', [
					'not' => 'None of the properties has to match',
					'or' => 'At least one of the properties has to match',
					'and' => 'All of the properties have to match'
				], 'or', ['disabled' => true]); ?>
			</li>
		</ul>
	</fieldset>
	
	<fieldset>
		<legend>Property Conditions</legend>
		<?= $this->render('shared/form/properties', [
			'f' => $f,
			'properties' => [
				'for' => $workerGroupFilter,
				'field' => 'WorkerGroupFilter',
				'description' => 'property condition',
				'key' => 'property_key',
				'value' => 'property_value',
				
				'hidden' => [
					'worker_group_id' => $workerGroup['id']
				]
			]
		]); ?>
	</fieldset>
	
	<fieldset>
		<ul>
			<li>
				<?= $f->submit(($hasFilter)? 'Save changes' : 'Add filter'); ?> or 
				<?= $this->linkTo('projects', 'worker', $project, ($hasFilter)? 'discard changes' : 'discard filter', ['class' => 'reset']); ?>
			</li>
		</ul>
	</fieldset>
</form>