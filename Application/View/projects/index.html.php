<?php $this->title('Projects | '); ?>


<div id="ticket-header">
	<h2 class="ticket"><span class="title">Projects</span></h2>
	
	<?php if (User::isAllowed('projects', 'create')): ?>
		<ul class="ticket-header-bar right horizontal">
			<li class="ticket-header-bar-background-left"></li>
			<li class="action create"><?= $this->linkTo('projects', 'create', '<span>create</span>', 'Create new project'); ?></li>
			<li class="ticket-header-bar-background-right"></li>
		</ul>
	<?php endif; ?>
</div>

<?php $hasReadonly = false; ?>
<ul class="projects">
	<?php if (!empty($projects)): ?>
		<?php if (User::isRestricted()): ?>
			<li class="project-restricted">
				Your project access is restricted. More projects may exist.
			</li>
		<?php endif; ?>
		<?php foreach ($projects as $project): ?>
			<?php if($project['read_only'] && !$hasReadonly): ?>
				<?php $hasReadonly = true; ?>
				</ul>
				<form>
					<fieldset class="foldable projects-list">
						<legend>Archived projects</legend>
						<ul class="projects">
			<?php endif ?>
			<li>
				<?= $this->linkTo('tickets', 'feed', ['project_slug' => $project['slug']], h($project['title']) . '<span>›</span>', $project['title'], ['class' => 'link']); ?>
				
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
<?php if($hasReadonly): ?>
	</fieldset>
</form>
<?php endif ?>