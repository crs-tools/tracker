<?php $this->title('Tickets | '); ?>

<div id="tickets-header" class="clearfix">
	<?php echo $f = $form(array('id' => 'tickets-filter')); ?>
		<ul class="ticket-header-bar left horizontal">
			<li class="ticket-header-bar-background-left"></li>
			<li data-ai="1" class="first<?php echo ($filter === null/* and !$search*/)? ' current': ''; ?>"><button>All</button></li>
			<li data-ai="2" <?php echo ($filter == 'recording')? ' class="current"' : ''; ?>><?= $f->button('t', null, 'Recording', array('value' => 'recording')); ?></li>
			<li data-ai="3" <?php echo ($filter == 'cutting')? ' class="current"' : ''; ?>><?= $f->button('t', null, 'Cutting', array('value' => 'cutting')); ?></li>
			<li data-ai="4" <?php echo ($filter == 'encoding')? ' class="current"' : ''; ?>><?= $f->button('t', null, 'Encoding', array('value' => 'encoding')); ?></li>
			<li data-ai="5" class="last<?php echo ($filter == 'releasing')? ' current': ''; ?>"><?= $f->button('t', null, 'Releasing', array('value' => 'releasing')); ?></li>
			<li data-ai="5" class="last<?php echo ($filter == 'released')? ' current': ''; ?>"><?= $f->button('t', null, 'Released', array('value' => 'released')); ?></li>
			<li class="ticket-header-bar-background-right"></li>
		</ul>
	</form>
	
	<?php echo $f = $searchForm(); ?>
		<ul class="ticket-header-bar right horizontal">
			<li class="ticket-header-bar-search"><?= $f->input('q', null, '', array('placeholder' => 'Search')); ?></li>
			<li class="ticket-header-bar-search-button <?php echo ($filter == 'search')? 'current' : ''; ?>">
				<?php echo $f->button('search', null, 'Search'); ?>
			</li>
			
			<?php if (User::isAllowed('tickets', 'create') or User::isAllowed('import', 'index')): ?>
				<li class="ticket-header-bar-background-left"></li>
				<?php if (User::isAllowed('tickets', 'create')): ?>
					<li class="action create"><?php echo $this->linkTo('tickets', 'create', $project, '<span>create</span>', 'Create new ticket…'); ?></li>
				<?php endif; ?>
				
				<?php if (User::isAllowed('import', 'index')): ?>
					<li class="action import"><?php echo $this->linkTo('import', 'index', $project, '<span>import</span>'); ?></li>
				<?php endif; ?>
				<li class="ticket-header-bar-background-right"></li>
			<?php endif; ?>
		</ul>
	</form>
</div>

<?php if($filter == 'search'): ?>
	<?= $this->render('tickets/search'); ?>
<?php endif ?>
<?php if(!empty($tickets)): ?>
	<ul class="tickets">
		<?php foreach ($tickets as $ticket) {
			echo $this->render('tickets/ticket', [
				'ticket' => $ticket
			]);
		} ?>
	</ul>
<?php endif ?>
