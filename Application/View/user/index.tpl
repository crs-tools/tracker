<?php $this->title('Users | '); ?>

<ul class="ticket-header-bar right horizontal table">
	<li class="ticket-header-bar-background-left"></li>
	<?php if ($this->User->isAllowed('user', 'create')): ?>
		<li class="action create"><?php echo $this->linkTo('user', 'create', '<span>create</span>', 'Create new user'); ?></li>
	<?php endif; ?>
	<li class="ticket-header-bar-background-right"></li>
</ul>

<div class="table">
	<h2>Users</h2>
	
	<table class="users">
		<thead>
			<tr>
				<th width="15%">Name</th>
				<th width="10%">Role</th>
				<th></th>
				<th width="3%">&nbsp;</th>
				<th width="5%">&nbsp;</th>
				<th width="5%">&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($users as $user): ?>
				<tr>
					<td><?php echo $user['name']; ?></td>
					<td><?php echo $user['role']; ?></td>
					<td></td>
					<td class="link hide right"><?php echo $this->linkTo('user', 'delete', $user, 'delete', array('class' => 'confirm-user-delete')); ?></td>
					<td class="link hide right">
						<?php if ($this->Acl->isAllowed($user['role'], 'user', 'act_as_substitute')) {
							echo $this->linkTo('user', 'substitute', $user, 'switch');
						} ?>
					</td>
					<td class="link hide right"><?php echo $this->linkTo('user', 'edit', $user, 'edit'); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<div class="table">
	<h2>Workers</h2>
	
	<table class="users">
		<thead>
			<tr>
				<th width="15%">Name</th>
				<th width="30%">Hostname</th>
				<th width="30%">Last seen</th>
				<th></th>
				<th width="5%">&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($workers as $worker): ?>
				<tr>
					<td><?php echo $worker['name']; ?></td>
					<td><?php echo $worker['hostname']; ?></td>
					<td><?php if (!empty($worker['last_seen'])) {
						echo Date::distanceInWords(Date::fromString($worker['last_seen']), null, true) . ' ago';
					} ?></td>
					<td></td>
					<td class="link hide right"><?php if ($this->User->isAllowed('user', 'delete')) {
						echo $this->linkTo('user', 'delete', $worker, 'unregister', array('class' => 'confirm-user-unregister'));
					} ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>