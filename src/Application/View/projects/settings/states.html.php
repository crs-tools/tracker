<?php $this->title('States | '); ?>
<?= $this->render('projects/settings/_header'); ?>

<?php $title_service = 'This state will be available to workers';
$title_skip = 'This state will be skipped in dependent encoding profiles';
$type = null;
$typeRows = 0; ?>

<?= $f = $stateForm(['disabled' => $project['read_only']]); ?>
	<div class="project-settings-save">
		<fieldset>
			<?= $f->submit('Save changes'); ?>
		</fieldset>
	</div>
	<div class="project-settings-triggerstate">
		<fieldset>
			<?= $f->select('dependent_ticket_trigger_state',
				'Minimum required state for encoding tickets to activate dependent tickets',
				$encodingStates, (!empty($project))? $project['dependent_ticket_trigger_state'] : null) ?>
		</fieldset>
	</div>
	<div class="column-100">
		<?php foreach ($states as $index => $state): ?>
			<?php if ($type != $state['ticket_type']):
				$type = $state['ticket_type'];
			    if ($type !== null):
                    // close previous table ?>
            </tbody>
        </table>
                <?php endif;
			    // render table header and line beginning with type name ?>
        <table class="default">
            <thead>
                <tr>
                    <th width="20%">Type</th>
                    <th width="40%">State</th>
                    <th width="15%" title="<?=$title_service?>">Service</th>
                    <th width="15%" title="<?=$title_skip?>">Master only</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= h(mb_ucfirst($type)); ?></td>
			<?php else:
                // render normal line beginning (without type name) ?>
                <tr>
                    <td class="empty"></td>
            <?php endif;
            // render rest of line ?>
                    <td>
                        <?=	$f->checkbox(
							'States[' . $index . '][ticket_state]',
							$state['ticket_state'],
							$state['project_enabled'],
							['value' => $state['ticket_state']] +
							(($state['project_enabled'])?
								['data-association-destroy' => 'States[' . $index . '][_destroy]'] :
								[]),
							false
						) .
						$f->hidden('States[' . $index . '][ticket_type]', $state['ticket_type']);
						?>
                    </td>
                    <td class="center">
                        <?php if ($state['service_executable']) {
							echo $f->checkbox('States[' . $index . '][service_executable]', null, $state['project_service_executable'], ['title' => $title_service], false);
						} ?>
                    </td>
                    <td class="center">
                        <?php if (TicketState::isSkippable($state['ticket_state'])) {
							echo $f->checkbox('States[' . $index . '][skip_on_dependent]', null, $state['project_skip_on_dependent'], ['title' => $title_skip], false);
						} ?>
                    </td>
                </tr>
				<?php $f->register('States[' . $index . '][_destroy]');
        endforeach; ?>
			</tbody>
		</table>
	</div>
</form>
