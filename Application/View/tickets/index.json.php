<?php $index = [];

foreach ($tickets as $ticket) {
	$index[] = $this->render('tickets/ticket', [
		'ticket' => $ticket
	]);
}

return $index; ?>