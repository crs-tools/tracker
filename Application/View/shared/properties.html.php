<?php
	
	if ($properties instanceOf Model_Resource) {
		$properties = clone $properties;
		$properties->except(['indexBy']);
		
		if ($properties->getRows() < 1) {
			return;
		}
	} elseif (empty($properties)) {
		return;
	}
	
?>
<table class="properties">
	<?php $root = null;
	
	foreach ($properties as $property):
		if ($root != $property['root']):
			$root = $property['root']; ?>
			<thead>
				<tr>
					<th colspan="2"><?= h($root); ?></th>
				</tr>
			</thead>
		<?php endif; ?>
		<tbody>
			<tr>
				<td class="key"><?= h((strpos($property['name'], '.') !== false)? (mb_substr($property['name'], mb_strlen($root) + 1)) : $property['name']); ?></td>
				<td class="value">
					<?php if (mb_strlen($property['value']) > 80 and ($pos = mb_strpos($property['value'], ' ', 80)) !== false) {
						echo h(mb_substr($property['value'], 0, $pos + 1)) . '<span class="more">' . h(mb_substr($property['value'], $pos + 1)) . '</span>';
					} else {
						echo h($property['value']);
					} ?>
				</td>
			</tr>
		</tbody>
	<?php endforeach; ?>
</table>