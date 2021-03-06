<?php $this->title('Tickets | '); ?>

<div id="tickets-header" class="clearfix">
	<?php echo $f = $form(['id' => 'tickets-filter']); ?>
		<ul class="ticket-header-bar left horizontal">
			<li class="ticket-header-bar-background-left"></li>
			<li data-ai="1" class="first all<?php echo ($filter === null/* and !$search*/)? ' current': ''; ?>"><button>All</button></li>
			<li data-ai="2" class="recording<?php echo ($filter == 'recording')? ' current"' : ''; ?>"><?= $f->button('t', null, 'Recording', ['value' => 'recording']); ?></li>
			<li data-ai="3" class="cutting<?php echo ($filter == 'cutting')? ' current"' : ''; ?>"><?= $f->button('t', null, 'Cutting', ['value' => 'cutting']); ?></li>
			<li data-ai="4" class="encoding<?php echo ($filter == 'encoding')? ' current"' : ''; ?>"><?= $f->button('t', null, 'Encoding', ['value' => 'encoding']); ?></li>
			<li data-ai="5" class="releasing <?php echo ($filter == 'releasing')? ' current': ''; ?>"><?= $f->button('t', null, 'Releasing', ['value' => 'releasing']); ?></li>
			<li data-ai="6" class="last released <?php echo ($filter == 'released')? ' current': ''; ?>"><?= $f->button('t', null, 'Released', ['value' => 'released']); ?></li>
			<li class="ticket-header-bar-background-right"></li>
		</ul>
	</form>
	
	<?= $this->render('tickets/index/_header'); ?>
</div>

<?php if(!empty($tickets)): ?>
	<ul class="tickets">
		<?php foreach ($tickets as $ticket) {
			echo $this->render('tickets/ticket', [
				'ticket' => $ticket
			]);
		} ?>
	</ul>
<?php endif ?>
