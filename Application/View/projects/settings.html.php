<?php $this->title('Settings | '); ?>
<?= $this->render('projects/settings/_header'); ?>

<h3 class="table">Statistics</h3>
<table class="properties">
	<thead>
		<tr>
			<th colspan="2">Total Duration (staged)</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="key">Recording tickets</td>
			<td class="value">
				<?= h(formatDuration($duration)); ?>
			</td>
		</tr>
		<tr>
			<td class="key">Encoding tickets</td>
			<td class="value">
				<?= h(formatDuration($duration * $encodingProfileCount)); ?>
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