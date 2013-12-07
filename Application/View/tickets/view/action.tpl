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