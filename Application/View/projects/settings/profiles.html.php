<?php $this->title('Encoding profiles | '); ?>
<?= $this->render('projects/settings/_header'); ?>

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
								<optgroup label="<?= h($name); ?>">
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