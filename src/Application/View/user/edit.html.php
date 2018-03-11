<?php if (!empty($user)) {
	$this->title('Edit user ' . $user['name'] . ' | ');
} else {
	$this->title('Add user | ');
} ?>


<?php if (!empty($user)): ?>
	<div id="ticket-header">
		<h2 class="ticket">
			<span class="title">Edit user <?= $user['name']; ?></span>
		</h2>

		<?php if (User::isAllowed('user', 'delete')): ?>
			<ul class="ticket-header-bar right horizontal">
				<li class="ticket-header-bar-background-left"></li>
					<li class="action delete"><?= $this->linkTo('user', 'delete', $user, '<span>delete</span>', 'Delete user', ['data-dialog-confirm' => 'Are you sure you want to permanently delete this user?']); ?></li>
				<li class="ticket-header-bar-background-right"></li>
			</ul>
		<?php endif; ?>
	</div>
<?php endif; ?>

<?= $f = $form(); ?>
	<fieldset>
		<?php if (empty($user)): ?>
			<h2>Add User</h2>
		<?php endif; ?>
		<ul>
			<li>
				<?= $f->input('name', 'Name', $user['name']); ?>
			</li>
			<li>
				<?php if ($user['role'] === 'engineer'): ?>
					<?= $f->select('role', 'Role', [
						'engineer' => 'engineer'
					], $user['role'], ['disabled' => true]); ?>
				<?php else: ?>
					<?= $f->select('role', 'Role', [
						'read only' => 'read only',
						'restricted' => 'restricted',
						'user' => 'user',
						'restricted superuser' => 'restricted superuser',
						'superuser' => 'superuser',
						'admin' => 'admin'
					], $user['role'], ['data-user-edit-role' => '']); ?>
				<?php endif; ?>
			</li>
		</ul>
	</fieldset>
	<fieldset>
		<legend>Password</legend>
		<ul>
			<?php if (!empty($user)): ?>
				<li>
					<?= $f->password('user_password', 'Your password'); ?>
					<span class="description">To change the users password first enter your password for confirmation.</span>
				</li>
			<?php endif; ?>
			<li><?= $f->password('password', (!empty($user))? 'New user password' : 'Password'); ?></li>
		</ul>
	</fieldset>
	<fieldset data-project-access-restrictions>
		<legend>Project access</legend>
		<ul>
			<li class="checkbox">
				<?= $f->checkbox('restrict_project_access', 'Restrict access to the following projects', (!empty($user))? $user['restrict_project_access'] : true, ['data-enable-restrictions' => '']); ?>
				<span class="description">Access restrictions are only available for non admin users.</span>
			</li>
			<?php if (!empty($userProjects)): ?>
				<?php foreach ($userProjects as $index => $project): ?>
					<li>
						<label></label>
						<?= $f->hidden('Project[' . $index . '][project_id]', $project['id'], ['data-project-index' => $index, 'data-project-destroy' => 'Project[' . $index . '][_destroy]']); ?>
						<span class="project-restrictions-project" data-project-delete>
							<?= h($projects[$project['id']]); ?>
						</span>
					</li>
				<?php endforeach; ?>
			<?php endif; ?>
			
			<li><?= $f->select('', '', ['' => ''] + $projects->toArray(), '', ['data-project-select' => '']); ?></li>
			<?php $f->register('Project[][project_id]'); ?>
			<?php $f->register('Project[][_destroy]'); ?>
		</ul>
	</fieldset>
	<fieldset>
		<ul>
			<li>
				<?php echo $f->submit((!empty($user))? 'Save user' : 'Create user') . ' or ';
				echo $this->linkTo('user', 'index', 'discard changes', array('class' => 'reset')); ?>
			</li>
		</ul>
	</fieldset>
</form>
