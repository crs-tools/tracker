<?= $this->render('projects/settings/_header'); ?>

<h3 class="table">Statistics</h3>
<table class="properties">
	<thead>
		<tr>
			<th colspan="2">Total Duration</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="key">Recording tickets</td>
			<td class="value">
				<?= h(round($duration / 3600, 1)); ?> h
			</td>
		</tr>
		<tr>
			<td class="key">Encoding tickets</td>
			<td class="value">
				<?= h(round($duration / 3600 * $encodingProfileCount, 1)); ?> h
			</td>
		</tr>
	</tbody>
</table>

<h3 class="table">Properties</h3>
<?= $this->render('shared/properties'); ?>