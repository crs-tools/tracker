<ul class="edit-properties" data-properties-description="<?= $properties['description']; ?>" data-properties-create-key="<?= $properties['field'] . '[][' . $properties['key'] . ']'; ?>" data-properties-create-value="<?= $properties['field'] . '[][' . $properties['value'] . ']'; ?>">
	<?php if ($properties['for'] !== null):
		foreach($properties['for'] as $index => $property):
			if (!empty($property['virtual'])) {
				continue;
			} ?>
			<li>
				<?php echo $f->hidden($properties['field'] . '[' . $index . '][' . $properties['key'] . ']', $property[$properties['key']]); 
				echo $f->input(
					$properties['field'] . '[' . $index . '][' . $properties['value'] . ']',
					$property[$properties['key']],
					$property[$properties['value']],
					[
						'data-property-index' => $index,
						'data-property-destroy' => $properties['field'] . '[' . $index . '][_destroy]'
					] +
					((isset($properties['placeholder']))? ['placeholder' => $properties['placeholder']] : [])
				);
				$f->register($properties['field'] . '[' . $index . '][_destroy]') ?>
			</li>
		<?php endforeach;
	endif;
	
	$f->register($properties['field'] . '[][' . $properties['key'] . ']');
	$f->register($properties['field'] . '[][' . $properties['value'] . ']'); ?>
</ul>