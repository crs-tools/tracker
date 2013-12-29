<?php if (!isset($action)) {
	$this->title($ticket['fahrplan_id'] . ' | ' . $ticket['title'] . ' | ');
} else {
	$this->title(mb_ucfirst($action) . ' lecture ' . $ticket['title'] . ' | ');
}

/*if (!$referer = Request::get('ref') or !$this->isValidReferer($referer, true)) {
	$referer = false;
}*/ ?>

<div id="ticket-header">
	<h2 class="ticket">
		<span class="fahrplan"><?php if ($ticket['fahrplan_id'] !== 0) {
			echo $ticket['fahrplan_id'];
		} else {
			if ($ticket['type_id'] == 3 and empty($ticket['parent_id'])) {
				echo '–';
			} else {
				echo $ticket['id'];
			}
		} ?></span>
		<span class="title" title="<?= $this->h($ticket['title']); ?>">
			<?php if (!empty($action)) {
				echo $this->h(mb_ucfirst($action)) . ' lecture ' . $this->linkTo('tickets', 'view', $ticket, $project, $this->h(str_shorten($ticket['title'], 37)));
			} else {
				echo $this->h(str_shorten($ticket['title'], 50));
			} ?>
		</span>
	</h2>
	
	<?php if (empty($action)): ?>
		<span class="date">
			last edited <?= /* echo Date::distanceInWords(new Date($ticket['modified']));  ?> ago<span>: <?=*/ (new DateTime($ticket['modified']))->format('D, M j Y, H:i'); ?><!--</span>-->
		</span>
	
		<div class="flags">
			<?php if ($ticket['failed']): ?>
				<span class="failed"><?= $ticket['ticket_state']; ?> failed</span>
			<?php else: ?>
				<span class="state"><?= $ticket['ticket_state']; ?></span>
			<?php endif; ?>
		
			<?php if ($ticket['needs_attention']): ?>
				<span class="needs_attention">needs attention</span>
			<?php endif; ?>
		
			<?php if (!empty($ticket['handle_id'])): ?>
				<span class="assignee">assigned to <?= $this->linkTo('tickets', 'index', $project, array('?u=' . $ticket['handle_id']), ($ticket['handle_id'] == User::getCurrent()['id']) ? 'you' : $ticket['handle_name']); ?></span>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	
	<?php if (User::isLoggedIn()): ?>
		<ul class="ticket-header-bar right horizontal">
			<li class="ticket-header-bar-background-left"></li>
			<?php if (User::isAllowed('tickets', 'cut') and $ticket->isEligibleAction('cut')): ?>
				<li class="action cut<?php echo (isset($action) and $action == 'cut')? ' current' : ''; ?>"><?php echo $this->linkTo('tickets', 'cut', $ticket, $project, '<span>cut</span>', 'Cut lecture…'); ?></li>
			<?php endif;
			if (User::isAllowed('tickets', 'check') and $ticket->isEligibleAction('check')): ?>
				<li class="action check<?php echo (isset($action) and $action == 'check')? ' current' : ''; ?>"><?php echo $this->linkTo('tickets', 'check', $ticket, $project, '<span>check</span>', 'Check ticket…'); ?></li>
			<?php endif;/*
			if (User::isAllowed('tickets', 'fix') and $this->State->isEligibleAction('fix', $ticket)): ?>
				<li class="action fix<?php echo ($action == 'fix')? ' current' : ''; ?>"><?php echo $this->linkTo('tickets', 'fix', $ticket + $project, '<span>fix</span>', 'Fix ticket…'); ?></li>
			<?php endif;
			if (User::isAllowed('tickets', 'handle') and $this->State->isEligibleAction('handle', $ticket)): ?>
				<li class="action handle<?php echo ($action == 'handle')? ' current' : ''; ?>"><?php echo $this->linkTo('tickets', 'handle', $ticket + $project, '<span>andle</span>', 'Handle ticket…'); ?></li>
			<?php endif;
			if (User::isAllowed('tickets', 'reset') and $this->State->isResetable($ticket)): ?>
				<li class="action reset"><?php echo $this->linkTo('tickets', 'reset', $ticket + $project, '<span>reset</span>', 'Reset encoding task', array('class' => 'confirm-ticket-reset')); ?></li>
			<?php endif;*/
			if (User::isAllowed('tickets', 'edit')): ?>
				<li class="action edit"><?php echo $this->linkTo('tickets', 'edit', $ticket, $project, /*(($referer)? array('?ref=' . $referer) : array()),*/ '<span>edit</span>', 'Edit ticket…'); ?></li>
			<?php endif;
			if (User::isAllowed('tickets', 'delete')): ?>
				<li class="action delete"><?php echo $this->linkTo('tickets', 'delete', $ticket, $project, '<span>delete</span>', 'Delete ticket', array('class' => 'confirm-ticket-delete')); ?></li>
			<?php endif; ?>
			<li class="ticket-header-bar-background-right"></li>
		</ul>
	<?php endif; ?>
</div>

<?php if (!empty($action)) {
	echo $this->render('tickets/view/action.tpl');
} ?>

<?php if (isset($parent)): ?>
	<h3>Parent</h3>
	<?= $this->render('tickets/list.tpl', array('tickets' => array($parent), 'referer' => false)); ?>
<?php endif; ?>

<?php if (isset($children) and $children->getRows() > 0): ?>
	<h3>Children</h3>
	<?= $this->render('tickets/list.tpl', array('tickets' => $children, 'referer' => false, 'simulateTickets' => true)); ?>
<?php endif; ?>

<h3 class="table">Properties</h3>

<?= $this->render('shared/properties.tpl'); ?>

<?php if (isset($parentProperties)) {
	echo $this->render('shared/properties.tpl', ['properties' => $parentProperties]);
}

if (isset($recordingProperties)) {
	echo $this->render('shared/properties.tpl', ['properties' => $recordingProperties]);
} ?>

<div id="timeline">
	<h3>Timeline</h3>
	<div class="line"></div>
	<ul class="clearfix">
		<?php if (empty($action) and User::isAllowed('tickets', 'comment')): ?>
			<li class="event left">
				<?php echo $f = $commentForm(); ?>
						<fieldset>
						<ul>
							<li><?php echo $f->textarea('text', null, null, array('class' => 'wide')); ?></li>
							<li>
								<?php echo $f->checkbox('needs_attention', 'Ticket needs attention', $ticket['needs_attention']);
								echo $f->submit('Comment'); ?>
							</li>
						</ul>
					</fieldset>
				</form>
			</li>
		<?php endif;
		
		$log = $log->getIterator();
		
		foreach ($comments as $comment) {
			while (strtotime($log->current()['created']) > strtotime($comment['created'])) {
				echo $this->render('tickets/view/log_entry.tpl', ['entry' => $log->current()]);
				$log->next();
			}
			
			echo $this->render('tickets/view/comment.tpl', ['comment' => $comment]);
		}
		
		while ($log->current()) {
			echo $this->render('tickets/view/log_entry.tpl', ['entry' => $log->current()]);
			$log->next();
		} ?>
	</ul>
</div>