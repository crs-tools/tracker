<?php $this->title('Check for Fahrplan updates | '); ?>

<div id="ticket-header">
	<h2 class="ticket"><span class="title">Check for Fahrplan updates</span></h2>

	<ul class="ticket-header-bar right horizontal">
		<li class="ticket-header-bar-background-left"></li>
			
		<?php if ($this->User->isAllowed('tickets', 'create')): ?>
			<li class="action create"><?php echo $this->linkTo('tickets', 'create', $project, '<span>create</span>', 'Create new ticket…'); ?></li>
		<?php endif; ?>
			
		<li class="action current import"><?php echo $this->linkTo('import', 'index', $project, '<span>import</span>'); ?></li>

		<?php if ($this->User->isAllowed('export', 'index')): ?>
			<li class="action export"><?php echo $this->linkTo('export', 'index', $project, '<span>export</span>'); ?></li>
		<?php endif; ?>
		
		<li class="ticket-header-bar-background-right"></li>
	</ul>
</div>

<?php echo $f = $this->form('import', 'apply', $project, array('id' => 'ticket-import-list')); ?>
	<?php if (!empty($tickets['added'])): ?>
		<fieldset>
			<legend>Tickets to add</legend>
			<br />
			<ul class="tickets">
				<?php foreach ($tickets['added'] as $id => $ticket): ?>
					<li>
						<a class="link" title="<?php foreach($ticket as $key => $value) { echo $key . ': ' . $value . "\n"; } ?>">
							<span class="vid"><?php echo $id; ?></span>
							<span class="title"><?php echo Text::truncate($ticket['Fahrplan.Title'], 45, '…'); ?></span>
						</a>
						<span class="other">
							<span class="checkbox"><?php echo $f->checkbox('ticket_add[' . $id . ']', false, null, array('checked' => 'checked')); ?></span>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</fieldset>
	<?php endif; ?>
	<?php if (!empty($tickets['updated'])): ?>
		<fieldset>
			<legend>Tickets to update</legend>
			<br />
			<ul class="tickets">
				<?php foreach ($tickets['updated'] as $id => $ticket): ?>
					<li>
						<a class="link">
							<span class="vid"><?php echo $id; ?></span>
							<span class="title"><?php echo Text::truncate($ticket['title'], 45, '…'); ?></span>
						</a>
						<span class="other">
							<span class="checkbox"><?php echo $f->checkbox('ticket_update[' . $id . ']', false, null, array('checked' => 'checked')); ?></span>
						</span>
					</li>
					
					<table class="diff">
						<tr>
							<?php foreach($ticket['properties'] as $key => $value):
								if (!$value['equals']): ?>
									<th><?php echo $key; ?></th>
									<td>
										<code>
											<?php if ($value['database'] == NULL): ?>
												<ins><?php echo $value['fahrplan']; ?></ins>
											<?php elseif ($value['fahrplan'] == NULL): ?>
												<del><?php echo $value['database']; ?></del>
											<?php else: ?>
												<del><?php echo $value['database']; ?></del>
												</code><code>
												<ins><?php echo $value['database']; ?></ins>
											<?php endif; ?>
										</code><br />
									</td>
								<?php endif;
							endforeach; ?>
						</tr>
					</table>
					
					<?php
					foreach($ticket['properties'] as $key => $value) {
						if (!$value['equals']) {
							
		
							echo "\n";
						}
					} ?>
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
							<span class="vid"><?php echo $id; ?></span>
							<span class="title"><?php echo Text::truncate($ticket['title'], 45, '…'); ?></span>
						</a>
						<span class="other">
							<span class="checkbox"><?php echo $f->checkbox('ticket_delete[' . $id . ']', false, null, array('checked' => 'checked')); ?></span>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</fieldset>
	<?php endif; ?>
	
	<fieldset>
		<ul>
			<li><?php echo $f->submit('Apply checked changes'); ?></li>
		</ul>
	</fieldset>
</form>