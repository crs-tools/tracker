<?php $this->title('Encoding profiles | ') ?>

<?php if (User::isAllowed('encodingprofiles', 'create')): ?>
	<ul class="ticket-header-bar right horizontal table">
		<li class="ticket-header-bar-background-left"></li>
			<li class="action create"><?php echo $this->linkTo('encodingprofiles', 'create', '<span>create</span>', 'Create new encoding profile…'); ?></li>
		<li class="ticket-header-bar-background-right"></li>
	</ul>
<?php endif; ?>

<div class="table encoding-profiles">
	<h2>Encoding profiles</h2>
	
	<table>
		<thead>
			<tr>
				<th class="name" width="30%">Name</th>
				<th class="slug" width="20%">Slug</th>
				<th class="version"></th>
				<th class="delete" width="3%">&nbsp;</th>
				<th class="edit" width="5%">&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($profiles as $profile): ?>
				<tr>
					<td class="name"><?= h($profile['name']); ?></td>
					<td class="slug"><?= h($profile['slug']); ?></td>
					<td class="version link"><?php if (User::isAllowed('encodingprofiles', 'view')) {
						echo $this->linkTo('encodingprofiles', 'view', $profile, 'Show ' . $profile['versions_count'] . ' version' . (($profile['versions_count'] == 1)? '' : 's'));
					} ?></td>
					<td class="delete link hide right"><?php if (User::isAllowed('encodingprofiles', 'delete')) {
						echo $this->linkTo('encodingprofiles', 'delete', $profile, 'delete', array('data-dialog-confirm' => 'Are you sure you want to permanently delete this encoding ticket?'));
					} ?></td>
					<td class="edit link hide right"><?php if (User::isAllowed('encodingprofiles', 'edit')) {
						echo $this->linkTo('encodingprofiles', 'edit', $profile, 'edit');
					} ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
		
	</table>
	<?php if (!isset($profile)): ?>
		<p>
			No existing encoding profiles found.
			<?php if (User::isAllowed('encodingprofiles', 'create')) {
				echo $this->linkTo('encodingprofiles', 'create', 'Create new encoding profile') . '.';
			} ?>
		</p>
	<?php endif; ?>
</div>