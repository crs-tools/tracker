<?php $this->title('Select rooms | Import | '); ?>

<div id="ticket-header">
	<h2 class="ticket"><span class="title">Select rooms</span></h2>

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

<?= $f = $reviewForm(); ?>
	<fieldset>
		<ul data-invert-checkboxes="true">
			<?php foreach ($rooms as $room => $exists): ?>
				<li class="checkbox"><?= $f->checkbox('rooms[' . $room . ']', $room, false, [], false); ?></li>
			<?php endforeach; ?>
			<li><?= $f->submit('Review changes'); ?></li>
		</ul>
	</fieldset>		
</form>