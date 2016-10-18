<?php $this->title('Settings | '); ?>
<?= $this->render('projects/settings/_header'); ?>

<h3 class="table">Statistics</h3>
<table class="properties">
	<thead>
		<tr>
			<th></th>
			<th>Count</th>
			<th>Recording Duration</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="key">All tickets</td>
			<td class="value"><?= h(
				((isset($stats['count']['staging']))? $stats['count']['staging'] : 0) +
				((isset($stats['count']['staged']))? $stats['count']['staged'] : 0) +
				((isset($stats['count']['closed']))? $stats['count']['closed'] : 0)
			); ?></td>
			<td class="disabled"></td>
		</td>
		<tr>
			<td class="key">Staging tickets</td>
			<td class="value">
				<?php if (isset($stats['count']['staging'])) {
					echo h($stats['count']['staging']);
				} else {
					echo '–';
				} ?>
			</td>
			<td class="value">
				<?php if (isset($stats['duration']['staging'])) {
					echo h(formatDuration($stats['duration']['staging']));
				} else {
					echo '–';
				} ?>
			</td>
		</tr>
		<tr>
			<td class="key">Staged tickets</td>
			<td class="value">
				<?php if (isset($stats['count']['staged'])) {
					echo h($stats['count']['staged']);
				} else {
					echo '–';
				} ?>
			</td>
			<td class="value">
				<?php if (isset($stats['duration']['staged'])) {
					echo h(formatDuration($stats['duration']['staged']));
				} else {
					echo '–';
				} ?>
			</td>
		</tr>
		<tr>
			<td class="key">Closed tickets</td>
			<td class="value">
				<?php if (isset($stats['count']['closed'])) {
					echo h($stats['count']['closed']);
				} else {
					echo '–';
				} ?>
			</td>
			<td class="disabled"></td>
		</td>
	</tbody>
</table>

<h3 class="table">Properties</h3>
<?= $this->render('shared/properties'); ?>

<h3 class="table">Other</h3>
<table class="properties">
	<thead>
		<tr>
			<th colspan="2">Project</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="key">Id</td>
			<td class="value">
				<?= h($project['id']); ?>
			</td>
		</tr>
	</tbody>
</table>