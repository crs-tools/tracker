<?php $this->title($group['title'] . ' queue | ') ?>

<div id="ticket-header">
	<h2 class="ticket"><span><?= h($group['title']); ?> queue</span></h2>
	
	<ul class="ticket-header-bar right horizontal">
		<li class="ticket-header-bar-background-left"></li>
		
		<li class="action queue current"><?= $this->linkTo('workers', 'queue', $group, '<span>queue</span>', 'Show worker group queue'); ?></li>
		
		<?php if (User::isAllowed('workers', 'edit')): ?>
			<li class="action edit"><?= $this->linkTo('workers', 'edit_group', $group, '<span>edit</span>', 'Edit worker group…'); ?></li>
		<?php endif; ?>
		<?php if (User::isAllowed('workers', 'delete')): ?>
			<li class="action delete"><?= $this->linkTo('workers', 'delete_group', $group, '<span>delete</span>', 'Delete worker group', ['data-dialog-confirm' => 'Are you sure you want to permanently delete this worker group?']); ?></li>
		<?php endif; ?>
		
		<li class="ticket-header-bar-background-right"></li>
	</ul>
</div>

<?php if (isset($queue)): ?>
	<ul class="tickets">
		<?php foreach ($queue as $ticket) {
			echo $this->render('tickets/ticket', [
				'ticket' => $ticket
			]);
		} ?>
	</ul>
<?php endif; ?>