<?php $this->title('Projects | '); ?>


<div id="ticket-header">
	<h2 class="ticket"><span class="title">All projects</span></h2>
	
	<?php if (User::isAllowed('projects', 'create')): ?>
		<ul class="ticket-header-bar right horizontal">
			<li class="ticket-header-bar-background-left"></li>
			<li class="action create"><?php echo $this->linkTo('projects', 'create', '<span>create</span>', 'Create new project'); ?></li>
			<li class="ticket-header-bar-background-right"></li>
		</ul>
	<?php endif; ?>
</div>

<ul class="projects">
	<?php if (!empty($projects)): ?>
		<?php foreach ($projects as $project): ?>
			<li>
				<?php echo $this->linkTo('tickets', 'feed', array('project_slug' => $project['slug']), $project['title'] . (($project['read_only'])? ' (locked)' : '') . '<span>›</span>', array('class' => 'link', 'title' => $project['title']));
				
				if (User::isAllowed('projects', 'view')) {
					echo $this->linkTo('projects', 'view', array('project_slug' => $project['slug']), 'settings', array('class' => 'settings'));
				}
				
				if (User::isAllowed('projects', 'edit')) {
					echo $this->linkTo('projects', 'edit', $project, array('?ref=index'), 'edit project', array('class' => 'edit'));
				}
				
				if (User::isAllowed('projects', 'delete')) {				
					echo $this->linkTo('projects', 'delete', $project, 'delete project', array('class' => 'delete'));
				} ?>
			</li>
		<?php endforeach; ?>
	<?php endif; ?>
</ul>