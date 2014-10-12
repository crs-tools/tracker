<?php $this->title($title . ' | '); ?>

<div id="ticket-header">
	<h2 class="ticket"><span class="title"><?= h($title); ?></span></h2>

	<ul class="ticket-header-bar right horizontal">
		<li class="ticket-header-bar-background-left"></li>
			
		<?php if (User::isAllowed('tickets', 'create')): ?>
			<li class="action create"><?= $this->linkTo('tickets', 'create', $project, '<span>create</span>', 'Create new ticket…'); ?></li>
		<?php endif; ?>
			
		<li class="action current import"><?= $this->linkTo('import', 'index', $project, '<span>import</span>', 'Import tickets…'); ?></li>
		
		<li class="ticket-header-bar-background-right"></li>
	</ul>
</div>