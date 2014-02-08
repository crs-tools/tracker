<?php if (!isset($action)) {
	$this->title($ticket['fahrplan_id'] . ' | ' . $ticket['title'] . ' | ');
} else {
	$this->title(mb_ucfirst($action) . ' lecture ' . $ticket['title'] . ' | ');
} ?>

<?= $this->render('tickets/view/header.tpl', [
	'titlePrefix' => (isset($action))?
		h(mb_ucfirst($action)) . ' lecture ' :
		null,
	'showDetails' => !isset($action),
	'currentAction' => (isset($action))? $action : null
]); ?>

<?php if (!empty($action)) {
	echo $this->render('tickets/view/action.tpl');
} ?>

<?php if (isset($parent)): ?>
	<h3>Parent</h3>
	<?= $this->render('tickets/list.tpl', [
		'tickets' => [$parent],
		'referer' => false
	]); ?>
<?php endif; ?>

<?php if (isset($children) and $children->getRows() > 0): ?>
	<h3>Children</h3>
	<?= $this->render('tickets/list.tpl', [
		'tickets' => $children,
		'referer' => false,
		'simulateTickets' => true
	]); ?>
<?php endif; ?>

<?php if (isset($profile)): ?>
	<h3 class="table">Encoding profile</h3>
	
	<table class="default">
		<thead>
			<tr>
				<th width="20%">Name</th>
				<th>Version</th>
				<th width="10%"></th>
				<th width="13%"></th>
			</tr>
		</thead>
		<tbody>
			<td><?= $profile['name']; ?></td>
			<td>r<?= $profile['revision'] . ' – ' . $profile['description']; ?></td>
			<td>
				<?php if (User::isAllowed('encodingprofiles', 'edit')) {
					echo $this->linkTo('encodingprofiles', 'edit', $profile, 'edit profile');
				} ?>
			</td>
			<td class="link right">
				<?php if (User::isAllowed('tickets', 'jobfile')) {
					echo $this->linkTo('tickets', 'jobfile', $ticket, ['.xml'], $project, 'download jobfile');
				} ?>
			</td>
		</tbody>
	</table>
<?php endif; ?>

<h3 class="table">Properties</h3>

<?= $this->render('shared/properties.tpl'); ?>

<?php if (isset($parentProperties)) {
	echo $this->render('shared/properties.tpl', ['properties' => $parentProperties]);
}

if (isset($recordingProperties)) {
	echo $this->render('shared/properties.tpl', ['properties' => $recordingProperties]);
} ?>

<div id="timeline">
	<h3>Timeline</h3>
	<div class="line"></div>
	<ul class="clearfix">
		<?php if (empty($action) and User::isAllowed('tickets', 'comment')): ?>
			<li class="event left">
				<?php echo $f = $commentForm(); ?>
						<fieldset>
						<ul>
							<li><?php echo $f->textarea('text', null, null, array('class' => 'wide')); ?></li>
							<li>
								<?php echo $f->checkbox('needs_attention', 'Ticket needs attention', $ticket['needs_attention']);
								echo $f->submit('Comment'); ?>
							</li>
						</ul>
					</fieldset>
				</form>
			</li>
		<?php endif;
		
		$log = $log->getIterator();
		
		foreach ($comments as $comment) {
			while (strtotime($log->current()['created']) > strtotime($comment['created'])) {
				echo $this->render('tickets/view/log_entry.tpl', ['entry' => $log->current()]);
				$log->next();
			}
			
			echo $this->render('tickets/view/comment.tpl', ['comment' => $comment]);
		}
		
		while ($log->current()) {
			echo $this->render('tickets/view/log_entry.tpl', ['entry' => $log->current()]);
			$log->next();
		} ?>
	</ul>
</div>