<?php

$this->title('Tickets | ');

echo $f = $searchForm(array('id' => 'tickets-search', 'data-search' => json_encode($searchForm->wasSubmitted() ? array(
		'fields' => $fields,
		'operators' => $operators,
		'values' => $values
) : null)));

$f->register('fields[]');
$f->register('operators[]');
$f->register('values[]');

?>

	<fieldset>
		<legend>Search</legend>
		
		<ul id="tickets-search-conditions">
			<li class="borderless searchrow">
				<?php echo $f->submit('Search'); ?>
			</li>
		</ul>
	</fieldset>
	<fieldset id="tickets-search-selects">
		<?php echo $f->select('types', '', $types, null, array('id' => 'tickets-search-types')); ?>
		<?php echo $f->select('states', '', $states, null, array('id' => 'tickets-search-states')); ?>
		<?php echo $f->select('users', '', $users, null, array('id' => 'tickets-search-assignees')); ?>
		<?php echo $f->select('profiles', '', $profiles, null, array('id' => 'tickets-search-profiles')); ?>
		<?php echo $f->select('rooms', '', $rooms, null, array('id' => 'tickets-search-rooms')); ?>
		<?php echo $f->select('days', '', $days, null, array('id' => 'tickets-search-days')); ?>
	</fieldset>
</form>
