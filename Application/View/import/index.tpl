<?php $this->title('Import | '); ?>

<div id="ticket-header">
	<h2 class="ticket"><span class="title">Import</span></h2>

	<ul class="ticket-header-bar right horizontal">
		<li class="ticket-header-bar-background-left"></li>
			
		<?php if (User::isAllowed('tickets', 'create')): ?>
			<li class="action create"><?php echo $this->linkTo('tickets', 'create', $project, '<span>create</span>', 'Create new ticket…'); ?></li>
		<?php endif; ?>
			
		<li class="action current import"><?php echo $this->linkTo('import', 'index', $project, '<span>import</span>'); ?></li>

		<?php if (User::isAllowed('export', 'index')): ?>
			<li class="action export"><?php echo $this->linkTo('export', 'index', $project, '<span>export</span>'); ?></li>
		<?php endif; ?>
		
		<li class="ticket-header-bar-background-right"></li>
	</ul>
</div>

<?php echo $f = $this->form('import', 'review', $project, array('id' => 'ticket-import')); ?>
	<fieldset>
		<legend>Check for Fahrplan updates</legend>
		<ul>
			<li>
				<?php echo $f->input('url','XML URL', $projectProperties['Fahrplan.XML'], array('class' => 'wide')); ?>
				<span class="description">
					The latest Fahrplan XML will be parsed and it'll be checked 
					wether there is a ticket with the correct metadata for every 
					event listed in the Fahrplan.
				</span>
			</li>
			<?php if (!empty($files)): ?>
				<li class="option"><label><strong>or</strong></label><br /></li>
				<li>
					<?php echo $f->select('file', 'import existing file', $files); ?>
				</li>
			<?php endif; ?>
			<li><?php echo $f->submit('Check for updates'); ?></li>
		</ul>
	</fieldset>		
</form>