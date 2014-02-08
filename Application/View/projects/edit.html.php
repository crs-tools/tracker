<?php $this->title((!empty($project))? ('Edit project | ') : 'Create new project | ');
echo $f = $form(array('id' => 'project-edit')); ?>
	<fieldset>
		<?php if (!empty($project)): ?>
			<h2>Edit project <?php echo $this->linkTo('projects', 'view', array('project_slug' => $project['slug']), $project['title']); ?></h2>
		<?php else: ?>
			<h2>Create new project</h2>
		<?php endif; ?>
		<ul>
			<li><?php echo $f->input('title', 'Title', $project['title'], array('class' => 'wide')); ?></li>
			<li><?php echo $f->input('slug', 'Slug', $project['slug'], array('class' => 'narrow')); ?></li>
			<li class="checkbox"><?php echo $f->checkbox('read_only', 'Disable write access to this project.', $project['read_only']); ?></li>
		</ul>
	</fieldset>
	<fieldset>
		<legend>Languages</legend>
		<?php echo $this->render('shared/form/properties.html.php', array(
			'f' => $f,
			'properties' => array(
				'for' => (!empty($project))? $project->Languages->orderBy('language') : null,
				'field' => 'languages',
				'description' => 'language',
				'key' => 'language',
				'value' => 'description'
			)
		)); ?>
	</fieldset>
	<fieldset>
		<legend>Properties</legend>
		<?php echo $this->render('shared/form/properties.html.php', array(
			'f' => $f,
			'properties' => array(
				'for' => (!empty($project))? $project->Properties->orderBy('name') : null,
				'field' => 'properties',
				'description' => 'property',
				'key' => 'name',
				'value' => 'value'
			)
		)); ?>
	</fieldset>
	<fieldset>
		<ul>
			<li>
				<?php if (!empty($project)) {
					echo $f->submit('Save changes') . ' or ';
					
					if (isset($_GET['ref']) and $_GET['ref'] == 'index') {
						echo $this->linkTo('projects', 'index', 'discard changes', array('class' => 'reset'));
					} else {
						echo $this->linkTo('tickets', 'index', array('project_slug' => $project['slug']), 'discard changes', array('class' => 'reset'));
					}
				} else {
					echo $f->submit('Create new project') . ' or ';
					echo $this->linkTo('projects', 'index', 'discard project', array('class' => 'reset'));
				} ?>
			</li>
		</ul>
	</fieldset>
</form>