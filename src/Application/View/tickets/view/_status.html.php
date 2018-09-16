<?php if (!$ticket['service_executable']) {
	return;
}

if ($ticket['encoding_profile_version_id'] !== null) {
	$projectEncodingProfile = $project
		->EncodingProfileVersion
		->except(['fields'])
		->select('tbl_project_encoding_profile.priority')
		->where([
			'id' => $ticket['encoding_profile_version_id']
		])
		->first();
}

if ($ticket->Parent['ticket_state'] !== 'staged') {
	$status = [
		'dependency',
		'Parent is ' . $ticket->Parent['ticket_state'] . ' (staged required).'
	];
// TODO: recording ticket < finalized (?)
} elseif (
	isset($projectEncodingProfile) and
	$projectEncodingProfile['priority'] === '0'
) {
	$status = [
		'disabled',
		'Encoding profile is disabled.'
	];
} elseif ($ticket['handle_id'] !== null) {
	$status = [
		'dependency',
		'Ticket is assigned to ' . $ticket['handle_name'] . '.'
	];
} elseif (
	isset($projectEncodingProfile) and
	$ticket->isDependeeTicketMissing() === true
) {
	$status = [
		'dependency',
		"Encoding profile dependency not satisfied\n" .
			'(encoding ticket for dependee is missing).'
	];
} elseif (
	isset($projectEncodingProfile) and
	($state = $ticket->getDependeeTicketState()) !== null and
	!$ticket->isDependeeTicketStateSatisfied()
) {
	$status = [
		'dependency',
		"Encoding profile dependency not satisfied\n" .
			'(supporting ticket is ' . $state . ').'
	];
} else {
	$status = [
		'queue',
		'Ticket is ' . (
			($ticket['ticket_state'] === 'scheduled')?
				'scheduled' : 'queued'
			) .
			((isset($projectEncodingProfile))?
				' (' .
				Ticket::$priorities[$projectEncodingProfile['priority']] .
				' priority)'
				: ''
			) . '.'
	];
}
// TODO: check worker group filter?
// TODO: all matching worker groups paused? ?>

<li class="service-status <?= h($status[0]); ?>">
	<span aria-label="<?= h($status[1]); ?>" data-tooltip="true"></span>
</li>