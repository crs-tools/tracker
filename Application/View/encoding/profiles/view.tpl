<ul class="ticket-header-bar right horizontal table">
	<li class="ticket-header-bar-background-left"></li>
	<?php /* TODO: versions */ ?>
	<?php if (User::isAllowed('encodingprofiles', 'edit')): ?>
		<li class="action edit"><?php echo $this->linkTo('encodingprofiles', 'edit', $profile, '<span>edit</span>', 'Edit encoding profile…'); ?></li>
	<?php endif; ?>
	<?php if (User::isAllowed('encodingprofiles', 'delete')): ?>
		<li class="action delete"><?php echo $this->linkTo('encodingprofiles', 'delete', $profile, '<span>delete</span>', 'Delete encoding profile'); ?></li>
	<?php endif; ?>
	<li class="ticket-header-bar-background-right"></li>
</ul>

<div class="table" id="encoding-profile-versions">
	<h2><?php echo $this->h($profile['name']); ?></h2>
	
	<?= $f = $form(); ?>
		<table class="double-stripe">
			<thead>
				<tr>
					<th width="7%"></th>
					<th width="3%"></th>
					<th width="18%">Created</th>
					<th>Description</th>
					<th></th>
					<th width="20%"></th>
			</thead>
			<tbody>
				<?php foreach ($versions as $version): ?>
					<tr>
						<td><?= $f->radio('version_a', null, $version['id']) . $f->radio('version_b', null, $version['id']); ?>
						<td><strong>r<?= $version['revision']; ?></strong></td>
						<td><?= (new Datetime($version['created']))->format('d.m.Y H:i'); ?></td>
						<td><?= $this->h($version['description']); ?></td>
						<td></td>
						<td class="link hide right encoding-profile-version-show"></td>
					</tr>
					<tr class="table-ignore encoding-profile-version">
						<td colspan="6">
							<textarea readonly data-has-editor="true"><?php echo $this->h($version['xml_template']); ?></textarea>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		
		</table>
		
		<p>
			<?= $f->submit('Compare versions'); ?>
		</p>
	</form>
</div>

<?php $this->render('shared/editor.tpl'); ?>