<table class="properties">
	<?php $root = null;
	
	if ($properties instanceOf Model_Resource) {
		$properties = clone $properties;
		$properties->except(['indexBy']);
	}
	
	foreach ($properties as $property):
		if ($root != $property['root']):
			$root = $property['root']; ?>
			<thead>
				<tr>
					<th colspan="2"><?= $this->h($root); ?></th>
				</tr>
			</thead>
		<?php endif; ?>
		<tbody>
			<tr>
				<td class="key"><?= $this->h((strpos($property['name'], '.') !== false)? (mb_substr($property['name'], mb_strlen($root) + 1)) : $property['name']); ?></td>
				<td class="value">
					<?php if (mb_strlen($property['value']) > 80 and ($pos = mb_strpos($property['value'], ' ', 80)) !== false) {
						echo $this->h(mb_substr($property['value'], 0, $pos + 1)) . '<span class="more">' . $this->h(mb_substr($property['value'], $pos + 1)) . '</span>';
					} else {
						echo $this->h($property['value']);
					} ?>
				</td>
			</tr>
		</tbody>
	<?php endforeach; ?>
</table>