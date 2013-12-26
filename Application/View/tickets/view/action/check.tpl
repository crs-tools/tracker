<li class="checkbox"><?= $f->checkbox('reset', 'Source material is flawed or cutting failed. Reset all encoding tasks.'); ?></li>
<li class="checkbox"><?= $f->checkbox('failed', 'This encoding failed or something is wrong with the metadata.'); ?></li>
<li><?= $f->textarea('comment', 'Comment', null, array('class' => 'wide' . (($action != 'fix')? ' hidden' : ''))); ?></li>

<li><?= $f->submit('Everything\'s fine'); ?>