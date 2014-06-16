<?php $this->title('Import | '); ?>

<div id="ticket-header">
	<h2 class="ticket"><span class="title">Import</span></h2>

	<ul class="ticket-header-bar right horizontal">
		<li class="ticket-header-bar-background-left"></li>
			
		<?php if (User::isAllowed('tickets', 'create')): ?>
			<li class="action create"><?= $this->linkTo('tickets', 'create', $project, '<span>create</span>', 'Create new ticket…'); ?></li>
		<?php endif; ?>
			
		<li class="action current import"><?= $this->linkTo('import', 'index', $project, '<span>import</span>'); ?></li>

		<?php if (User::isAllowed('export', 'index')): ?>
			<li class="action export"><?= $this->linkTo('export', 'index', $project, '<span>export</span>'); ?></li>
		<?php endif; ?>
		
		<li class="ticket-header-bar-background-right"></li>
	</ul>
</div>

<?= $f = $form(array('id' => 'ticket-import')); ?>
	<fieldset>
		<legend>Check for Fahrplan updates</legend>
		<ul>
			<li>
				<?= $f->input('url','XML URL', (isset($source))? $source['value'] : '', array('class' => 'wide')); ?>
				<span class="description">
					The latest Fahrplan XML will be parsed and it'll be checked 
					wether there is a ticket with the correct metadata for every 
					event listed in the Fahrplan.
				</span>
			</li>
			<?php if (!empty($files)): ?>
				<li class="option"><label><strong>or</strong></label><br /></li>
				<li>
					<?= $f->select('file', 'import existing file', $files); ?>
				</li>
			<?php endif; ?>
			<li class="checkbox"><?= $f->checkbox('create_recording_tickets', 'Create missing recording tickets', true); ?></li>
			<li class="checkbox"><?= $f->checkbox('create_encoding_tickets', 'Create missing tickets for encoding profiles', true); ?></li>
			<li><?= $f->submit('Check for updates'); ?></li>
		</ul>
	</fieldset>		
</form>