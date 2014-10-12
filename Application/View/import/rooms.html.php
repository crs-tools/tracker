<?= $this->render('import/_header', ['title' => 'Import: select rooms']); ?>

<?= $f = $reviewForm(); ?>
	<fieldset>
		<ul data-invert-checkboxes="true">
			<?php foreach ($rooms as $room): ?>
				<li class="checkbox"><?= $f->checkbox('rooms[' . $room . ']', $room, (isset($selectedRooms[$room]))? $selectedRooms[$room] : true, [], false); ?></li>
			<?php endforeach; ?>
			<li><?= $f->submit('Cancel', ['name' => 'cancel']) . $f->submit('Continue'); ?></li>
		</ul>
	</fieldset>		
</form>