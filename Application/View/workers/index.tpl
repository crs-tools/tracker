<?php if (User::isAllowed('worker', 'create_group')): ?>
	<ul class="ticket-header-bar right horizontal table">
		<li class="ticket-header-bar-background-left"></li>
			<li class="action create"><?php echo $this->linkTo('workers', 'create_group', '<span>create</span>', 'Create new worker group'); ?></li>
		<li class="ticket-header-bar-background-right"></li>
	</ul>
<?php endif; ?>

<div class="table">
	<h2>Worker groups</h2>
	<?php foreach ($groups as $group): ?>
		<table class="stripe">
			<thead>
				<tr>
					<th colspan="3"><?php echo $group['title']; ?></th>
					<th></th>
					<th width="5%" class="link hide right">
						<?php if (User::isAllowed('workers', 'delete_group')) {
							echo $this->linkTo('workers', 'delete_group', $group, 'delete', array('data-dialog-confirm' => 'Are you sure you want to permanently delete this worker group?'));
						} ?>
					</th>
					<th width="5%" class="link hide right">
						<?php if (User::isAllowed('workers', 'delete_group')) {
							echo $this->linkTo('workers', 'edit_group', $group, 'edit');
						} ?>
					</th>
				</tr>
				<tr>
					<th width="15%">Name</th>
					<th width="30%">Hostname</th>
					<th width="30%">Last seen</th>
					<th colspan="3"></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($group->Worker->orderBy('last_seen') as $worker): ?>
					<tr>
						<td><?php echo $worker['name']; ?></td>
						<td><?php echo $worker['hostname']; ?></td>
						<td><?php echo $worker['last_seen']; ?></td>
						<td colspan="3"></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endforeach; ?>
</div>