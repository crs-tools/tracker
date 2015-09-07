<?php $this->title('Settings | '); ?>
<?= $this->render('projects/settings/_header'); ?>

<h3 class="table">Statistics</h3>
<table class="properties">
	<thead>
		<tr>
			<th colspan="2">Recording Duration</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="key">Staging tickets</td>
			<td class="value">
				<?= h(formatDuration($duration['staging'])); ?>
			</td>
		</tr>
		<tr>
			<td class="key">Staged tickets</td>
			<td class="value">
				<?= h(formatDuration($duration['staged'])); ?>
			</td>
		</tr>
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