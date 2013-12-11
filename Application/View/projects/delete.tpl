<?php echo $f = $form(); ?>
	<fieldset>
		<h2>Delete project <?= $this->linkTo('projects', 'view', $project, $project['title']); ?>?</h2>
		<ul>
			<li>
				<p>
					<strong>Are you sure you want to delete this project?</strong>
					<span class="description">
						All related tickets, their properties, comments and log entries will be permanently erased.<br />
						You can't undo this action.
					</span>
				</p>
			</li>
			<li><?= $f->submit('Delete project'); ?> or <?= $this->linkTo('projects', 'index', 'return without doing anything'); ?></li>
		</ul>
	</fieldset>
</form>