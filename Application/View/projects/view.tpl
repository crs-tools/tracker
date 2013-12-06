<div id="ticket-header">
	<h2 class="ticket"><span class="title"><?php echo $this->h($project['title']); ?></span></h2>

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

<table class="properties">
	<?php $root = null;
	
	foreach ($project->Properties as $property):
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

<h3 class="table">Encoding profiles</h3>

<?= $f = $profilesForm() ?>
	<table class="default">
		<thead>
			<tr>
				<th width="20%">Name</th>
				<th>Version</th>
				<th>Priority</th>
				<th width="5%">Delete</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($versions as $index => $version): ?>
			<tr>
				<td><?= $version->EncodingProfile['name']; ?></td>
				<td>
					<?= $f->select('EncodingProfileVersion[' . $index . '][encoding_profile_version_id]', null, $version->EncodingProfile->Versions->indexBy('id', 'encodingProfileVersionTitle')->toArray(), $version['id'], array('data-submit-on-change' => true)); ?>
				</td>
				<td><?= $f->select('EncodingProfileVersion[' . $index . '][priority]', null, array('0.5' => 'low', '0.75' => 'inferior', '1' => 'normal', '1.25' => 'superior', '1.5' => 'high'), $version['priority'], array('data-submit-on-change' => true)); ?></td>
				<td><?= $f->checkbox('EncodingProfileVersion[' . $index . '][_destroy]', null, false, array('data-submit-on-change' => true)); ?></td>
			</tr>
		<?php endforeach; ?>
			<tr>
				<td></td>
				<td colspan="3">
					<select name="EncodingProfileVersion[][encoding_profile_version_id]" data-submit-on-change="1">
						<option value="">Add encoding profile</option>
						<?php $name = null;
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
					<?php $f->register('EncodingProfileVersion[][encoding_profile_version_id]'); ?>
				</td>
			</tr>
		</tbody>
	</table>
</form>

<h3 class="table">States</h3>