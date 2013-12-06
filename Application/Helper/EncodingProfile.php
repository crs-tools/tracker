<?php
	
	function encodingProfileVersionTitle(array $entry) {
		return 'r' . $entry['revision'] .
			(($entry['description'] !== null)? (' – ' . $entry['description']) : '') .
			' (' . (new Datetime($entry['created']))->format('d.m.Y H:i') . ')';
	}
	
?>