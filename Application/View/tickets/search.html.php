<?php $this->title('Ticket search | '); ?>

<div id="ticket-header" class="search-header clearfix">
	<h2 class="ticket"><span class="title">Search</span></h2>
	
	<?= $this->render('tickets/index/_header'); ?>
</div>

<?php echo $f = $searchForm([
	'id' => 'tickets-search',
	'data-search' => json_encode(($searchForm->wasSubmitted())? [
		'fields' => $fields,
		'operators' => $operators,
		'values' => $values
	] : null),
	'data-edit-url' => $this->Request->getRootURL() .
		Router::reverse('tickets', 'edit_multiple', [
			'tickets' => '{tickets}',
			'project_slug' => $project['slug']
		])
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

	<?php if(!empty($tickets)):
		$stats = ['meta' => 0, 'recording' => 0, 'encoding' => 0, 'ingest' => 0]; ?>
		<ul class="tickets">
			<?php foreach ($tickets as $ticket) {
				echo $this->render('tickets/ticket', [
					'ticket' => $ticket
				]);
				
				$stats[$ticket['ticket_type']]++;
			} ?>
		</ul>
		
		<div class="tickets-search-stats">
			The search matched
			<?php foreach ($stats as $type => $count) {
				if ($count > 0 and $type !== 'meta') {
					echo (($count === 1)? 'one' : $count) . ' ' . $type .
						' ticket' . (($count !== 1)? 's' : '') . ', ';
				}
			} ?>
			<?= ($stats['meta'] === 1)? 'one' : $stats['meta']; ?> 
			meta ticket<?= ($stats['meta'] !== 1)? 's' : ''; ?>.
		</div>
	<?php endif ?>
</form>
