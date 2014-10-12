<?= $this->render('import/_header', ['title' => 'Import: review changes']); $index = 0; ?>

<?= $f = $applyForm(['id' => 'ticket-import-list']); ?>
	<?php if (!empty($diff['new'])): ?>
		<h3 class="table">New tickets</h3>
		<ul class="tickets">
			<?php foreach ($diff['new'] as $ticket): ?>
				<li>
					<a class="link">
						<span class="vid"><?= h($ticket['fahrplan_id']); ?></span>
						<span class="title"><?= h(str_truncate($ticket['title'], 45, '…')); ?></span>
					</a>
					<span class="other">
					</span>
					<?= $this->render('import/_inputs', ['f' => $f, 'ticket' => $ticket, 'index' => $index]); ?>
				</li>
				
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
			<?php $index++; endforeach; ?>
		</ul>
	<?php endif; ?>
	
	<?php if (!empty($diff['changed'])): ?>
		<h3 class="table">Updated tickets</h3>
		<ul class="tickets">
			<?php foreach ($diff['changed'] as $id => $ticket): ?>
				<li>
					<a class="link" href="<?= $this->Request->getRootURL() . Router::reverse('tickets', 'view', $ticket->toArray() + ['project_slug' => $project['slug']]); ?>">
						<span class="vid"><?= h($currentState[$ticket['id']]['fahrplan_id']); ?></span>
						<span class="title"><?= h(str_truncate((isset($ticket['title']))? $ticket['title'] : $currentState[$ticket['id']]['title'], 45, '…')); ?></span>
						<span class="view">&nbsp;</span>
					</a>
					<span class="other">
					</span>
					<?= $this->render('import/_inputs', ['f' => $f, 'ticket' => $ticket, 'index' => $index]); ?>
				</li>
				
				<li class="table">
					<table class="diff">
						<?php foreach ($ticket['Properties'] as $property): ?>
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
						<?php endforeach; ?>
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
					<a class="link" href="<?= $this->Request->getRootURL() . Router::reverse('tickets', 'view', $ticket->toArray() + ['project_slug' => $project['slug']]); ?>">
						<span class="vid"><?= h($currentState[$ticket['id']]['fahrplan_id']); ?></span>
						<span class="title"><?= h(str_truncate((isset($ticket['title']))? $ticket['title'] : $currentState[$ticket['id']]['title'], 45, '…')); ?></span>
						<span class="view">&nbsp;</span>
					</a>
					<span class="other">
					</span>
					<?= $this->render('import/_inputs', ['f' => $f, 'ticket' => $ticket, 'index' => $index]); ?>
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