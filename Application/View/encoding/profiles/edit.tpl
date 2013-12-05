<?php $this->title((isset($profile))? ('Edit encoding profile ' . $profile['name'] . ' | ') : 'Create encoding profile | '); ?>

<?= $f = $form(); ?>
	<fieldset>
		<?php if (isset($profile)): ?>
			<h2>Edit encoding profile <?= $this->h($profile['name']); ?></h2>
		<?php else: ?>
			<h2>Create new encoding profile</h2>
		<?php endif; ?>
		<ul>
			<li><?= $f->input('name', 'Name', $profile['name'], array('class' => 'wide')); ?></li>
			<li><?= $f->input('slug', 'Slug', $profile['slug'], array('class' => 'narrow')); ?></li>
		</ul>
	</fieldset>
	
	<fieldset>
		<legend>Files</legend>
		<ul>
			<li><?= $f->input('extension', 'File extension', $profile['extension'], array('class' => 'narrow')); ?></li>
			<li><?= $f->input('mirror_folder', 'Folder name on mirror', $profile['mirror_folder']); ?></li>
		</ul>
	</fieldset>
	<fieldset>
		<legend>Encoding</legend>
		<ul>
			<?php if (isset($profile)): ?>
				<li><?= $f->select('version', 'Version', $versions->indexBy(
					'id',
					function(array $entry) {
						return 'r' . $entry['revision'] .
							(($entry['description'] !== null)? (' ' . $entry['description']) : '') .
							' (' . (new Datetime($entry['created']))->format('d.m.Y H:i') . ')';
					}
				)->toArray()); ?></li>
				<li class="checkbox"><?= $f->checkbox('create_version', 'Create new version when editing the template'); ?></li>
			<?php endif; ?>
			<li><?= $f->input('versions[0][description]', 'Description', '', array('class' => 'wide'));  ?></li>
			<li><?= $f->textarea('versions[0][xml_template]', 'XML encoding template', '', array('class' => 'extra-wide')); ?></li>
		</ul>
	</fieldset>
	<fieldset>
		<ul>
			<li><?php 
				echo $f->submit((isset($profile))? 'Save changes' : 'Create new encoding profile') . ' or ';
				echo $this->linkTo('encodingprofiles', 'index', (isset($profile))? 'discard changes' : 'discard encoding profile', array('class' => 'reset'));
			?></li>
		</ul>
	</fieldset>
</form>

<?php $this->render('shared/editor.tpl'); ?>