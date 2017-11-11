<?php $this->title((!empty($project))? ('Edit project | ') : 'Create new project | ');
echo $f = $form(array('id' => 'project-edit')); ?>
	<fieldset>
		<?php if (!empty($project)): ?>
			<h2>Edit project <?php echo $this->linkTo('projects', 'settings', array('project_slug' => $project['slug']), h($project['title'])); ?></h2>
		<?php else: ?>
			<h2>Create new project</h2>
		<?php endif; ?>
		<ul>
			<li><?php echo $f->input('title', 'Title', (!empty($project))? $project['title'] : '', ['class' => 'wide']); ?></li>
			<li><?php echo $f->input('slug', 'Slug', (!empty($project))? $project['slug'] : '', ['class' => 'narrow']); ?></li>
			<li class="checkbox"><?php echo $f->checkbox('read_only', 'Archive project, disable write access.', (!empty($project))? $project['read_only'] : false); ?></li>
		</ul>
	</fieldset>
	<fieldset>
		<ul>
			<li>
				<?php if (!empty($project)) {
					echo $f->submit('Save changes') . ' or ';
					echo $this->linkTo('projects', 'settings', $project, 'discard changes', ['class' => 'reset']);
				} else {
					echo $f->submit('Create new project') . ' or ';
					echo $this->linkTo('projects', 'index', 'discard project', ['class' => 'reset']);
				} ?>
			</li>
		</ul>
	</fieldset>
</form>