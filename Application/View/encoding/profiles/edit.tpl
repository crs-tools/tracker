<?php $this->title((isset($profile))? ('Edit encoding profile ' . $profile['name'] . ' | ') : 'Create encoding profile | '); ?>

<div id="ticket-header">
	<?php if (isset($profile)): ?>
		<h2 class="ticket"><span>Edit encoding profile <?= $this->h($profile['name']); ?></span></h2>
	<?php endif; ?>
	<ul class="ticket-header-bar right horizontal">
		<li class="ticket-header-bar-background-left"></li>
		<?php if (User::isAllowed('encodingprofiles', 'view')): ?>
			<li class="action versions<?= ($arguments['action'] == 'view')? ' current' : '' ?>"><?= $this->linkTo('encodingprofiles', 'view', $profile, '<span>versions</span>', 'Show all versions'); ?></li>
		<?php endif; ?>
		<?php if (User::isAllowed('encodingprofiles', 'edit')): ?>
			<li class="action edit<?= ($arguments['action'] == 'edit')? ' current' : '' ?>"><?= $this->linkTo('encodingprofiles', 'edit', $profile, '<span>edit</span>', 'Edit encoding profile…'); ?></li>
		<?php endif; ?>
		<?php if (User::isAllowed('encodingprofiles', 'delete')): ?>
			<li class="action delete"><?= $this->linkTo('encodingprofiles', 'delete', $profile, '<span>delete</span>', 'Delete encoding profile'); ?></li>
		<?php endif; ?>
		<li class="ticket-header-bar-background-right"></li>
	</ul>
</div>

<?= $f = $form(); ?>
	<fieldset>
		<?php if (!isset($profile)): ?>
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
			<?php if (isset($profile)):
				$useRequestValue = (!$form->getValue('save'))? false : Form::REQUEST_METHOD_FORM; ?>
				<li><?= $f->select('version', 'Version', $versions->indexBy('id','encodingProfileVersionTitle')->toArray(), $version['id'], array('data-submit-on-change' => true)); ?></li>
				<li class="checkbox"><?php if ($version['id'] != $profile->LatestVersion['id']) {
						echo $f->hidden('create_version', 1, array('readonly' => true));
						echo $f->checkbox('create_version', 'Create new version when editing the template', true, array('disabled' => true), false);
						echo '<span class="description">Editing an old version always creates a new version.</span>';
					} else {
						echo $f->checkbox('create_version', 'Create new version when editing the template', false, [], $useRequestValue);
					}
				?></li>
				
				<li><?= $f->input('description', 'Description', $version['description'], array('class' => 'wide'), $useRequestValue);  ?></li>
				<li><?= $f->textarea('xml_template', 'XML encoding template', $version['xml_template'], array('class' => 'extra-wide', 'data-has-editor' => true), $useRequestValue); ?></li>
			<?php else: ?>
				<li><?= $f->input('versions[0][description]', 'Description', '', array('class' => 'wide'));  ?></li>
				<li><?= $f->textarea('versions[0][xml_template]', 'XML encoding template', '', array('class' => 'extra-wide', 'data-has-editor' => true)); ?></li>
			<?php endif; ?>
		</ul>
	</fieldset>
	<fieldset>
		<ul>
			<li><?php 
				echo $f->submit((isset($profile))? 'Save changes' : 'Create new encoding profile', array('name' => 'save')) . ' or ';
				echo $this->linkTo('encodingprofiles', 'index', (isset($profile))? 'discard changes' : 'discard encoding profile', array('class' => 'reset'));
			?></li>
		</ul>
	</fieldset>
</form>

<?php $this->render('shared/editor.tpl'); ?>