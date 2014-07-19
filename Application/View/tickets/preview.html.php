<?php $this->title('Preview change of '.$tickets->getRows().' tickets | '); ?>

<div id="ticket-header">
	<h2 class="ticket"><span class="title">
		Preview change of <?=$tickets->getRows()?> tickets
	</span></h2>
	
	<ul class="ticket-header-bar right horizontal">
		<li class="ticket-header-bar-background-left"></li>
		<li class="action create"><?php echo $this->linkTo('tickets', 'create', $project, '<span>create</span>', 'Create new ticket…'); ?></li>
		
		<?php if (User::isAllowed('import', 'index')): ?>
			<li class="action import"><?php echo $this->linkTo('import', 'index', $project, '<span>import</span>'); ?></li>
		<?php endif; ?>
		<li class="ticket-header-bar-background-right"></li>
	</ul>
</div>

<?= $f = $form(array('id' => 'ticket-preview')); ?>
	<?php
		$has_changes = false;
		
		$has_title = !empty($form->getValue('title'));
		$has_priority = ('_' != $form->getValue('priority'));
		$has_comment = !empty($form->getValue('comment'));
		$has_handle = ('_' != $form->getValue('handle_id'));
		$has_needs_attention = $form->getValue('change_needs_attention');
		
		if($has_title || $has_priority || $has_handle || $has_needs_attention):
			$has_changes = true;
	?>
	<fieldset>
		<ul>
			<?php if($has_title): ?>
				<li>
					<?= $f->hidden('title'); ?>
					<label>Title</label>
					<span class="text"><?= h($form->getValue('title')) ?></span>
				</li>
			<?php endif ?>
			
			<?php if($has_priority): ?>
				<li>
					<?= $f->hidden('priority'); ?>
					<label>Priority</label>
					<span class="text"><?= h($priorities[$form->getValue('priority')]) ?></span>
				</li>
			<?php endif ?>
			
			<?php if($has_handle): ?>
				<li>
					<?= $f->hidden('handle_id'); ?>
					<label>Assignee</label>
					<span class="text"><?= h($users[$form->getValue('handle_id')]) ?></span>
				</li>
			<?php endif ?>
			
			<?php if($has_needs_attention): ?>
				<li>
					<?= $f->hidden('needs_attention'); ?>
					<?= $f->hidden('change_needs_attention'); ?>
					<label>Ticket needs attention</label>
					<span class="text"><?= $form->getValue('needs_attention') ? 'Yes' : 'No' ?></span>
				</li>
				<?php if($has_comment): ?>
					<li>
						<?= $f->hidden('comment'); ?>
						<label>Comment</label>
						<span class="text"><?= $form->getValue('comment') ?></span>
					</li>
				<?php endif ?>
			<?php endif ?>
		</ul>
	</fieldset>
	<?php
		endif;
		
		$has_ticket_state = ('_' != $form->getValue('ticket_state'));
		$has_failed = $form->getValue('change_failed');
		$has_comment = !empty($form->getValue('comment'));
		if($has_ticket_state || $has_failed):
			$has_changes = true;
	?>
	<fieldset>
		<legend>State</legend>
		<ul>
			<?php if($has_ticket_state): ?>
				<li>
					<?= $f->hidden('ticket_state'); ?>
					<label>State</label>
					<span class="text"><?= h($states[$form->getValue('ticket_state')]) ?></span>
				</li>
			<?php endif ?>
			
			<?php if($has_failed): ?>
				<li>
					<?= $f->hidden('failed'); ?>
					<?= $f->hidden('change_failed'); ?>
					<label>Current state failed</label>
					<span class="text"><?= $form->getValue('failed') ? 'Yes' : 'No' ?></span>
				</li>
				<?php if($has_comment && !$has_needs_attention): ?>
					<li>
						<?= $f->hidden('comment'); ?>
						<label>Comment</label>
						<span class="text"><?= $form->getValue('comment') ?></span>
					</li>
				<?php endif ?>
			<?php endif ?>
		</ul>
	</fieldset>
	<?php
		endif;
		
		$has_properties = false;
		if(is_array($form->getValue('properties'))) {
			foreach ($form->getValue('properties') as $idx => $property) {
				$has_properties |= !empty($property['value']);
			}
		}
		
		if($has_properties):
			$has_changes = true;
	?>
	<fieldset>
		<legend>Properties</legend>
		<ul>
			<?php foreach ($form->getValue('properties') as $idx => $property): ?>
				<?php if (!empty($property['value'])): ?>
					<li>
						<label><?= h($property['name']) ?></label>
						<span class="text"><?= h($property['value']) ?></span>
					</li>
					<?= $f->hidden('properties['.$idx.'][name]', $property['name']) ?>
					<?= $f->hidden('properties['.$idx.'][value]', $property['value']) ?>
				<?php endif ?>
			<?php endforeach ?>
		</ul>
	</fieldset>
	<?php
		endif;
	?>
	<fieldset>
		<ul>
			<?php if(!$has_changes): ?>
				<li>
					Nothing was selected to change.
				</li>
				<li>
					<?= $this->linkTo('tickets', 'index', array('project_slug' => $project['slug']), 'back to tickets', array('class' => 'reset')); ?>
				</li>
			<?php else: ?>
				<li>
					<?= $f->submit('Change '.$tickets->getRows().' tickets', ['name' => 'multisave']) ?>
				</li>
			<?php endif ?>
		</ul>
	</fieldset>
</form>
