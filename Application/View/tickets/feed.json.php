<?php $entries = [];

foreach ($log as $entry) {
	$entries[] = $this->render('tickets/feed/entry', ['entry' => $entry]);
}

return [
	'entries' => $entries,
	'progress' =>$this->render('tickets/feed/progress'),
	'actions' => $this->render('tickets/feed/actions')
]; ?>