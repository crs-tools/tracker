<?php if (empty($action)) {
	$this->title((($ticket['type_id'] == 3)? '' : ((($ticket['fahrplan_id'] === 0)? $ticket['id'] : $ticket['fahrplan_id']) . ' | ')) . $ticket['title'] . ' | ');
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
				echo mb_ucfirst($action) . ' lecture ' . $this->linkTo('tickets', 'view', $ticket, $project, $this->h(str_shorten($ticket['title'], 37)));
			} else {
				echo $this->h(str_shorten($ticket['title'], 50));
			} ?>
		</span>
	</h2>
	
	<?php if (empty($action)): ?>
		<span class="date">
			last edited <?php /* echo Date::distanceInWords(new Date($ticket['modified'])); */ ?> ago<span>: <?= (new DateTime($ticket['modified']))->format('D, M j Y, H:i'); ?></span>
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
				<span class="assignee">assigned to <?= $this->linkTo('tickets', 'index', $project, array('?u=' . $ticket['handle_id']), ($ticket['handle_id'] == User::getCurrent()['id']) ? 'you' : $ticket['user_name']); ?></span>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	
	<?php if (User::isLoggedIn()): ?>
		<ul class="ticket-header-bar right horizontal">
			<li class="ticket-header-bar-background-left"></li>
			<?php /*if (User::isAllowed('tickets', 'cut') and $this->State->isEligibleAction('cut', $ticket)): ?>
				<li class="action mark<?php echo ($action == 'cut')? ' current' : ''; ?>"><?php echo $this->linkTo('tickets', 'cut', $ticket + $project, '<span>cut</span>', 'Mark lecture…'); ?></li>
			<?php endif;
			if (User::isAllowed('tickets', 'check') and $this->State->isEligibleAction('check', $ticket)): ?>
				<li class="action check<?php echo ($action == 'check')? ' current' : ''; ?>"><?php echo $this->linkTo('tickets', 'check', $ticket + $project, '<span>check</span>', 'Check ticket…'); ?></li>
			<?php endif;
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
				<li class="action edit"><?php echo $this->linkTo('tickets', 'edit', $ticket, $project, (($referer)? array('?ref=' . $referer) : array()), '<span>edit</span>', 'Edit ticket…'); ?></li>
			<?php endif;
			if (User::isAllowed('tickets', 'delete')): ?>
				<li class="action delete"><?php echo $this->linkTo('tickets', 'delete', $ticket, $project, '<span>delete</span>', 'Delete ticket', array('class' => 'confirm-ticket-delete')); ?></li>
			<?php endif; ?>
			<li class="ticket-header-bar-background-right"></li>
		</ul>
	<?php endif; ?>
</div>

<?php /* if (!empty($action)) {
	echo $this->render('tickets/view/action.tpl');
} */ ?>

<?php if ($parent !== null): ?>
	<h3>Parent</h3>
	<?= $this->render('tickets/list.tpl', array('tickets' => array($parent), 'referer' => false)); ?>
<?php endif; ?>

<?php if ($children->getRows() > 0): ?>
	<h3>Children</h3>
	<?= $this->render('tickets/list.tpl', array('tickets' => $children, 'referer' => false, 'simulateTickets' => true)); ?>
<?php endif; ?>

<h3 class="table">Properties</h3>

<?= $this->render('shared/properties.tpl'); ?>

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
		
		foreach ($comments as $comment): ?>
			<li class="event comment left">
				<p><?php echo nl2br($this->h($comment['comment'])); ?></p>
				<strong>– <?php echo $this->h($comment['user_name']); ?></strong>
				<?php if (User::isAllowed('tickets', 'delete_comment', $comment['id'], $comment['user_id'])) {
					echo $this->linkTo('tickets', 'delete_comment', $comment, $project, 'delete');
				} ?>
				<span class="date"><?php echo (new DateTime($comment['created']))->format('d.m.Y H:i'); ?></span>
			</li>
		<?php endforeach;
		
		
		/*if (!empty($timeline)):
			foreach ($timeline as $entry):
				if ($entry['event'] != 'Comment.Add'): ?>
					<li class="event <?php echo $entry['type'] . ' ' . (($entry['type'] == 'comment')? 'left' : 'right'); ?>">
						<?php switch ($entry['type']) {
							case 'comment': ?>
								<p><?php echo nl2br(Filter::specialChars($entry['comment'])); ?></p>
								<strong<?php echo (!empty($entry['origin_user_name']))? ' class="origin"' : ''; ?>>– <?php echo (empty($entry['origin_user_name']))? $entry['user_name'] : mb_substr($entry['origin_user_name'], 0, 20); // TODO: add symbol ?></strong>
								<?php if (User::isAllowed('tickets', 'delete_comment', $entry['id'], $entry['user_id'])) {
									echo $this->linkTo('tickets', 'delete_comment', $entry + $project, 'delete');
								} ?>
								<span class="date"><?php echo Date::distanceInWords(new Date($entry['created'])); ?> ago<span>: <?php echo Date::fromString($entry['created'], null, 'D, M j Y, H:i') ?></span></span>
								<?php break;
							case 'log': ?>
									<span class="title"><?php if (isset($entry['message'])) {
										$toState = (isset($entry['to_state_name']))? $entry['to_state_name'] : 'unknown state';
										$fromState = (isset($entry['from_state_name']))? $entry['from_state_name'] : 'unknown state';
										
										// TODO: add {duration}
										echo str_replace(
											array('{to_state}', '{to_State}', '{from_state}', '{from_State}'),
											array($toState, mb_ucfirst($toState), $fromState, mb_ucfirst($fromState)),
											$entry['message']
										);
									} else {
										echo '<em>' . $entry['event'] . '</em>';
									} ?></span>
									<?php if (!empty($entry['comment'])): ?>
										<code>
											<?php $lines = array_filter(explode("\n", Filter::specialChars($entry['comment'])));
											
											echo nl2br(implode('<br />', array_slice($lines, 0, 3)));
											
											if (count($lines) > 3) {
												echo ' ' . $this->linkTo('tickets', 'log', $ticket + array('entry' => $entry['id']) + $project + array('.txt'), 'more');
											} ?>
										</code>
									<?php endif; ?>
									
									<span class="date"><?php echo Date::distanceInWords(new Date($entry['created'])); ?> ago<span>: <?php echo Date::fromString($entry['created'], null, 'D, M j Y, H:i') ?></span></span><span class="description"> by <?php echo $entry['user_name']; ?></span>
								<?php break;
						} ?>
						<span class="spine"></span>
	    			</li>
				<?php endif;
			endforeach;
		endif; */ ?>
	</ul>
</div>