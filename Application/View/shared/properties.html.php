<?php if (empty($properties)) {
		return;
} ?>
<table class="properties">
	<?php $root = null;
	
	foreach ($properties as $property):
		$parts = explode('.', $property['name']);
		
		if ($root !== $parts[0]):
			$root = $parts[0]; ?>
			<thead>
				<tr>
					<th colspan="2"><?= h($root); ?></th>
				</tr>
			</thead>
		<?php endif; ?>
		<tbody>
			<tr>
				<td class="key"><span class="root"><?= h($root . '.'); ?></span><?php
					$key = h((count($parts) > 1)? implode('.', array_slice($parts, 1)) : $property['name']);
					echo (!empty($property['virtual']))? ('<em title="Virtual property">' . $key . '</em>') : $key;
				?></td>
				<td class="value">
					<?php if (mb_strlen($property['value']) > 80 and ($pos = mb_strpos($property['value'], ' ', 80)) !== false) {
						echo nl2br(h(mb_substr($property['value'], 0, $pos + 1))) . '<span class="more">' . nl2br(h(mb_substr($property['value'], $pos + 1))) . '</span>';
					} else {
						echo nl2br(h($property['value']));
					} ?>
				</td>
			</tr>
		</tbody>
	<?php endforeach; ?>
	<?php if (!empty($merged) and !isset($_GET['merged'])): ?>
		<tfoot>
			<tr>
				<td colspan="2" class="link center more">
					<?= $this->linkTo('tickets', 'view', $ticket, $project, ['?merged'], 'Show merged properties'); ?>
				</td>
			</tr>
		</tfoot>
	<?php endif; ?>
</table>