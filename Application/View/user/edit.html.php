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
				<?= $f->select('role', 'Role', [
					'read only' => 'read only',
					'restricted' => 'restricted',
					'user' => 'user',
					'superuser' => 'superuser',
					'admin' => 'admin'
				], $user['role']); ?>
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
	<fieldset>
		<legend>Project access</legend>
		<ul>
			<li class="checkbox"><?= $f->checkbox('restrict_project_access', 'Restrict access to the following projects', $user['restrict_project_access']); ?></li>
			<?php foreach ($userProjects as $index => $project): ?>
				<li><?= $f->select('Project[' . $index . '][project_id]', '', $projects->toArray(), $project['id']); ?></li>
			<?php endforeach; ?>
			<li>
				<li><label></label><p><a href="#">Add restriction</a><span class="description">A user without project restrictions has access to all projects.</span></p></li>
			</li>
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
