<?php if (!empty($project)) {
	$this->title('Edit project ' . Filter::specialChars($project['title']) . ' | ');
	echo $f = $this->form('projects', 'edit', $project + ((Request::get('ref') == 'index')? array('?ref=index') : array()), array('id' => 'project-edit'));
} else {
	$this->title('Create new project | ');
	echo $f = $this->form('projects', 'create', array(), array('id' => 'project-edit'));
} ?>
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
		<ul class="edit-properties" data-property-object="language">
			<?php if (!empty($languages)): ?>
				<?php foreach($languages as $key => $value): ?>
					<li><?php echo $f->input('languages[' . $key . ']', $key, $value); ?></li>
				<?php endforeach; ?>
			<?php endif; ?>
		</ul>
	</fieldset>
	<fieldset>
		<legend>Properties</legend>
		<ul class="edit-properties" data-property-object="property">
			<?php if (!empty($properties)): ?>
				<?php foreach($properties as $key => $value): ?>
					<li><?php echo $f->input('properties[' . $key . ']', $key, $value); ?></li>
				<?php endforeach; ?>
			<?php endif; ?>
		</ul>
	</fieldset>
	<fieldset>
		<ul>
			<li>
				<?php if (!empty($project)) {
					echo $f->submit('Save changes') . ' or ';
					
					if (Request::get('ref') == 'index') {
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