<?= $this->render('import/header', ['title' => 'Select rooms']); ?>

<?= $f = $reviewForm(); ?>
	<fieldset>
		<ul data-invert-checkboxes="true">
			<?php foreach ($rooms as $room => $exists): ?>
				<li class="checkbox"><?= $f->checkbox('rooms[' . $room . ']', $room, false, [], false); ?></li>
			<?php endforeach; ?>
			<li><?= $f->submit('Review changes'); ?></li>
		</ul>
	</fieldset>		
</form>