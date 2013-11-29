<?php $this->title('Settings | '); ?>

<h2>Settings</h2>

<?php echo $f = $form(); ?>
	<fieldset>
		<legend>Change Password</legend>
		<ul>
			<li><?php echo $f->password('current_password', 'Current password'); ?></li>
			<li><?php echo $f->password('password', 'New password'); ?></li>
			<li><?php echo $f->password('password_confirmation', 'Repeat password'); ?></li>
			<li><?php echo $f->submit('Change password') ?></li>
		</ul>
	</fieldset>
</form>