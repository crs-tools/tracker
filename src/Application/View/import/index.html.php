<?php $this->title('Import | '); ?>

<?= $this->render('import/_header', ['title' => 'Import tickets']); ?>

<?php if ($unfinishedImport !== null): ?>
	<?= $f = $continueForm(); ?>
		<fieldset>
			<legend>Continue import</legend>
			<ul>
				<li class="warning"></li>
				<li>
					<label></label>
					<p>
						<strong>You have an unfinished import created <?= timeAgo($unfinishedImport['created']); ?>.</strong>
					</p>
				</li>
				<li><?= $f->input('url','XML URL', $unfinishedImport['url'], ['readonly' => true, 'class' => 'wide']); ?></li>
				<li><?= $f->submit('Cancel', ['name' => 'cancel']) . $f->submit('Continue'); ?></li>
			</ul>
		</fieldset>
	</form>
<?php endif; ?>

<?= $f = $form(['id' => 'ticket-import']); ?>
	<fieldset>
		<legend>New import</legend>
		<ul>
			<li>
				<?= $f->input('url','XML URL', '', ['class' => 'wide', 'disabled' => $project['read_only']]); ?>
			</li>
			<li>
				<?= $f->select(
					'auth_type',
					'Authentication',
					[
						'' => 'No Authentication',
						'basic' => 'Basic HTTP Authentication',
						'header_authentication' => 'Authentication Header'
						'header_authorization' => 'Authorization Header'
					],
					'',
					['data-import-auth-type-value']
				); ?>
			</li>
			<li><?= $f->input('auth_user', 'Username', '', ['data-import-auth-type' => 'basic']); ?></li>
			<li><?= $f->password('auth_password', 'Password', ['data-import-auth-type' => 'basic']); ?></li>
			<li><?= $f->input('auth_header_authentication', 'Authentication Header', '', ['class' => 'wide', 'data-import-auth-type' => 'header_authentication']); ?></li>
			<li><?= $f->input('auth_header_authorization', 'Authorization Header', '', ['class' => 'wide', 'data-import-auth-type' => 'header_authorization']); ?></li>
			<li><?= $f->submit('Create new import', ['disabled' => $project['read_only']]); ?></li>
		</ul>
	</fieldset>
</form>

<?php if ($previousImports->getRows() > 0): ?>
	<h3>Previous imports</h3>
	<table class="default">
		<thead>
			<tr>
				<th>URL</th>
				<th>Version</th>
				<th>Imported</th>
				<th>User</th>
				<?php if (!$project['read_only']): ?>
					<th></th>
				<?php endif; ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach($previousImports as $import):
				$date = h((new DateTime($import['created']))->format('Y-m-d H:i:s'));
				$url = parse_url($import['url']); ?>
				<tr>
					<td>
						<a href="<?= h($import['url']); ?>" rel="nofollow" aria-label="<?= h($import['url']); ?>" data-tooltip="true">
							<?= ($url !== false and isset($url['host']) and isset($url['path'])) ? h($url['host'] . str_shorten($url['path'], 35 - mb_strlen($url['host']), 2, '…', '/')) : $import['url']; ?>
						</a>
					</td>
					<td><?= $this->linkTo(
						'import', 'download', $import, $project, ['.xml'],
						(!empty($import['version']))? $import['version'] : ('<em>' . $date . '</em>'),
						(!empty($import['version']))? $import['version'] : $date
					); ?></td>
					<td><?= timeAgo($import['created'], ''); ?></td>
					<td><?= h($import['user_name']); ?></td>
					<?php if (!$project['read_only']): ?>
						<td class="link right">
							<?= $this->linkTo('import', 'repeat', $import, $project, 'Repeat import…'); ?>
						</td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
		<?php if (!isset($_GET['all'])): ?>
			<tfoot>
			<tr>
				<td colspan="5" class="link center more">
					<?= $this->linkTo('import', 'index', $project, ['?all'], 'Show all'); ?>
				</td>
			</tr>
			</tfoot>
		<?php endif; ?>
	</table>
<?php endif; ?>
