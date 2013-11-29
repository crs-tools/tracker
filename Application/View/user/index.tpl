<?php $this->title('Manage users | '); ?>

<?php if (User::isAllowed('user', 'create')): ?>
	<ul class="ticket-header-bar right horizontal table">
		<li class="ticket-header-bar-background-left"></li>
			<li class="action create"><?php echo $this->linkTo('user', 'create', '<span>create</span>', 'Create new user'); ?></li>
		<li class="ticket-header-bar-background-right"></li>
	</ul>
<?php endif; ?>

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
					<td class="link hide right"><?php if (User::isAllowed('user', 'delete') and !$user->isCurrent()) {
						echo $this->linkTo('user', 'delete', $user, 'delete', array('class' => 'confirm-user-delete'));
					} ?></td>
					<td class="link hide right">
						<?php /*if (AccessControl::isAllowed($user['role'], 'user', 'act_as_substitute')) {
							echo $this->linkTo('user', 'substitute', $user, 'switch');
						}*/ ?>
					</td>
					<td class="link hide right"><?php if (User::isAllowed('user', 'edit')) {
						echo $this->linkTo('user', 'edit', $user, 'edit');
					} ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>