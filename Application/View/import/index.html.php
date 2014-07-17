<?php $this->title('Import | '); ?>

<?= $this->render('import/_header', ['title' => 'Import']); ?>

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