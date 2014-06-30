<?php $this->title('Ticket search | '); ?>

<div id="ticket-header" class="clearfix">
	<h2 class="ticket"><span class="title">Search</span></h2>
	
	<?= $this->render('tickets/index/_header'); ?>
</div>

<?php echo $f = $searchForm([
	'id' => 'tickets-search',
	'data-search' => json_encode(($searchForm->wasSubmitted())? [
		'fields' => $fields,
		'operators' => $operators,
		'values' => $values
	] : null)
]); ?>
	<fieldset>
		<ul id="tickets-search-conditions">
			<li class="submit">
				<?= $f->submit('Search'); ?>
			</li>
		</ul>
	</fieldset>
	<fieldset id="tickets-search-selects">
		<?= $f->select('types', '', ['meta' => 'meta', 'recording' => 'recording', 'ingest' => 'ingest', 'encoding' => 'encoding'], null, ['id' => 'tickets-search-types']); ?>
		<?= $f->select('states', '', $states, null, ['id' => 'tickets-search-states']); ?>
		<?= $f->select('users', '', $users, null, ['id' => 'tickets-search-assignees']); ?>
		<?= $f->select('profiles', '', $profiles->toArray(), null, ['id' => 'tickets-search-profiles']); ?>
		<?= $f->select('rooms', '', $rooms->toArray(), null, ['id' => 'tickets-search-rooms']); ?>
		<?= $f->select('days', '', $days->toArray(), null, ['id' => 'tickets-search-days']); ?>
	</fieldset>
	
	<?php $f->register('fields[]');
	$f->register('operators[]');
	$f->register('values[]'); ?>
</form>

<?php if(!empty($tickets)): ?>
	<ul class="tickets">
		<?php foreach ($tickets as $ticket) {
			echo $this->render('tickets/ticket', [
				'ticket' => $ticket
			]);
		} ?>
	</ul>
<?php endif ?>