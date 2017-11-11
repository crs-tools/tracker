<ul class="edit-properties" data-properties-description="<?= $properties['description']; ?>" data-properties-create-key="<?= $properties['field'] . '[][' . $properties['key'] . ']'; ?>" data-properties-create-value="<?= $properties['field'] . '[][' . $properties['value'] . ']'; ?>">
	<?php if ($properties['for'] !== null):
		foreach($properties['for'] as $index => $property):
			if (!empty($property['virtual'])) {
				continue;
			} ?>
			<li>
				<?php echo $f->hidden($properties['field'] . '[' . $index . '][' . $properties['key'] . ']', $property[$properties['key']]);
				
				$showTextarea = (strpos($property[$properties['value']], "\n") !== false);
				
				if ($showTextarea) {
					echo $f->textarea(
						$properties['field'] . '[' . $index . '][' . $properties['value'] . ']',
						$property[$properties['key']],
						$property[$properties['value']],
						[
							'class' => 'wide',
							'data-property-index' => $index,
							'data-property-destroy' => $properties['field'] . '[' . $index . '][_destroy]'
						] +
						((isset($properties['placeholder']))? ['placeholder' => $properties['placeholder']] : [])
					);
				} else {
					echo $f->input(
						$properties['field'] . '[' . $index . '][' . $properties['value'] . ']',
						$property[$properties['key']],
						$property[$properties['value']],
						[
							'class' => 'wide',
							'data-property-index' => $index,
							'data-property-destroy' => $properties['field'] . '[' . $index . '][_destroy]'
						] +
						((isset($properties['placeholder']))? ['placeholder' => $properties['placeholder']] : [])
					);
				}
				
				$f->register($properties['field'] . '[' . $index . '][_destroy]');
				
				if (isset($properties['hidden'])) {
					foreach ($properties['hidden'] as $key => $value) {
						if (is_int($key)) {
							echo $f->hidden($properties['field'] . '[' . $index . '][' . $value . ']', $property[$value]);
						} else {
							echo $f->hidden($properties['field'] . '[' . $index . '][' . $key . ']', $value);
						}
					}
				} ?>
			</li>
		<?php endforeach;
	endif;
	
	$f->register($properties['field'] . '[][' . $properties['key'] . ']');
	$f->register($properties['field'] . '[][' . $properties['value'] . ']');
	
	if (isset($properties['hidden'])) {
		foreach ($properties['hidden'] as $key => $value) {
			if (is_int($key)) {
				continue;
			}
			
			echo $f->hidden($properties['field'] . '[][' . $key . ']', $value, ['data-properties-hidden' => true]);
		}
	} ?>
</ul>