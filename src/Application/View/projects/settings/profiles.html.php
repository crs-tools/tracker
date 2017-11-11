<?php $this->title('Encoding profiles | '); ?>
<?= $this->render('projects/settings/_header'); ?>

<?= $f = $profilesForm(['disabled' => $project['read_only']]) ?>
	<table class="default profiles">
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
				<td class="name"><?= $version->EncodingProfile['name']; ?></td>
				<?php // TODO: $f->selectForResource? ?>
				<td class="version">
					<?= $f->select(
						'versions[' . $index . '][1]',
						null,
						$version->EncodingProfile->Versions->indexBy('id', 'encodingProfileVersionTitle')->toArray(),
						$version['id'],
						['data-encoding-profile-version-id' => $version['id'], 'data-encoding-profile-index' => $index],
						false
					) . // TODO: show "x newer versions", maybe JS?
					$f->hidden('versions[' . $index . '][0]', $version['id']); ?>
				</td>
				<td class="priority">
					<?= $f->select(
						'priority[' . $version['id'] . ']',
						null,
						['0' => 'disabled'] + Ticket::$priorities,
						$version['priority'],
						['data-submit-on-change' => true],
						false
					); ?>
				</td>
				<td class="link right edit"><?= $this->linkTo('encodingprofiles', 'edit', $version->EncodingProfile, ['version' => $version['id']], 'edit profile'); ?></td>
				<td class="right destroy"><?= $f->button(
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
					<?php if (!$project['read_only']) {
						$f->register('add');
					} ?>
					<select name="add" data-submit-on-change="1"<?= ($project['read_only'])? ' disabled="disabled"' : '' ?>>
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