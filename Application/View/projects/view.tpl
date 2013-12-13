<div id="ticket-header">
	<h2 class="ticket"><span class="title"><?= $this->h($project['title']); ?></span></h2>

	<ul class="ticket-header-bar right horizontal">
		<li class="ticket-header-bar-background-left"></li>
		
		<?php if (User::isAllowed('projects', 'edit')): ?>
			<li class="action edit"><?php echo $this->linkTo('projects', 'edit', $project, '<span>edit</span>', 'Edit project'); ?></li>
		<?php endif; ?>
		
		<?php if (User::isAllowed('projects', 'delete')): ?>
			<li class="action delete"><?php echo $this->linkTo('projects', 'delete', $project, '<span>delete</span>', 'Delete project…'); ?></li>
		<?php endif; ?>
		<li class="ticket-header-bar-background-right"></li>
	</ul>
</div>

<h3 class="table">Properties</h3>

<?= $this->render('shared/properties.tpl'); ?>

<h3 class="table">Encoding profiles</h3>

<?= $f = $profilesForm() ?>
	<table class="default">
		<thead>
			<tr>
				<th width="20%">Name</th>
				<th>Version</th>
				<th width="10%">Priority</th><?php // TODO: link to edit profile ?>
				<th width="10%"></th>
				<th width="5%"></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($versions as $index => $version): ?>
			<tr>
				<td><?= $version->EncodingProfile['name']; ?></td>
				<?php // TODO: $f->selectForResource? ?>
				<td><?= $f->select(
					'EncodingProfileVersion[' . $index . '][encoding_profile_version_id]',
					null,
					$version->EncodingProfile->Versions->indexBy('id', 'encodingProfileVersionTitle')->toArray(),
					$version['id'],
					array('data-encoding-profile-version-id' => $version['id'], 'data-encoding-profile-index' => $index)
				); // TODO: show "x newer versions", maybe JS? ?></td>
				<td><?= $f->select(
					'EncodingProfileVersion[' . $index . '][priority]',
					null,
					array('0' => 'disabled', '0.5' => 'low', '0.75' => 'inferior', '1' => 'normal', '1.25' => 'superior', '1.5' => 'high'),
					$version['priority'],
					array('data-submit-on-change' => true)
				); ?></td>
				<td class="link right"><?= $this->linkTo('encodingprofiles', 'edit', $version->EncodingProfile, 'edit profile'); // TODO: ?version=XX ?></td>
				<td class="right"><?= $f->checkbox(
					'EncodingProfileVersion[' . $index . '][_destroy]',
					null,
					false,
					array('data-submit-on-change' => true, 'data-encoding-profile-destroy' => true)
				); ?></td>
			</tr>
		<?php endforeach; ?>
		<?php $f->register('EncodingProfileVersion[][encoding_profile_version_id]'); ?>
		<?php $f->register('EncodingProfileVersion[][_destroy]'); ?>
		<?php if ($versionsLeft->getRows() > 0): ?>
			<tr>
				<td></td>
				<td colspan="3">
					<select name="EncodingProfileVersion[][encoding_profile_version_id]" data-submit-on-change="1">
						<option value="">Add encoding profile</option>
						<?php $name = null;
						// TODO: $f->groupedSelect?
						foreach ($versionsLeft as $version):
							if ($name != $version['name']):
								if ($name !== null): ?>
									</optgroup>
								<?php endif;
								$name = $version['name']; ?>
								<optgroup label="<?= $this->h($name); ?>">
							<?php endif;?>
							<?= View::tag('option', array('value' => $version['id']), encodingProfileVersionTitle($version->toArray())); ?>
						<?php endforeach; ?>
						</optgroup>
					</select>
				</td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>
</form>

<h3 class="table">States</h3>

<h3 class="table">Worker groups</h3>

<?= $f = $workerGroupForm(); ?>
	<ul class="worker-groups">
		<?php foreach ($workerGroups as $index => $group): ?>
			<li>
				<?= $f->checkbox(
					'WorkerGroup[' . $index . '][worker_group_id]',
					$group['title'],
					isset($workerGroupAssignment[$group['id']]),
					['value' => $group['id']] +
						((isset($workerGroupAssignment[$group['id']]))?
							['data-worker-group-destroy' => 'WorkerGroup[' . $index . '][_destroy]'] :
							[]),
					false
				); ?>
			</li>
		<?php $f->register('WorkerGroup[' . $index . '][_destroy]');
		endforeach; ?>
	</ul>
	
	<?= $f->submit('Save assignment'); ?>
</form>