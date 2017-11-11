<div id="ticket-header">
	<h2 class="ticket"><span class="title"><?php echo Filter::specialChars($project['title']); ?></span></h2>

	<ul class="ticket-header-bar right horizontal">
		<li class="ticket-header-bar-background-left"></li>
		
		<?php if ($this->User->isAllowed('projects', 'edit')): ?>
			<li class="action edit"><?php echo $this->linkTo('projects', 'edit', $project, '<span>edit</span>', 'Edit project'); ?></li>
		<?php endif; ?>
		
		<?php if ($this->User->isAllowed('projects', 'delete')): ?>
			<li class="action delete"><?php echo $this->linkTo('projects', 'delete', $project, '<span>delete</span>', 'Delete project…'); ?></li>
		<?php endif; ?>
		<li class="ticket-header-bar-background-right"></li>
	</ul>
</div>

<?php if (!empty($properties)): ?>	
	<h3>Properties</h3>
	<table class="properties">
		<?php foreach ($properties as $title => $root): ?>
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

<br />

<ul class="ticket-header-bar right horizontal table">
	<li class="ticket-header-bar-background-left"></li>
	<?php if ($this->User->isAllowed('user', 'create')): ?>
		<li class="action create"><?php echo $this->linkTo('encodingprofiles', 'create', $project, '<span>create</span>', 'Create new encoding profile…'); ?></li>
	<?php endif; ?>
	<li class="ticket-header-bar-background-right"></li>
</ul>

<div class="table">
	<h2>Encoding Profiles</h2>
	
	<?php if (!empty($profiles)): ?>
		<table>
			<thead>
				<tr>
					<th width="30%">Name</th>
					<th width="10%">Slug</th>
					<th width="10%">Extension</th>
					<th></th>
					<th width="3%">&nbsp;</th>
					<th width="5%">&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($profiles as $i => $profile): ?>
					<tr class="<?php echo (($i & 1) ? 'even' : 'odd'); ?>">
						<td><?php echo $profile['name']; ?></td>
						<td><?php echo $profile['slug']; ?></td>
						<td><?php echo $profile['extension']; ?></td>
						<td></td>
						<td class="link hide right"><?php if ($this->User->isAllowed('encodingprofiles', 'delete')) {
							echo $this->linkTo('encodingprofiles', 'delete', $profile + $project, 'delete', array('class' => 'confirm-user-delete'));
						} ?></td>
						<td class="link hide right"><?php if ($this->User->isAllowed('encodingprofiles', 'edit')) {
							echo $this->linkTo('encodingprofiles', 'edit', $profile + $project, 'edit');
						} ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else: ?>
		<p>
			No existing encoding profiles found.
			<?php if ($this->User->isAllowed('encodingprofiles', 'create')) {
				echo $this->linkTo('encodingprofiles', 'create', $project, 'Create new encoding profile')/* . ' or ' . $this->linkTo('encodingprofiles', 'import', $project, 'import profiles from another project')*/ . '.';
			} ?>
		</p>
	<?php endif; ?>
</div>