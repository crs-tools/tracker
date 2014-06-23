<?php // this is currently need for the queue as it's not embedded in a project
if (!isset($project)) {
	$project = $ticket->Project;
} ?>

<li data-id="<?= $ticket['id']; ?>"<?= ((!empty($ticket['parent_id']))? ' class="' . ((!empty($simulateTickets))? 'no-properties' : 'child') . '"' : ''); ?>>
	<a class="link" href="<?= $this->Request->getRootURL() . Router::reverse('tickets', 'view', $ticket->toArray() + ['project_slug' => $project['slug']]); ?>">
		
		<span class="vid<?= (($ticket['needs_attention'] and (empty($ticket['parent_id']) or !empty($simulateTickets)))? ' needs_attention' : ''); ?>">
		
		<?php if (empty($ticket['parent_id']) or isset($simulateTickets)) {
			echo h($ticket['fahrplan_id']);
		} else {
			echo  '&nbsp;';
		} ?>
		
		</span><span class="title"<?= (empty($ticket['parent_id']) and mb_strlen($ticket['title']) > 40)? ' aria-label="' . h($ticket['title']) . '" data-tooltip="true"' : ''; ?>>
		
		<?php if (!empty($ticket['encoding_profile_name'])) {
			echo $ticket['encoding_profile_name'];
		} elseif ($ticket['ticket_type'] == 'recording') {
			echo 'Recording';
		} elseif ($ticket['ticket_type'] == 'ingest') {
			echo 'Ingest';
		} else {
			echo h(str_shorten($ticket['title'], 40));
		} ?>
		
		</span><span class="state<?= (($ticket['failed'])? ' failed' : ''); ?>"><?= $ticket['ticket_state'] . (($ticket['failed'])? ' failed' : ''); ?>
		
		<?php if (!empty($ticket['parent_id']) and isset($ticket['priority_product'])) {
			echo '</span><span class="priority">';
			echo '<strong aria-label="Priority: ' . $ticket['priority_product'] . '" data-tooltip="true">' .
				round((((float) $ticket['priority_product']) - 1) * 100 + 1, 2) . '</strong>';
		} else {
			echo '</span><span class="day">';
		
			if (empty($ticket['parent_id']) and isset($ticket['fahrplan_day'])) {
				echo (!empty($ticket['fahrplan_day']))? ('Day ' . h($ticket['fahrplan_day'])) : '-'; 
			}
		} ?>
		
		</span><span class="start">
		
		<?php if (empty($ticket['parent_id']) and isset($ticket['fahrplan_start'])) {
			echo h($ticket['fahrplan_start']);
		} ?>
		
		</span><span class="room">
		
		<?php if (empty($ticket['parent_id']) and isset($ticket['fahrplan_room'])) {
			echo h($ticket['fahrplan_room']);
		} ?>
		
		</span><span class="view"></span>
	</a><span class="other">
		
		<?php if (!empty($ticket['handle_id']) and isset($ticket['handle_name'])): ?>
			<span class="assignee"><?= $this->linkTo(
				'tickets', 'index', $project,
				['?u=' . $ticket['handle_id']],
				$ticket['handle_name'],
				null,
				[
					'data-handle' => $ticket['handle_id']/*,
					'aria-label' => "Last seen: \nIdentitfication: 12392",
					'data-tooltip' => true*/
				]
			); ?></span>
		<?php endif; ?>
		
		<?php if ($ticket->isEligibleAction('cut') and User::isAllowed('tickets', 'cut')) {
			echo $this->linkTo('tickets', 'cut', $ticket, $project, '<span>cut</span>', 'Cut recording "' . $ticket['title'] . '"', ['class' => 'action']);
		} ?>
		
		<?php if ($ticket->isEligibleAction('check') and User::isAllowed('tickets', 'check')) {
			echo $this->linkTo('tickets', 'check', $ticket, $project, '<span>check</span>', 'Check "' . $ticket['title'] . '"', ['class' => 'action']);
		} ?>
		
		<?php if (User::isAllowed('tickets', 'edit')) {
			echo $this->linkTo('tickets', 'edit', $ticket, $project, '<span>edit</span>', 'Edit ticket "' . $ticket['title'] . '"', ['class' => 'edit']);
		} ?>
	</span>
	
	<?php if (empty($ticket['parent_id']) or isset($simulateTickets)) {
		echo $this->linkTo(
			'tickets', 'view', $ticket, $project,
			(isset($ticket['progress']))? ('<span class="progress-width" data-progress="' . round($ticket['progress']) . '">' . (($ticket['progress'] != '0')? '<span></span>' : '') . '</span>') : '',
			(isset($ticket['progress']))? (round($ticket['progress']) . '%') : '',
			['class' => 'progress']
		);
	} ?>
</li>