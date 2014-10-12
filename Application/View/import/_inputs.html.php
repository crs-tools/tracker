<?php foreach (['id', 'fahrplan_id', 'title', '_destroy'] as $field) {
	if (!isset($ticket[$field])) {
		continue;
	}
	
	echo $f->hiddenEncoded('tickets[' . $index . '][' . $field . ']', $ticket[$field]);
}

$p = 0;

foreach ($ticket['Properties'] as $property) {
	echo $f->hidden('tickets[' . $index . '][Properties][' . $p . '][name]', $property['name']);
	
	if (isset($property['value'])) {
		echo $f->hiddenEncoded('tickets[' . $index . '][Properties][' . $p . '][value]', $property['value']);
	}
	
	if (isset($property['_destroy'])) {
		echo $f->hidden('tickets[' . $index . '][Properties][' . $p . '][_destroy]', $property['_destroy']);
	}
	
	$p++;
} ?>