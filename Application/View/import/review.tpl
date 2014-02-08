<?php $this->title('Check for Fahrplan updates | '); ?>

<div id="ticket-header">
	<h2 class="ticket"><span class="title">Check for Fahrplan updates</span></h2>
	
	<ul class="ticket-header-bar right horizontal">
		<li class="ticket-header-bar-background-left"></li>
		
		<?php if (User::isAllowed('tickets', 'create')): ?>
			<li class="action create"><?= $this->linkTo('tickets', 'create', $project, '<span>create</span>', 'Create new ticket…'); ?></li>
		<?php endif; ?>
		
		<li class="action current import"><?= $this->linkTo('import', 'index', $project, '<span>import</span>'); ?></li>
		
		<?php if (User::isAllowed('export', 'index')): ?>
			<li class="action export"><?= $this->linkTo('export', 'index', $project, '<span>export</span>'); ?></li>
		<?php endif; ?>
		
		<li class="ticket-header-bar-background-right"></li>
	</ul>
</div>

<?= $f = $applyForm(array('id' => 'ticket-import-list')); ?>
	<?php if (!empty($tickets['new'])): ?>
		<fieldset>
			<legend>Tickets to add</legend>
			<br />
			<ul class="tickets">
				<?php foreach ($tickets['new'] as $id => $ticket): ?>
					<li>
						<a class="link" title="<?php foreach($ticket as $key => $value) { echo h($key . ': ' . $value) . "\n"; } ?>">
							<span class="vid"><?= $id; ?></span>
							<span class="title"><?= h(str_truncate($ticket['Fahrplan.Title'], 45, '…')); ?></span>
						</a>
						<span class="other">
							<span class="checkbox"><?= $f->checkbox('tickets[new][' . $id . ']', null, true, [], false); ?></span>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</fieldset>
	<?php endif; ?>
	<?php if (!empty($tickets['changed'])): ?>
		<fieldset>
			<legend>Tickets to update</legend>
			<br />
			<ul class="tickets">
				<?php foreach ($tickets['changed'] as $id => $ticket): ?>
					<li>
						<a class="link">
							<span class="vid"><?= $id; ?></span>
							<span class="title"><?= h(str_truncate($ticket['properties']['Fahrplan.Title'], 45, '…')); ?></span>
						</a>
						<span class="other">
							<span class="checkbox"><?= $f->checkbox('tickets[change][' . $id . ']', null, true, [], false); ?></span>
						</span>
					</li>
					
					<table class="diff">
						<?php foreach($ticket['diff'] as $key => $value): ?>
							<tr>
								<th width="10%">
									<?php if ($value['fahrplan'] === null): ?>
										<del><?= h($key); ?></del>
									<?php else: ?>
										<?= h($key); ?>
									<?php endif; ?>
								</th>
								<td>
									<code>
										<?php if ($value['database'] === null): ?>
											<ins><?= h($value['fahrplan']); ?></ins>
										<?php elseif ($value['fahrplan'] === null): ?>
											<del><?= h($value['database']); ?></del>
										<?php else: ?>
											<del><?= h($value['database']); ?></del>
											</code><code>
											<ins><?= h($value['fahrplan']); ?></ins>
										<?php endif; ?>
									</code><br />
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				<?php endforeach; ?>
			</ul>
		</fieldset>
	<?php endif; ?>
	<?php if (!empty($tickets['deleted'])): ?>
		<fieldset>
			<legend>Tickets to delete</legend>
			<br />
			<ul class="tickets">
				<?php foreach ($tickets['deleted'] as $id => $ticket): ?>
					<li>
						<a class="link">
							<span class="vid"><?= $id; ?></span>
							<span class="title"><?= h(str_truncate($ticket['title'], 45, '…')); ?></span>
						</a>
						<span class="other">
							<span class="checkbox"><?= $f->checkbox('tickets[delete][' . $id . ']', null, true, [], false); ?></span>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</fieldset>
	<?php endif; ?>
	
	<fieldset>
		<ul>
			<li><?= $f->submit('Apply checked changes'); ?></li>
		</ul>
	</fieldset>
</form>