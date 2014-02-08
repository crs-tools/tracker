<?php $this->title('Tickets | '); ?>

<div id="tickets-header" class="clearfix">
	<?php echo $f = $form(array('id' => 'tickets-filter'), false); ?>
		<ul class="ticket-header-bar left horizontal">
			<li class="ticket-header-bar-background-left"></li>
			<li data-ai="1" class="first<?php echo ($filter === null/* and !$search*/)? ' current': ''; ?>"><button>All</button></li>
			<li data-ai="2" <?php echo ($filter == 'recording')? ' class="current"' : ''; ?>><?= $f->button('t', null, 'Recording', array('value' => 'recording')); ?></li>
			<li data-ai="3" <?php echo ($filter == 'cutting')? ' class="current"' : ''; ?>><?= $f->button('t', null, 'Cutting', array('value' => 'cutting')); ?></li>
			<li data-ai="4" <?php echo ($filter == 'encoding')? ' class="current"' : ''; ?>><?= $f->button('t', null, 'Encoding', array('value' => 'encoding')); ?></li>
			<li data-ai="5" class="last<?php echo ($filter == 'releasing')? ' current': ''; ?>"><?= $f->button('t', null, 'Releasing', array('value' => 'releasing')); ?></li>
			<li data-ai="5" class="last<?php echo ($filter == 'released')? ' current': ''; ?>"><?= $f->button('t', null, 'Released', array('value' => 'released')); ?></li>
			<li class="ticket-header-bar-background-right"></li>
			
			<?php /*<li class="ticket-header-bar-search"><?= $f->input('q', null, '', array('placeholder' => 'Search')); ?></li>
			<li data-ai="-1" class="ticket-header-bar-search-button"<?php echo (Request::exists(Request::get, 'search'))? ' class="current"' : ''; ?>><?php echo $f->button('search', null, 'Search', array('value' => '')); ?></li>> */ ?>
		</ul>
	</form>
	
	<?php if (User::isAllowed('tickets', 'create') or User::isAllowed('import', 'index') or User::isAllowed('export', 'index')): ?>
		<ul class="ticket-header-bar right horizontal">
			<li class="ticket-header-bar-background-left"></li>		
			<?php if (User::isAllowed('tickets', 'create')): ?>
				<li class="action create"><?php echo $this->linkTo('tickets', 'create', $project, '<span>create</span>', 'Create new ticket…'); ?></li>
			<?php endif; ?>
			
			<?php if (User::isAllowed('import', 'index')): ?>
				<li class="action import"><?php echo $this->linkTo('import', 'index', $project, '<span>import</span>'); ?></li>
			<?php endif; ?>
			
			<?php if (User::isAllowed('export', 'index')): ?>
				<li class="action export"><?php echo $this->linkTo('export', 'index', $project, '<span>export</span>'); ?></li>
			<?php endif; ?>
			<li class="ticket-header-bar-background-right"></li>
		</ul>
	<?php endif; ?>
</div>

<?php /*if (Request::exists(Request::get, 'search')): ?>
	<?php echo $f = $this->form('tickets', 'index', $project + array('?search'), array('id' => 'tickets-search')); ?>
		<fieldset>
			<legend>Search</legend>
			
			<ul id="tickets-search-conditions">
				<li class="borderless">
					<?php if (User::isAllowed('tickets', 'edit')) {
						echo $f->submit('Mass edit tickets', array('name' => 'edit', 'id' => 'tickets-search-mass-edit', 'disabled' => (Request::isPostRequest())? false : 'disabled'));
					}
					
					echo $f->submit('Search'); ?>
				</li>
			</ul>
		</fieldset>
		<fieldset id="tickets-search-selects">
			<?php echo $f->select('', false, $types, null, array('id' => 'tickets-search-types')); ?>
			<select id="tickets-search-states">
				<?php foreach($types as $id => $name): ?>
					<optgroup label="<?php echo $name; ?>">
						<?php foreach ( $states[$id] as $state ) {
							echo $f->option($state['name'], $state['id'], Request::get('s') == $state['id']);
						} ?>
					</optgroup>
				<?php endforeach; ?>
			</select>
			<?php echo $f->select('', false, $users, null, array('id' => 'tickets-search-assignees')); ?>
			<?php echo $f->select('', false, (empty($profiles))? array('' => '–') : $profiles, null, array('id' => 'tickets-search-profiles')); ?>
		</fieldset>
	</form>
<?php endif;*/ ?>

<?= $this->render('tickets/list'); ?>

<?php /*if (Request::exists(Request::get, 'search')): ?>
	<script type="text/javascript">
		var search = <?php echo (Request::isPostRequest())? json_encode(array(
			'fields' => Request::post('fields'),
			'operators' => Request::post('operators'),
			'values' => Request::post('values')
		)) : 'null'; ?>;
	</script>
<?php endif;*/ ?>