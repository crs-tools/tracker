<?php $this->title((isset($group))? ('Edit worker group ' . $group['title'] . ' | ') : 'Create new worker group | '); ?>


<?php if (isset($group)): ?>
	<div id="ticket-header">
		<h2 class="ticket"><span><?= h('Edit worker group ' . $group['title']); ?></span></h2>
		
		<ul class="ticket-header-bar right horizontal">
			<li class="ticket-header-bar-background-left"></li>
		
			<?php if (User::isAllowed('workers', 'queue')): ?>
				<li class="action versions"><?= $this->linkTo('workers', 'queue', $group, '<span>queue</span>', 'Show worker group queue'); ?></li>
			<?php endif; ?>
			
			<li class="action edit current"><?= $this->linkTo('workers', 'edit_group', $group, '<span>edit</span>', 'Edit worker group…'); ?></li>
			
			<?php if (User::isAllowed('workers', 'delete')): ?>
				<li class="action delete"><?= $this->linkTo('workers', 'delete_group', $group, '<span>delete</span>', 'Delete worker group', ['data-dialog-confirm' => 'Are you sure you want to permanently delete this worker group?']); ?></li>
			<?php endif; ?>
		
			<li class="ticket-header-bar-background-right"></li>
		</ul>
	</div>
<?php endif; ?>

<?= $f = $form(); ?>
	<fieldset>
		<?php if (!isset($group)): ?>
			<h2>Create new worker group</h2>
		<?php endif; ?>
		
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