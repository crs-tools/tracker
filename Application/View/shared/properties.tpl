<ul class="edit-properties" data-properties-description="language" data-properties-create-key="<?php echo $properties['field'] . '[][' . $properties['key'] . ']'; ?>" data-properties-create-value="<?php echo $properties['field'] . '[][' . $properties['value'] . ']'; ?>">
	<?php if ($properties['for'] !== null):
		foreach($properties['for'] as $index => $property): ?>
			<li>
				<?php echo $f->hidden($properties['field'] . '[' . $index . '][' . $properties['key'] . ']', $property[$properties['key']]); 
				echo $f->input(
					$properties['field'] . '[' . $index . '][' . $properties['value'] . ']',
					$property[$properties['key']],
					$property[$properties['value']],
					array(
						'data-property-index' => $index,
						'data-property-destroy' => $properties['field'] . '[' . $index . '][_destroy]'
					)
				);
				$f->register($properties['field'] . '[' . $index . '][_destroy]') ?>
			</li>
		<?php endforeach;
	endif;
	
	$f->register($properties['field'] . '[][' . $properties['key'] . ']');
	$f->register($properties['field'] . '[][' . $properties['value'] . ']'); ?>
</ul>