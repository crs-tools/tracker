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
					<li class="action delete"><?= $this->linkTo('user', 'delete', $user, '<span>delete</span>', 'Delete user', array('class' => 'confirm-user-delete')); ?></li>
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
				<?= $f->select('role', 'Role', array('restricteduser' => 'restricted user', 'user' => 'user', 'superuser' => 'superuser', 'admin' => 'admin'), $user['role']); ?>
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
		<ul>
			<li>
				<?php echo $f->submit((!empty($user))? 'Save user' : 'Create user') . ' or ';
				echo $this->linkTo('user', 'index', 'discard changes', array('class' => 'reset')); ?>
			</li>
		</ul>
	</fieldset>
</form>