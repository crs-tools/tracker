<?= $this->render('import/_header', ['title' => 'Repeat import']); ?>

<?= $f = $form(); ?>
	<fieldset>
		<ul>
			<li class="radio">
				<?= $f->radio('source', 'Fetch new version from source', 'url', true); ?>
				<span class="description">Access <code><?= h($import['url']); ?></code> to load an updated version of the Fahrplan.</span>
			</li>
			<li class="radio">
				<?= $f->radio('source', 'Use previously downloaded Fahrplan XML', 'xml'); ?>
				<span class="description">Version <?= h($import['version']); ?></span>
			</li>
			<li class="checkbox">
				<?= $f->checkbox('apply_rooms', 'Apply previous room selection', true); ?>
				<span class="description">
					<?php $rooms = []; foreach (json_decode($import['rooms']) as $room => $enabled) {
						if (!$enabled) {
							$rooms[] = '<del>' . h($room) . '</del>';
						} else {
							$rooms[] = h($room);
						}
					} echo implode(', ', $rooms); ?>
				</span>
			</li>
			<li><?= $f->submit('Repeat import') . ' or ' .
					$this->linkTo('import', 'index', $project, 'return to index', ['class' => '']); ?></li>
		</ul>
	</fieldset>		
</form>