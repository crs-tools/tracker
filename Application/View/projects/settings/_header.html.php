<div id="ticket-header">
	<h2 class="ticket"><span class="title"><?= h($project['title']); ?></span></h2>
	
	<?php if ($project['read_only']): ?>
		<div class="flags">
			<span class="failed">read only</span>
		</div>
	<?php endif; ?>
	
	<ul class="ticket-header-bar right horizontal">
		<li class="ticket-header-bar-background-left"></li>
		
		<?php if (User::isAllowed('projects', 'duplicate')): ?>
			<li class="action duplicate"><?= $this->linkTo('projects', 'duplicate', $project, '<span>duplicate</span>', 'Duplicate project'); ?></li>
		<?php endif; ?>
		
		<?php if (User::isAllowed('projects', 'edit')): ?>
			<li class="action edit"><?php echo $this->linkTo('projects', 'edit', $project, '<span>edit name</span>', 'Edit project name'); ?></li>
		<?php endif; ?>
		
		<?php if (User::isAllowed('projects', 'delete')): ?>
			<li class="action delete"><?php echo $this->linkTo('projects', 'delete', $project, '<span>delete</span>', 'Delete project…'); ?></li>
		<?php endif; ?>
		<li class="ticket-header-bar-background-right"></li>
	</ul>
</div>

<ul class="project-settings-header horizontal clearfix">
	<li<?= ($arguments['action'] === 'settings')? ' class="current"' : ''; ?>><?= $this->linkTo('projects', 'settings', $project, 'Info/<br />Statistics', 'Info/Statistics', ['class' => 'info']); ?></li>
	
	<?php if (User::isAllowed('projects', 'properties')): ?>
		<li<?= ($arguments['action'] === 'properties')? ' class="current"' : ''; ?>><?= $this->linkTo('projects', 'properties', $project, 'Properties/<br />Languages', 'Properties/Languages', ['class' => 'properties']); ?></li>
	<?php endif; ?>
	
	<?php if (User::isAllowed('projects', 'profiles')): ?>
		<li<?= ($arguments['action'] === 'profiles')? ' class="current"' : ''; ?>><?= $this->linkTo('projects', 'profiles', $project, 'Encoding profiles', ['class' => 'encoding']); ?></li>
	<?php endif; ?>
	
	<?php if (User::isAllowed('projects', 'states')): ?>
		<li<?= ($arguments['action'] === 'states')? ' class="current"' : ''; ?>><?= $this->linkTo('projects', 'states', $project, 'States', ['class' => 'states']); ?></li>
	<?php endif; ?>
	
	<?php if (User::isAllowed('projects', 'worker')): ?>
		<li<?= ($arguments['action'] === 'worker')? ' class="current"' : ''; ?>><?= $this->linkTo('projects', 'worker', $project, 'Worker groups', ['class' => 'worker']); ?></li>
	<?php endif; ?>
	
	<?php /*<li<?= ($arguments['action'] === 'webhooks')? ' class="current"' : ''; ?>><?= $this->linkTo('projects', 'webhooks', $project, 'Webhooks', ['class' => 'hooks']); ?></li>*/ ?>
</ul>