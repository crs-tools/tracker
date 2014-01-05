<?php
	
	function encodingProfileVersionTitle(array $entry) {
		return 'r' . $entry['revision'] .
			(($entry['description'] !== null)? (' – ' . $entry['description']) : '') .
			' (' . (new Datetime($entry['created']))->format('d.m.Y H:i') . ')';
	}
	
	function encodingProfileTitle(array $entry) {
		return $entry['name'] . ' (r' . $entry['revision'] . ')';
	}
	
?>