<?php $this->title('Services | '); ?>

<div id="tickets-header" class="clearfix">
	<?php //echo $f = $this->form('services', 'workers', $project, array('id' => 'services-filter', 'method' => 'get'), false); ?>
	<form>
		<ul class="ticket-header-bar left horizontal">
			<li class="ticket-header-bar-background-left"></li>
			<?php /*
			<li data-ai="1" class="first<?php echo (!Request::get('t') and !Request::exists(Request::get, 'search'))? ' current': ''; ?>"><button>All</button></li>
			<li data-ai="2" <?php echo (Request::get('t') == 'merging')? ' class="current"' : ''; ?>><?php echo $f->button('t', null, 'Merging', array('value' => 'merging')); ?></li>
			<li data-ai="3" <?php echo (Request::get('t') == 'copying')? ' class="current"' : ''; ?>><?php echo $f->button('t', null, 'Copying', array('value' => 'copying')); ?></li>
			<li data-ai="4" <?php echo (Request::get('t') == 'encoding')? ' class="current"' : ''; ?>><?php echo $f->button('t', null, 'Encoding', array('value' => 'encoding')); ?></li>
			<li data-ai="4" <?php echo (Request::get('t') == 'postprocessing')? ' class="current"' : ''; ?>><?php echo $f->button('t', null, 'Postprocessing', array('value' => 'postprocessing')); ?></li>
			<li data-ai="5" class="last<?php echo (Request::get('t') == 'releasing')? ' current': ''; ?>"><?php echo $f->button('t', null, 'Releasing', array('value' => 'releasing')); ?></li>
			*/ ?>
			<li class="ticket-header-bar-background-right"></li>
		</ul>
	</form>
</div>

<?php if (!empty($workers)): ?>
  <ul class="workers">
  	<?php foreach ($workers['user'] as $worker):
  		if (!isset($service['state']) or $service['state'] == $worker['ticket'][0]['state_id']): ?>
  			<li>
  				<span class="label">
  					<strong><?php echo $worker['name']; ?></strong>
  					<?php if (!empty($worker['hostname'])): ?>
  						<span class="hostname"><?php echo $worker['hostname']; ?></span>
  					<?php endif; ?>
  				</span>
  				<span class="display">
  					<?php if (isset($worker['ticket'][0])): ?>
  						<?php echo $this->linkTo('tickets', 'view', $worker['ticket'][0] + $project, mb_ucfirst($this->State->getNameById($worker['ticket'][0]['state_id'])) . ' ' . $worker['ticket'][0]['fahrplan_id'] . '…', array('class' => 'process')); ?>
  						<?php if (isset($worker['servicelogentry'][0]['progress'])): ?>
  							<span class="progress"><?php echo $worker['servicelogentry'][0]['progress']; ?>%</span>
  						<?php endif; ?>
  					<?php endif; ?>
  					<?php if (isset($worker['last_seen'])): ?>
  						<span class="lastseen" title="last seen <?php echo Date::fromString($worker['last_seen'], null, 'D, M j Y, H:i'); ?>"><?php $time = round(time() - Date::fromString($worker['last_seen'])->getTimestamp());
  							if ($time < 60) {
  								echo $time . ' sec';
  							} elseif ($time < 3600) {
  								echo round($time / 60) . ' min';
  							} elseif ($time < 86400) {
  								echo round($time / 3600) . ' hours';
  							} else {
  								echo round($time / 86400) . ' days';
  							}
  						?></span>
  					<?php endif; ?>
  					<?php if (isset($worker['ticket'][0])): ?>
  						<span class="since" title="since <?php echo Date::fromString($worker['ticket'][0]['modified'], null, 'D, M j Y, H:i'); ?>"><?php $time = round(time() - Date::fromString($worker['ticket'][0]['modified'])->getTimestamp());
  							// TODO: less duplicate code
  							if ($time < 60) {
  								echo $time . ' sec';
  							} elseif ($time < 3600) {
  								echo round($time / 60) . ' min';
  							} elseif ($time < 86400) {
  								echo round($time / 3600) . ' hours';
  							} else {
  								echo round($time / 86400) . ' days';
  							}
  						?></span>
  					<?php endif; ?>
  				</span>
  				<span class="actions">
  					<?php /*echo $this->linkTo('services', 'command', $worker, 'command<span></span>', 'issue command to ' . $worker['name'] . '…'); ?>
  					<?php echo $this->linkTo('services', 'halt', $worker, 'halt<span></span>', 'halt ' . $worker['name'] . '…');*/ ?>
  				</span>
			
  				<span class="log">
  					<span class="grip"></span>
  					<?php if (!empty($worker['servicelogentry'])): ?>
  						<ul class="log">
  							<?php foreach (array_reverse($worker['servicelogentry']) as $entry): ?>
  								<li><span class="time"><?php echo $entry['created']; ?></span><code><?php $entry['output_delta']; ?></code></li>
  							<?php endforeach; ?>
  						</ul>
  					<?php endif; ?>
  				</span>
  			</li>
  		<?php endif;
  	endforeach; ?>
  </ul>
<?php endif; ?>