<div id="ticket-header">
	<h2 class="ticket"><span class="title"><?= h($project['title']); ?></span></h2>

	<ul class="ticket-header-bar right horizontal">
		<li class="ticket-header-bar-background-left"></li>
		
		<?php if (User::isAllowed('projects', 'edit')): ?>
			<li class="action edit"><?php echo $this->linkTo('projects', 'edit', $project, '<span>edit</span>', 'Edit project'); ?></li>
		<?php endif; ?>
		
		<?php if (User::isAllowed('projects', 'delete')): ?>
			<li class="action delete"><?php echo $this->linkTo('projects', 'delete', $project, '<span>delete</span>', 'Delete project…'); ?></li>
		<?php endif; ?>
		<li class="ticket-header-bar-background-right"></li>
	</ul>
</div>

<h3 class="table">Properties</h3>

<?= $this->render('shared/properties.html.php'); ?>

<h3 class="table">Encoding profiles</h3>
<?= $this->render('projects/view/profiles.html.php'); ?>

<h3 class="table">States</h3>
<?= $this->render('projects/view/states.html.php'); ?>

<h3 class="table">Worker groups</h3>
<?= $this->render('projects/view/worker.html.php'); ?>