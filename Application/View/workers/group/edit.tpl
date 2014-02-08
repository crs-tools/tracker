<?php $this->title((isset($group))? ('Edit worker group ' . $group['title'] . ' | ') : 'Create new worker group | '); ?>

<?= $f = $form(); ?>
	<fieldset>
		<h2><?= (isset($group))? h('Edit worker group ' . $group['title']) : 'Create new worker group'; ?></h2>
		<ul>
			<li><?= $f->input('title', 'Title', $group['title']); ?></li>
		</ul>
	</fieldset>
	<?php if (isset($group)): ?>
		<fieldset>
			<legend>Authentication</legend>	
			<ul>
				<li><?= $f->input('token', 'Token', $group['token'], array('readonly' => true, 'class' => 'wide'), false); ?></li>
				<li><?= $f->input('secret', 'Secret', $group['secret'], array('readonly' => true, 'class' => 'wide'), false); ?></li>
				<li class="checkbox"><?= $f->checkbox('create_secret', 'Create new secret', false, array(), false); ?></li>
			</ul>
		</fieldset>
	<?php endif; ?>
	<fieldset>
		<ul	>
			<li><?php
				echo $f->submit((isset($group))? 'Save changes' : 'Create new worker group') . ' or ';
				echo $this->linkTo('workers', 'index', (isset($group))? 'discard changes' : 'discard worker group', array('class' => 'reset'));
			?></li>
		</ul>
	</fieldset>
</form>