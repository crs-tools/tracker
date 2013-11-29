<?php if (empty($action)) {
	$this->title((($ticket['type_id'] == 3)? '' : ((($ticket['fahrplan_id'] === 0)? $ticket['id'] : $ticket['fahrplan_id']) . ' | ')) . $ticket['title'] . ' | ');
} else {
	$this->title(mb_ucfirst($action) . ' lecture ' . $ticket['title'] . ' | ');
}

if (!$referer = Request::get('ref') or !$this->isValidReferer($referer, true)) {
	$referer = false;
} ?>

<div id="ticket-header">
	<h2 class="ticket">
		<span class="fahrplan"><?php if ($ticket['fahrplan_id'] !== 0) {
			echo $ticket['fahrplan_id'];
		} else {
			if ($ticket['type_id'] == 3 and empty($ticket['parent_id'])) {
				echo '–';
			} else {
				echo $ticket['id'];
			}
		} ?></span>
		<span class="title" title="<?php echo Filter::specialChars($ticket['title']); ?>">
			<?php if (!empty($action)) {
				echo mb_ucfirst($action) . ' lecture ' . $this->linkTo('tickets', 'view', $ticket + $project, Filter::specialChars(Text::shorten($ticket['title'], 37)));
			} else {
				echo Filter::specialChars(Text::shorten($ticket['title'], 50));
			} ?>
		</span>
	</h2>
	
	<?php if (empty($action)): ?>
		<span class="date">
			last edited <?php echo Date::distanceInWords(new Date($ticket['modified'])); ?> ago<span>: <?php echo Date::fromString($ticket['modified'], null, 'D, M j Y, H:i') ?></span>
		</span>
	
		<div class="flags">
			<?php if ($ticket['failed']): ?>
				<span class="failed"><?php echo $ticket['state_name']; ?> failed</span>
			<?php else: ?>
				<span class="state"><?php echo $ticket['state_name']; ?></span>
			<?php endif; ?>
		
			<?php if ($ticket['needs_attention']): ?>
				<span class="needs_attention">needs attention</span>
			<?php endif; ?>
		
			<?php if (!empty($ticket['user_id'])): ?>
				<span class="assignee">assigned to <?php echo $this->linkTo('tickets', 'index', $project + array('?u=' . $ticket['user_id']), ($ticket['user_id'] == $this->User->get('id')) ? 'you' : $ticket['user_name']); ?></span>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	
	<?php if ($this->User->isLoggedIn()): ?>
		<ul class="ticket-header-bar right horizontal">
			<li class="ticket-header-bar-background-left"></li>
			<?php if (User::isAllowed('tickets', 'cut') and $this->State->isEligibleAction('cut', $ticket)): ?>
				<li class="action mark<?php echo ($action == 'cut')? ' current' : ''; ?>"><?php echo $this->linkTo('tickets', 'cut', $ticket + $project, '<span>cut</span>', 'Mark lecture…'); ?></li>
			<?php endif;
			if (User::isAllowed('tickets', 'check') and $this->State->isEligibleAction('check', $ticket)): ?>
				<li class="action check<?php echo ($action == 'check')? ' current' : ''; ?>"><?php echo $this->linkTo('tickets', 'check', $ticket + $project, '<span>check</span>', 'Check ticket…'); ?></li>
			<?php endif;
			if (User::isAllowed('tickets', 'fix') and $this->State->isEligibleAction('fix', $ticket)): ?>
				<li class="action fix<?php echo ($action == 'fix')? ' current' : ''; ?>"><?php echo $this->linkTo('tickets', 'fix', $ticket + $project, '<span>fix</span>', 'Fix ticket…'); ?></li>
			<?php endif;
			if (User::isAllowed('tickets', 'handle') and $this->State->isEligibleAction('handle', $ticket)): ?>
				<li class="action handle<?php echo ($action == 'handle')? ' current' : ''; ?>"><?php echo $this->linkTo('tickets', 'handle', $ticket + $project, '<span>andle</span>', 'Handle ticket…'); ?></li>
			<?php endif;
			if (User::isAllowed('tickets', 'reset') and $this->State->isResetable($ticket)): ?>
				<li class="action reset"><?php echo $this->linkTo('tickets', 'reset', $ticket + $project, '<span>reset</span>', 'Reset encoding task', array('class' => 'confirm-ticket-reset')); ?></li>
			<?php endif;
			if (User::isAllowed('tickets', 'edit')): ?>
				<li class="action edit"><?php echo $this->linkTo('tickets', 'edit', $ticket + $project + (($referer)? array('?ref=' . $referer) : array()), '<span>edit</span>', 'Edit ticket…'); ?></li>
			<?php endif;
			if (User::isAllowed('tickets', 'delete')): ?>
				<li class="action delete"><?php echo $this->linkTo('tickets', 'delete', $ticket + $project, '<span>delete</span>', 'Delete ticket', array('class' => 'confirm-ticket-delete')); ?></li>
			<?php endif; ?>
			<li class="ticket-header-bar-background-right"></li>
		</ul>
	<?php endif; ?>
</div>

<?php if (!empty($action)): ?>
	<?php echo $f = $this->form('tickets', $action, $ticket + $project + (($referer)? array('?ref=' . $referer) : array()), array('id' => 'ticket-action')); ?>
		<fieldset>
			<ul>
				<?php if ($ticket['user_id'] != $this->User->get('id')): ?>
					<li class="warning"></li>
					<li>
						<label></label>
						<p>
							<?php if (empty($ticket['user_id'])) {
								echo 'This ticket is abandoned';
							} else {
								echo $this->linkTo('tickets', 'index', $project + array('?u=' . $ticket['user_id']), $ticket['user_name']) . ' is ' . $state;
							}
						
							echo ' since ' . Date::distanceInWords(Date::fromString($ticket['modified'])); ?>.
						</p>
					</li>
					<li><?php echo $f->submit('Appropriate ticket', array('name' => 'appropriate')) . ' or ' . $this->linkTo('tickets', 'index', $project, 'leave ticket untouched'); ?></li></ul>
				<?php else: ?>
					<?php if ($action == 'cut'): ?>
						<?php if (!empty($project['languages'])): ?>
							<li>
								<?php echo $f->select('language', 'Language', array('') + $project['languages']); ?>
								<span class="description">
									<?php if (!empty($properties['Fahrplan.Language'])): ?>
										Fahrplan language is set as <em><?php echo $properties['Fahrplan.Language']; ?></em>.
									<?php else: ?>
										Fahrplan language not set.
									<?php endif; ?>
								</span>
							</li>
						<?php endif; ?>
						<?php if (isset($properties['Record'])) {
							$delayProperties = Model::indexByField($properties['Record'], 'name');
						} ?>
						<li class="checkbox"><?php echo $f->checkbox('delay', 'There is a noticable audio delay.', !empty($delayProperties['Record.AVDelay'])); ?></li>
						<li>
							<?php echo $f->input('delay_by', 'Delay audio by', (!empty($delayProperties['Record.AVDelay']))? Properties::delayToMilliseconds($delayProperties['Record.AVDelay']['value']) : ''); ?>
							<span class="description">Delay is specified in milliseconds and can be negative.</span>
						</li>
						<li class="checkbox"><?php echo $f->checkbox('expand', 'The timeline ends before the lecture ends.'); ?></li>
						<li><?php echo $f->select('expand_by', 'Expand timeline by', array('5' => '5 minutes', '10' => '10 minutes', '20' => '20 minutes', '30' => '30 minutes', '60' => '60 minutes', '90' => '90 minutes')); ?></li>
					<?php elseif ($action == 'fix'): ?>
						<li>
							<?php echo $f->input('replacement', 'Source Replacement'); ?>
							<span class="description">If there is a new file that should act as source for lecture pieces, insert the filename here.</span>
						</li>
					<?php endif; ?>
					<?php if ($action != 'fix'): ?>
						<li class="checkbox">
							<?php switch ($action) {
								case 'check':
									echo $f->checkbox('reset', 'Source material is flawed or cutting failed. Reset all encoding tasks.') . '</li><li class="checkbox">';
									echo $f->checkbox('failed', 'This encoding failed or something is wrong with the metadata.');
									break;
								case 'cut':
									echo $f->checkbox('failed', 'I\'m unable to cut this lecture, because something is broken.', $ticket['failed']);
									break;
								case 'handle':
									echo $f->checkbox('failed', 'I dont\' think we are going to resolve this.', null, array('class' => 'wontfix'));
									break;
							} ?>
						</li>
					<?php endif; ?>
					<li><?php echo $f->textarea('comment', 'Comment', null, array('class' => 'wide' . (($action != 'fix')? ' hidden' : ''))); ?></li>
					<li>
						<?php switch ($action) {
							case 'cut':
								echo $f->submit('I finished cutting');
								break;
							case 'check':
								echo $f->submit('Everything\'s fine');
								break;
							case 'fix':
								echo $f->submit('There, I fixed it');
								break;
							case 'handle':
								echo $f->submit('Resolve this case');
						}
					
						if ($action != 'fix') {
							echo $f->checkbox('forward', null, false, array(), true) . '<label for="ticket-action-forward" class="ticket-action-forward">jump to next ticket</label>';
						}

						echo ' or ' . $this->linkTo('tickets', 'un' . $action, $ticket + $project + (($referer)? array('?ref=' . $referer) : array()), 'leave and reset ticket', 'reset state and remove assignee'); ?>
					</li>
				<?php endif; ?>
			</ul>
		</fieldset>
	</form>
<?php endif; ?>

<?php if (!empty($parent)): ?>
	<h3>Parent</h3>
	<?php $this->render('tickets/table.tpl', array('tickets' => $parent, 'referer' => false)); ?>
<?php endif; ?>

<?php if (!empty($children)): ?>
	<h3>Children</h3>
	<?php $this->render('tickets/table.tpl', array('tickets' => $children, 'referer' => false, 'simulateTickets' => true)); ?>
<?php endif; ?>

<?php if (!empty($properties)): ?>	
	<h3>Properties</h3>
	<table class="properties">
		<?php foreach ($properties as $title => $root): ?>
			<tr>
				<th colspan="2"><?php echo $title; ?></th>
			</tr>
			<?php foreach ($root as $property): ?>
				<tr>
					<td class="key"><?php echo (mb_strpos($property['name'], '.') !== false)? (mb_substr($property['name'], mb_strlen($title) + 1)) : $property['name']; ?></td>
					<td class="value">
						<?php if (mb_strlen($property['value']) > 80 and ($pos = mb_strpos($property['value'], ' ', 80)) !== false) {
							echo mb_substr($property['value'], 0, $pos + 1) . '<span class="more">' . mb_substr($property['value'], $pos + 1) . '</span>';
						} else {
							echo $property['value'];
						} ?>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endforeach; ?>
	</table>
<?php endif; ?>

<div id="timeline">
	<h3>Timeline</h3>
	<div class="line"></div>
	<ul class="clearfix">
		<?php if (empty($action) and User::isAllowed('tickets', 'comment')): ?>
			<li class="event left">
				<?php echo $f = $this->form('tickets', 'comment', $ticket + $project); ?>
						<fieldset>
						<ul>
							<li><?php echo $f->textarea('text', null, null, array('class' => 'wide')); ?></li>
							<li>
								<?php echo $f->checkbox('needs_attention', 'Ticket needs attention', $ticket['needs_attention']);
								echo $f->submit('Comment'); ?>
							</li>
						</ul>
					</fieldset>
				</form>
			</li>
		<?php endif;
		
		if (!empty($timeline)):
			foreach ($timeline as $entry):
				if ($entry['event'] != 'Comment.Add'): ?>
					<li class="event <?php echo $entry['type'] . ' ' . (($entry['type'] == 'comment')? 'left' : 'right'); ?>">
						<?php switch ($entry['type']) {
							case 'comment': ?>
								<p><?php echo nl2br(Filter::specialChars($entry['comment'])); ?></p>
								<strong<?php echo (!empty($entry['origin_user_name']))? ' class="origin"' : ''; ?>>– <?php echo (empty($entry['origin_user_name']))? $entry['user_name'] : mb_substr($entry['origin_user_name'], 0, 20); // TODO: add symbol ?></strong>
								<?php if (User::isAllowed('tickets', 'delete_comment', $entry['id'], $entry['user_id'])) {
									echo $this->linkTo('tickets', 'delete_comment', $entry + $project, 'delete');
								} ?>
								<span class="date"><?php echo Date::distanceInWords(new Date($entry['created'])); ?> ago<span>: <?php echo Date::fromString($entry['created'], null, 'D, M j Y, H:i') ?></span></span>
								<?php break;
							case 'log': ?>
									<span class="title"><?php if (isset($entry['message'])) {
										$toState = (isset($entry['to_state_name']))? $entry['to_state_name'] : 'unknown state';
										$fromState = (isset($entry['from_state_name']))? $entry['from_state_name'] : 'unknown state';
										
										// TODO: add {duration}
										echo str_replace(
											array('{to_state}', '{to_State}', '{from_state}', '{from_State}'),
											array($toState, mb_ucfirst($toState), $fromState, mb_ucfirst($fromState)),
											$entry['message']
										);
									} else {
										echo '<em>' . $entry['event'] . '</em>';
									} ?></span>
									<?php if (!empty($entry['comment'])): ?>
										<code>
											<?php $lines = array_filter(explode("\n", Filter::specialChars($entry['comment'])));
											
											echo nl2br(implode('<br />', array_slice($lines, 0, 3)));
											
											if (count($lines) > 3) {
												echo ' ' . $this->linkTo('tickets', 'log', $ticket + array('entry' => $entry['id']) + $project + array('.txt'), 'more');
											} ?>
										</code>
									<?php endif; ?>
									
									<span class="date"><?php echo Date::distanceInWords(new Date($entry['created'])); ?> ago<span>: <?php echo Date::fromString($entry['created'], null, 'D, M j Y, H:i') ?></span></span><span class="description"> by <?php echo $entry['user_name']; ?></span>
								<?php break;
						} ?>
						<span class="spine"></span>
	    			</li>
				<?php endif;
			endforeach;
		endif; ?>
	</ul>
</div>