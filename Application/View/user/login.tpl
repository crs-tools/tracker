<?php $this->title('Login | '); ?>

<div id="user-login-wrapper">
<?php echo $f = $form(array('id' => 'user-login')); ?>
	<fieldset>
		<legend>Login</legend>
		<ul>
			<li><?php echo $f->input('user', 'User'); ?></li>
			<li><?php echo $f->password('password', 'Password'); ?></li>
			<?php /*<li class="checkbox"><?php echo $f->checkbox('remember', 'Keep me logged in'); ?></li>*/ ?>
			<li><?php echo $f->submit('Login'); ?></li>
		</ul>
	</fieldset>
</form>
</div>