<table class="properties">
	<?php $root = null;
	
	foreach ($properties as $property):
		if ($root != $property['root']):
			$root = $property['root']; ?>
			<thead>
				<tr>
					<th colspan="2"><?php echo $root; ?></th>
				</tr>
			</thead>
		<?php endif; ?>
		<tbody>
			<tr>
				<td class="key"><?php echo (strpos($property['name'], '.') !== false)? (mb_substr($property['name'], mb_strlen($root) + 1)) : $property['name']; ?></td>
				<td class="value">
					<?php if (mb_strlen($property['value']) > 80 and ($pos = mb_strpos($property['value'], ' ', 80)) !== false) {
						echo mb_substr($property['value'], 0, $pos + 1) . '<span class="more">' . mb_substr($property['value'], $pos + 1) . '</span>';
					} else {
						echo $property['value'];
					} ?>
				</td>
			</tr>
		</tbody>
	<?php endforeach; ?>
</table>