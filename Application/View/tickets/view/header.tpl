<div id="ticket-header">
	<h2 class="ticket">
		<span class="fahrplan"><?= $this->h($ticket['fahrplan_id']); ?></span>
		<span class="title"<?= (empty($titlePrefix) and mb_strlen($ticket['title']) > 50)? ' aria-label="' . $this->h($ticket['title']) . '" data-tooltip="true"' : ''; ?>>
			<?php if (!empty($titlePrefix)) {
				echo $titlePrefix . $this->linkTo('tickets', 'view', $ticket, $project, $this->h(str_shorten($ticket['title'], 37)), null, array('aria-label' => $ticket['title'], 'data-tooltip' => true));
			} else {
				echo $this->h(str_shorten($ticket['title'], 50));
			} ?>
		</span>
	</h2>
	
	<?php if (isset($showDetails) and $showDetails): ?>
		<span class="date">
			last edited <?= timeAgo($ticket['modified']); ?>
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
			<?php foreach (['cut', 'check', 'edit', 'delete'] as $action):
				if (!User::isAllowed('tickets', $action) or !$ticket->isEligibleAction($action)) {
					continue;
				} ?>
				
				<li class="action <?= $action . ((isset($currentAction) and $currentAction == $action)? ' current' : ''); ?>">
					<?php switch ($action) {
						case 'cut':
						case 'check':
						case 'edit':
							echo $this->linkTo('tickets', $action, $ticket, $project, '<span>' . $action . '</span>', ucfirst($action) . '…');
							break;
						case 'reset':
							echo $this->linkTo('tickets', 'reset', $ticket + $project, '<span>reset</span>', 'Reset encoding task', array('class' => 'confirm-ticket-reset'));
							break;
						case 'delete':
							echo $this->linkTo('tickets', 'delete', $ticket, $project, '<span>delete</span>', 'Delete ticket', array('class' => 'confirm-ticket-delete'));
							break;
					} ?>
				</li>
			<?php endforeach; ?>
			<li class="ticket-header-bar-background-right"></li>
		</ul>
	<?php endif; ?>
</div>