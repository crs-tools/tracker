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
				<?= $f->input('url','XML URL', '', ['class' => 'wide']); ?>
			</li>
			<li><?= $f->submit('Create new import'); ?></li>
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
				<th>User</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($previousImports as $import):
				$date = h((new DateTime($import['created']))->format('Y-m-d H:i:s')); ?>
				<tr>
					<td><code><?= h($import['url']); ?></code></td>
					<td><?= $this->linkTo('import', 'download', $import, $project, ['.xml'], (!empty($import['version']))? $import['version'] : ('<em>' . $date . '</em>'), (!empty($import['version']))? $import['version'] : $date); ?></td>
					<td><?= h($import['user_name']); ?></td>
					<td class="link right"><?= $this->linkTo('import', 'repeat', $import, $project, 'Repeat import…'); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
		<?php if (!isset($_GET['all'])): ?>
			<tfoot>
			<tr>
				<td colspan="4" class="link center more">
					<?= $this->linkTo('import', 'index', $project, ['?all'], 'Show all'); ?>
				</td>
			</tr>
			</tfoot>
		<?php endif; ?>
	</table>
<?php endif; ?>
