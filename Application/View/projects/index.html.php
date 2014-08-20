<?php $this->title('Projects | '); ?>


<div id="ticket-header">
	<h2 class="ticket"><span class="title">All projects</span></h2>
	
	<?php if (User::isAllowed('projects', 'create')): ?>
		<ul class="ticket-header-bar right horizontal">
			<li class="ticket-header-bar-background-left"></li>
			<li class="action create"><?= $this->linkTo('projects', 'create', '<span>create</span>', 'Create new project'); ?></li>
			<li class="ticket-header-bar-background-right"></li>
		</ul>
	<?php endif; ?>
</div>

<ul class="projects">
	<?php if (!empty($projects)): ?>
		<?php foreach ($projects as $project): ?>
			<li>
				<?= $this->linkTo('tickets', 'feed', ['project_slug' => $project['slug']], $project['title'] . (($project['read_only'])? ' (locked)' : '') . '<span>›</span>', $project['title'], ['class' => 'link']); ?>
				
				<ul class="actions horizontal">
					<li><?= $this->linkTo('tickets', 'index', ['project_slug' => $project['slug']], 'tickets'); ?></li>
					
					<?php if (User::isAllowed('projects', 'settings')): ?>
						<li><?= $this->linkTo('projects', 'settings', ['project_slug' => $project['slug']], 'project settings'); ?></li>
					<?php endif; ?>
				</ul>
			</li>
		<?php endforeach; ?>
	<?php endif; ?>
</ul>
