<?php $this->title('Login | '); ?>

<div id="user-login-wrapper">
<?= $f = $form(array('id' => 'user-login')); ?>
	<fieldset>
		<legend>Login</legend>
		<ul>
			<li><?= $f->input('user', 'User'); ?></li>
			<li><?= $f->password('password', 'Password'); ?></li>
			<li class="checkbox"><?= $f->checkbox('remember', 'Keep me logged in'); ?></li>
			<li><?= $f->submit('Login'); ?></li>
		</ul>
	</fieldset>
</form>
</div>