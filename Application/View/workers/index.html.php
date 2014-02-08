<?php $this->title('Worker groups | '); ?>

<?php if (User::isAllowed('worker', 'create_group')): ?>
	<ul class="ticket-header-bar right horizontal table">
		<li class="ticket-header-bar-background-left"></li>
			<li class="action create"><?= $this->linkTo('workers', 'create_group', '<span>create</span>', 'Create new worker group'); ?></li>
		<li class="ticket-header-bar-background-right"></li>
	</ul>
<?php endif; ?>

<div class="table">
	<h2>Worker groups</h2>
	<?php foreach ($groups as $group): ?>
		<table class="stripe">
			<thead>
				<tr>
					<th colspan="3"><?= h($group['title']); ?></th>
					<th></th>
					<th width="15%" class="link right small">
						<?php echo $this->linkTo('workers', 'queue', $group, 'show queue'); ?>
					</th>
					<th width="5%" class="link right">
						<?php if (User::isAllowed('workers', 'delete_group')) {
							echo $this->linkTo('workers', 'delete_group', $group, 'delete', array('data-dialog-confirm' => 'Are you sure you want to permanently delete this worker group?'));
						} ?>
					</th>
					<th width="5%" class="link right">
						<?php if (User::isAllowed('workers', 'delete_group')) {
							echo $this->linkTo('workers', 'edit_group', $group, 'edit');
						} ?>
					</th>
				</tr>
				<tr>
					<th width="15%">Name</th>
					<th width="30%">Hostname</th>
					<th width="30%">Last seen</th>
					<th colspan="4"></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($group->Worker->orderBy('last_seen') as $worker): ?>
					<tr>
						<td><?= h($worker['name']); ?></td>
						<td><?= h($worker['hostname']); ?></td>
						<td><?= (new DateTime($worker['last_seen']))->format('d.m.Y H:i:s'); ?></td>
						<td colspan="4"></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endforeach; ?>
</div>