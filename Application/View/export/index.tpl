<?php $this->title('Export | '); ?>

<div id="ticket-header">
	<h2 class="ticket"><span class="title">Export</span></h2>

	<ul class="ticket-header-bar right horizontal">
		<li class="ticket-header-bar-background-left"></li>
		
		<?php if (User::isAllowed('tickets', 'create')): ?>
			<li class="action create"><?php echo $this->linkTo('tickets', 'create', $project, '<span>create</span>', 'Create new ticket…'); ?></li>
		<?php endif; ?>
			
		<?php if (User::isAllowed('import', 'index')): ?>
			<li class="action import"><?php echo $this->linkTo('import', 'index', $project, '<span>import</span>'); ?></li>
		<?php endif; ?>

		<li class="action current export"><?php echo $this->linkTo('export', 'index', $project, '<span>export</span>'); ?></li>
		
		<li class="ticket-header-bar-background-right"></li>
	</ul>
</div>

<?php echo $f = $this->form('export', 'wiki', $project); ?>
	<?php if (User::isAllowed('export', 'wiki', $project)): ?>
	<fieldset>
		<legend>Export to public wiki</legend>
		<ul>
			<li><?php echo $f->input('url', 'URL to public wiki', $projectProperties['Wiki.URL'], array('class' => 'wide')); ?></li>
			<li><?php echo $f->input('user', 'User', $projectProperties['Wiki.User']); ?></li>
			<li><?php echo $f->password('password', 'Password', array('value' => $projectProperties['Wiki.Password'])); ?></li>
			<li><?php echo $f->submit('Export to public wiki'); ?></li>
		</ul>
	</fieldset>
	<?php endif; ?>
</form>

<?php if (User::isAllowed('export', 'feedback')):
	echo $f = $this->form('export', 'feedback', $project); ?>
		<fieldset>
			<legend>Sync with feedback tracker</legend>
			<ul>
				<li><?php echo $f->input('url', 'URL to feedback tracker', $projectProperties['Feedback.URL'], array('class' => 'wide')); ?></li>
				<li><?php echo $f->input('user', 'User', $projectProperties['Feedback.User']); ?></li>
				<li><?php echo $f->password('password', 'Password', array('value' => $projectProperties['Feedback.Password'])); ?></li>
				<li><?php echo $f->submit('Sync with feedback tracker'); ?></li>
			</ul>
		</fieldset>
	</form>
<?php endif; ?>

<?php if (User::isAllowed('export', 'podcast')): ?>
	<?php echo $f = $this->form('export', 'podcast', $project); ?>
		<fieldset>
			<legend>Export as podcast feed</legend>
			<ul>
				<?php if (empty($profiles)): ?>
					<li>
						<p>
							You can't export a podcast feed without an existing encoding profile.
							<?php if (User::isAllowed('encoders', 'profiles')) {
								echo $this->linkTo('encodingprofiles', 'create', $project, 'Create new encoding profile') . '.';
							} ?>
						</p>
					</li>
				<?php else: ?>
					<li><?php echo $f->select('profile', 'Podcast feed', $profiles); ?></li>
					<li><?php echo $f->submit('Show podcast feed'); ?></li>
				<?php endif; ?>
			</ul>
		</fieldset>
	</form>
<?php endif; ?>