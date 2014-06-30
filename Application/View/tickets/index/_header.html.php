<?= $f = $searchForm(['id' => 'tickets-quicksearch']); ?>
	<ul class="ticket-header-bar right horizontal">
		<li class="ticket-header-bar-search"><?= $f->input('q', null, '', array('placeholder' => 'Search')); ?></li>
		<li class="ticket-header-bar-search-button <?= ($arguments['action'] == 'search')? 'current' : ''; ?>">
			<?= $f->button('search', null, 'Search'); ?>
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