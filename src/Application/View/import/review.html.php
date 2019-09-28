<?= $this->render('import/_header', ['title' => 'Import: review changes']); $index = 0; ?>

<?= $f = $applyForm(['id' => 'ticket-import-list']); ?>
	<?php if (!empty($diff['new'])): ?>
		<h3 class="table">New tickets</h3>
		<ul class="tickets">
			<?php foreach ($diff['new'] as $ticket): ?>
				<li>
					<?= $f->checkbox('selected[+' . $ticket['fahrplan_id'] . ']', null, true, ['class' => 'ticket-search-edit-select']); ?>
					<a class="link">
						<span class="vid"><?= h($ticket['fahrplan_id']); ?></span>
						<span class="title"><?= h(str_truncate($ticket['title'], 45, '…')); ?></span>
					</a>
					<span class="other">
					</span>
					<a class="progress">
					</a>
				</li>
				
				<?php if (!empty($ticket['Properties'])): ?>
					<li class="table">
						<table class="diff">
							<?php foreach ($ticket['Properties'] as $property): ?>
								<tr>
									<th><?= h($property['name']); ?></th>
									<td><code><ins><?= h($property['value']); ?></ins></code></td>
								</tr>
							<?php endforeach; ?>
						</table>
					</li>
				<?php endif; ?>
			<?php $index++; endforeach; ?>
		</ul>
	<?php endif; ?>
	
	<?php if (!empty($diff['changed'])): ?>
		<h3 class="table">Updated tickets</h3>
		<ul class="tickets">
			<?php foreach ($diff['changed'] as $id => $ticket): ?>
				<li>
					<?= $f->checkbox('selected[~' . $ticket['id'] . ']', null, true, ['class' => 'ticket-search-edit-select']); ?>
					<a class="link" href="<?= $this->Request->getRootURL() . Router::reverse('tickets', 'view', $ticket->toArray() + ['project_slug' => $project['slug']]); ?>">
						<span class="vid"><?= h($currentState[$ticket['id']]['fahrplan_id']); ?></span>
						<span class="title"><?= h(str_truncate((!empty($ticket['title']))? $ticket['title'] : $currentState[$ticket['id']]['title'], 45, '…')); ?></span>
						<span class="view"><span></span></span>
					</a>
					<span class="other">
					</span>
					<a class="progress" href="<?= $this->Request->getRootURL() . Router::reverse('tickets', 'view', $ticket->toArray() + ['project_slug' => $project['slug']]); ?>">
					</a>
				</li>
				
				<li class="table">
					<table class="diff">
						<?php if (!empty($ticket['Properties'])):
							foreach ($ticket['Properties'] as $property): ?>
								<tr>
									<th>
										<?php if (!empty($property['_destroy'])): ?>
											<del><?= h($property['name']); ?></del>
										<?php else: ?>
											<?= h($property['name']); ?>
										<?php endif; ?>
									</th>
									<td>
										<code>
											<?php if (!isset($property['_previous'])):
												if (!empty($property['value'])): ?>
													<ins><?= h($property['value']); ?></ins>
												<?php else: ?>
													&nbsp;
												<?php endif;
											elseif (!empty($property['_destroy'])): ?>
												<del><?= h($property['_previous']); ?></del>
											<?php else: ?>
												<del><?= h($property['_previous']); ?></del>
												</code><code>
												<ins><?= h(str_replace(["\r", "\n"], ['\r', '\n'], $property['value'])); ?></ins>
											<?php endif; ?>
										</code>
									</td>
								</tr>
							<?php endforeach;
						endif; ?>
						<?php if ($ticket['title'] !== null and $ticket['title'] !== $currentState[$ticket['id']]['title']): ?>
								<th><em>Fahrplan.Title</em></th>
								<td>
									<code><del><?= h($currentState[$ticket['id']]['title']); ?></del></code>
									<code><ins><?= h($ticket['title']); ?></ins></code>
								</td>
							</tr>
						<?php endif; ?>
					</table>
				</li>
			<?php $index++; endforeach; ?>
		</ul>
	<?php endif; ?>
	
	<?php if (!empty($diff['deleted'])): ?>
		<h3 class="table">Removed tickets</h3>
		<ul class="tickets">
			<?php foreach ($diff['deleted'] as $id => $ticket): ?>
				<li>
					<?= $f->checkbox('selected[~' . $ticket['id'] . ']', null, false, ['class' => 'ticket-search-edit-select']); ?>
					<a class="link" href="<?= $this->Request->getRootURL() . Router::reverse('tickets', 'view', $ticket->toArray() + ['project_slug' => $project['slug']]); ?>">
						<span class="vid"><?= h($currentState[$ticket['id']]['fahrplan_id']); ?></span>
						<span class="title"><?= h(str_truncate((!empty($ticket['title']))? $ticket['title'] : $currentState[$ticket['id']]['title'], 45, '…')); ?></span>
						<span class="view"><span></span></span>
					</a>
					<span class="other">
					</span>
					<a class="progress" href="<?= $this->Request->getRootURL() . Router::reverse('tickets', 'view', $ticket->toArray() + ['project_slug' => $project['slug']]); ?>">
					</a>
				</li>
			<?php $index++; endforeach; ?>
		</ul>
	<?php endif; ?>
	
	<fieldset>
		<ul>
			<li><?= $f->submit('Back', ['disabled' => true]) . $f->submit('Finish import'); ?></li>
		</ul>
	</fieldset>
</form>
