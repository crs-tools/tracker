<?php $this->title('Settings | '); ?>

<h2>Settings</h2>

<?= $f = $form(); ?>
	<fieldset>
		<legend>Change Password</legend>
		<ul>
			<li><?= $f->password('current_password', 'Current password'); ?></li>
			<li><?= $f->password('password', 'New password'); ?></li>
			<li><?= $f->password('password_confirmation', 'Repeat password'); ?></li>
			<li><?= $f->submit('Change password') ?></li>
		</ul>
	</fieldset>
</form>