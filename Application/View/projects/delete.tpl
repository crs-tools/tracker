<?php echo $f = $this->form('projects', 'delete', $project); ?>
	<fieldset>
		<h2>Delete project <?php echo $this->linkTo('tickets', 'index', array('project_slug' => $project['slug']), $project['title']); ?></h2>
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
			<li><?php echo $f->submit('Delete project'); ?> or <?php echo $this->linkTo('projects', 'index', 'return without doing anything') ?></li>
		</ul>
	</fieldset>
</form>