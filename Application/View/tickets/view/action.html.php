<?= $f = $actionForm(['id' => 'ticket-action']); ?>
	<fieldset>
		<ul>
			<?php // TODO: replace with Model::doesBelongTo(Model $model)
			if ($ticket['handle_id'] != User::getCurrent()['id']): ?>
				<li class="warning"></li>
				<li>
					<label></label>
					<p>
						<?php if (empty($ticket['handle_id'])) {
							// TODO: more options if $action failed
							echo 'This ticket is abandoned';
						} else {
							echo $this->linkTo('tickets', 'index', $project, array('?u=' . $ticket['handle_id']), $ticket['handle_name']) . ' is ' . $state;
						}
						
						echo ' since ' . (new DateTime($ticket['modified']))->format('d.m.Y H:i:s'); ?>.
					</p>
				</li>
				<li><?= $f->submit('Appropriate ticket', array('name' => 'appropriate')) . ' or ' . $this->linkTo('tickets', 'index', $project, 'leave ticket untouched'); ?></li></ul>
			<?php else: ?>
				<?php switch ($action) {
					case 'cut':
					case 'check':
						echo $this->render('tickets/view/action/' . $action . '', ['f' => $f]);
						break;
				} ?>
					<?php
					// TODO: enable jumping again
					/*
						echo $f->checkbox('forward', null, false, array(), true) . '<label for="ticket-action-forward" class="ticket-action-forward">jump to next ticket</label>';
					*/

					echo ' or ' . $this->linkTo(
						'tickets',
						'un' . $action,
						$ticket,
						$project/* + (($referer)? array('?ref=' . $referer) : array())*/,
						'leave and reset ticket',
						'reset state and remove assignee'
					); ?>
				</li>
			<?php endif; ?>
		</ul>
	</fieldset>
</form>