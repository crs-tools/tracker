<?php $this->title('Properties & Languages | '); ?>
<?= $this->render('projects/settings/_header'); ?>

<?php echo $f = $form(['disabled' => $project['read_only']]); ?>
	<div class="project-settings-save">
		<fieldset>
			<?= $f->submit('Save changes'); ?>
		</fieldset>
	</div>
	<fieldset>
		<legend>Properties</legend>
		<?= $this->render('shared/form/properties', [
			'f' => $f,
			'properties' => [
				'for' => (!empty($project))? $project->Properties : null,
				'field' => 'properties',
				'description' => 'property',
				'key' => 'name',
				'value' => 'value'
			]
		]); ?>
	</fieldset>
	
	<fieldset>
		<legend>Languages</legend>
		<?= $this->render('shared/form/properties', [
			'f' => $f,
			'properties' => [
				'for' => (!empty($project))? $project->Languages : null,
				'field' => 'languages',
				'description' => 'language',
				'key' => 'language',
				'value' => 'description'
			]
		]); ?>
	</fieldset>
	
	<fieldset>
		<ul>
			<li>
				<?php echo $f->submit('Save changes') . ' or ';
				echo $this->linkTo('projects', 'properties', $project, 'discard changes', ['class' => 'reset']); ?>
			</li>
		</ul>
	</fieldset>
</form>