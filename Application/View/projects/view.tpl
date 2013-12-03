<div id="ticket-header">
	<h2 class="ticket"><span class="title"><?php echo $this->h($project['title']); ?></span></h2>

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

<?php if (!empty($properties)): ?>
	<h3>Properties</h3>
	<table class="properties">
		<?php foreach ($project->ProjectProperties as $title => $root): ?>
			<tr>
				<th colspan="2"><?php echo $title; ?></th>
			</tr>
			<?php foreach ($root as $property): ?>
				<tr>
					<td class="key"><?php echo (mb_strpos($property['name'], '.') !== false)? (mb_substr($property['name'], mb_strlen($title) + 1)) : $property['name']; ?></td>
					<td class="value">
						<?php if (mb_strlen($property['value']) > 80 and ($pos = mb_strpos($property['value'], ' ', 80)) !== false) {
							echo mb_substr($property['value'], 0, $pos + 1) . '<span class="more">' . mb_substr($property['value'], $pos + 1) . '</span>';
						} else {
							echo $property['value'];
						} ?>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endforeach; ?>
	</table>
<?php endif; ?>