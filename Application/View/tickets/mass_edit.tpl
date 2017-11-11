<?php $this->title('Edit multiple tickets | '); ?>

<?php echo $f = $this->form('tickets', 'edit', array('id' => implode(Model::indexByField($tickets,'id', 'id'), ',')) + $project, array('id' => 'ticket-edit', 'class' => 'mass')); ?> 
	<fieldset>
		<h2>Edit multiple tickets</h2>
		
		<?php $this->render('tickets/table.tpl', array('tickets' => $tickets)); ?>
	</fieldset>
	<fieldset>
		<legend></legend>
		<ul>
			<li><?php echo $f->select('priority', 'Priority', array('' => '', '0.5' => 'low', '0.75' => 'inferior', '1' => 'normal', '1.25' => 'superior', '1.5' => 'high')); ?>
			<li><?php echo $f->select('assignee', 'Assignee', array('' => '', '–' => '–') + $users, ''); ?></li>
			<li class="checkbox"><?php echo $f->checkbox('needs_attention', 'Tickets need attention') . $f->hidden('set_needs_attention', 'checked', array('disabled'));; ?></li>
			
			<li><?php echo $f->select('state', 'State', array('' => '') + $states); ?></li>
			<li class="checkbox"><?php echo $f->checkbox('failed', 'Current state failed') . $f->hidden('set_failed', 'checked', array('disabled')); ?></li>
		</ul>
	</fieldset>
	<fieldset>
		<ul>
			<li>
				<?php echo $f->submit('Save changes') . ' or ';
				echo $this->linkTo('tickets', 'index', $project, 'discard changes', array('class' => 'reset')); ?>
			</li>
		</ul>
	</fieldset>
</form>