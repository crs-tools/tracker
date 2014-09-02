<?php $this->title('Encoding profiles | '); ?>
<?= $this->render('projects/settings/_header'); ?>

<?= $f = $profilesForm() ?>
	<table class="default">
		<thead>
			<tr>
				<th width="20%">Name</th>
				<th>Version</th>
				<th width="10%">Priority</th>
				<th width="10%"></th>
				<th width="5%"></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($versions as $index => $version): ?>
			<tr>
				<td><?= $version->EncodingProfile['name']; ?></td>
				<?php // TODO: $f->selectForResource? ?>
				<td>
					<?= $f->select(
						'versions[' . $index . '][1]',
						null,
						$version->EncodingProfile->Versions->indexBy('id', 'encodingProfileVersionTitle')->toArray(),
						$version['id'],
						['data-encoding-profile-version-id' => $version['id'], 'data-encoding-profile-index' => $index]
					) . // TODO: show "x newer versions", maybe JS?
					$f->hidden('versions[' . $index . '][0]', $version['id']); ?>
				</td>
				<td>
					<?= $f->select(
						'priority[' . $version['id'] . ']',
						null,
						['0' => 'disabled', '0.5' => 'low', '0.75' => 'inferior', '1' => 'normal', '1.25' => 'superior', '1.5' => 'high'],
						$version['priority'],
						['data-submit-on-change' => true]
					); ?>
				</td>
				<td class="link right"><?= $this->linkTo('encodingprofiles', 'edit', $version->EncodingProfile, 'edit profile'); // TODO: ?version=XX ?></td>
				<td class="right"><?= $f->button(
					'remove',
					null,
					'remove profile',
					['value' => $version['id'], 'class' => 'link', 'data-dialog-confirm' => 'Are you sure you want to remove this encoding profile and delete all related encoding tickets?']
				); ?></td>
			</tr>
		<?php endforeach; ?>
		
		<?php if ($versionsLeft->getRows() > 0): ?>
			<tr>
				<td></td>
				<td colspan="3">
					<?php $f->register('add'); ?>
					<select name="add" data-submit-on-change="1">
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
							<?= View::tag('option', ['value' => $version['id']], encodingProfileVersionTitle($version->toArray())); ?>
						<?php endforeach; ?>
						</optgroup>
					</select>
				</td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>
</form>