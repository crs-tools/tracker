<?php $this->title('Settings | '); ?>

<h2>Settings</h2>

<?php echo $f = $this->form('user', 'settings'); ?>
	<fieldset>
		<legend>Change Password</legend>
		<ul>
			<li><?php echo $f->password('password', 'Current password'); ?></li>
			<li><?php echo $f->password('new_password', 'New password'); ?></li>
			<li><?php echo $f->password('new_password_confirmation', 'Repeat password'); ?></li>
			<li><?php echo $f->submit('Change password') ?></li>
		</ul>
	</fieldset>
</form>