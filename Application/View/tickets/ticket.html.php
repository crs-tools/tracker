<?php // this is currently need for the queue as it's not embedded in a project
if (!isset($project)) {
	$project = $ticket->Project;
}

$attributes = [
	'data-id' => $ticket['id'],
	'data-fahrplan-id' => $ticket['fahrplan_id'],
	'data-ticket-type' => $ticket['ticket_type']
];

if (empty($ticket['parent_id'])) {
	$attributes['data-title'] = $ticket['title'];
} else {
	$attributes['class'] = ((!empty($simulateTickets))? 'no-properties' : 'child');
}

if (!empty($filtered) and (!empty($filtered[$ticket['id']]) or !empty($filtered[$ticket['parent_id']]))) {
	if (isset($attributes['class'])) {
		$attributes['class'] .= ' filtered';
	} else {
		$attributes['class'] = 'filtered';
	}
	
	$filters = (!empty($filtered[$ticket['id']]))?
		$filtered[$ticket['id']] : $filtered[$ticket['parent_id']];
	
	$attributes['title'] = "Ticket does not match the following properties from the worker group filter:\n";
	
	foreach ($filters as $filter) {
		$attributes['title'] .= "\n" . $filter['property_key'] . ' = ' . $filter['property_value'];
	}
} ?>

<?= View::tag('li', $attributes, false); ?>
	<a class="link" href="<?= $this->Request->getRootURL() . Router::reverse('tickets', 'view', $ticket->toArray() + ['project_slug' => $project['slug']]); ?>">
		<span class="vid<?= (((empty($ticket['parent_id'])) and $ticket->needsAttention())? ' needs_attention' : ''); ?>" title="<?= h($ticket['fahrplan_id']); ?>">
		
		<?php if (empty($ticket['parent_id']) or isset($simulateTickets)) {
			echo h($ticket['fahrplan_id']);
		} else {
			echo  '&nbsp;';
		} ?>
		
		</span><span class="title"<?= (empty($ticket['parent_id']) and mb_strlen($ticket['title']) > 39)? ' aria-label="' . h($ticket['title']) . '" data-tooltip="true"' : ''; ?>>
		
		<?php $suffix = $ticket->getTitleSuffix();
		echo h(str_shorten(($suffix !== '')? $suffix : $ticket['title'], 39)); ?>
		
		</span><span class="state<?= (($ticket['failed'])? ' failed' : ''); ?>"><?= $ticket['ticket_state'] . (($ticket['failed'])? ' failed' : ''); ?>
		
		<?php if (!empty($ticket['parent_id']) and isset($ticket['priority_product'])) {
			echo '</span><span class="priority">';
			echo '<strong aria-label="Priority: ' . $ticket['priority_product'] . '" data-tooltip="true">' .
				round((((float) $ticket['priority_product']) - 1) * 100 + 1, 2) . '</strong>';
		} else {
			echo '</span><span class="day">';
		
			if (empty($ticket['parent_id']) and isset($ticket['fahrplan_day'])) {
				echo (!empty($ticket['fahrplan_day']) or $ticket['fahrplan_day'] === '0')? ('Day ' . h($ticket['fahrplan_day'])) : '-';
			}
		} ?>
		
		</span><span class="start">
		
		<?php if (empty($ticket['parent_id']) and !empty($ticket['fahrplan_datetime'])) {
			echo h((new DateTime($ticket['fahrplan_datetime']))->format('H:i'));
		} else {
			echo '&nbsp';
		} ?>
		
		</span><span class="room"<?php if (empty($ticket['parent_id']) and isset($ticket['fahrplan_room'])) {
			echo ' title="' . h($ticket['fahrplan_room']) . '">';
			echo h($ticket['fahrplan_room']);
		} else {
			echo '>';
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
			echo $this->linkTo('tickets', 'cut', $ticket, $project, '<span>cut</span>', 'Cut recording ticket…', ['class' => 'action']);
		} ?>
		
		<?php if ($ticket->isEligibleAction('check') and User::isAllowed('tickets', 'check')) {
			echo $this->linkTo('tickets', 'check', $ticket, $project, '<span>check</span>', 'Check ticket…', ['class' => 'action']);
		} ?>
		
		<?php if (User::isAllowed('tickets', 'edit')) {
			echo $this->linkTo('tickets', 'edit', $ticket, $project, '<span>edit</span>', 'Edit ticket…', ['class' => 'edit']);
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
