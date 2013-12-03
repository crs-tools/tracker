<?php $this->title((isset($profile))? ('Edit encoding profile ' . $profile['name'] . ' | ') : 'Create encoding profile | '); ?>

<?= $f = $form(); ?>
	<fieldset>
		<?php if (!empty($profile)): ?>
			<h2>Edit encoding profile <?php echo Filter::specialChars($profile['name']); ?></h2>
		<?php else: ?>
			<h2>Create new encoding profile</h2>
		<?php endif; ?>
		<ul>
			<li><?php echo $f->input('name', 'Name', $profile['name'], array('class' => 'wide')); ?></li>
			<li><?php echo $f->input('slug', 'Slug', $profile['slug'], array('class' => 'narrow')); ?></li>
		</ul>
	</fieldset>
	
	<fieldset>
		<legend>Files</legend>
		<ul>
			<li><?php echo $f->input('extension', 'File extension', $profile['extension'], array('class' => 'narrow')); ?></li>
			<li><?php echo $f->input('mirror_folder', 'Folder name on mirror', $profile['mirror_folder']); ?></li>
		</ul>
	</fieldset>
	<fieldset>
		<legend>Encoding</legend>
		<ul>
			<li><?php echo $f->textarea('xml_template', 'XML encoding template', $profile['xml_template'], array('class' => 'extra-wide')); ?></li>
			<li>
				<?php if (!empty($profile)) {
					echo $f->submit('Save changes') . ' or ';
					echo $this->linkTo('projects', 'view', $project, 'discard changes', array('class' => 'reset'));
				} else {
					echo $f->submit('Create new encoding profile') . ' or ';
					echo $this->linkTo('projects', 'view', $project, 'discard encoding profile', array('class' => 'reset'));
				} ?>
			</li>
		</ul>
	</fieldset>
</form>
<?php $this->contentFor('scripts', '<script src="' . $this->Request->getRootURL() . 'javascript/codemirror.js" type="text/javascript"></script>'); ?>