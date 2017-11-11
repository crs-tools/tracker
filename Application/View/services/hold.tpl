<?php echo $f = $this->form('services', 'hold', $project); ?>
	<fieldset>
		<h2>Hold on services</h2>
		<ul>
			<li>
				<p>
					<strong>Are you sure you want to hold on services?</strong>
					<span class="description">
						All future request for serviceable tickets will be declined. Current jobs will be finished.
					</span>
				</p>
			</li>
			<li><?php echo $f->submit('Halt all services'); ?> or <?php echo $this->linkTo('services', 'workers', $project, 'return without doing anything') ?></li>
		</ul>
	</fieldset>
</form>