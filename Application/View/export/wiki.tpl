<?php $this->layout(false); ?>
<!-- <released-lectures> -->
{| width="100%" border="1"
! VID
! Title
<?php foreach ($profiles as $profile): ?>
! <?php echo $profile['slug']; ?>

<?php endforeach; ?>
|-
<?php if(!empty($tickets)): ?>
<?php foreach ($tickets as $ticket): ?>
| [<?php echo sprintf($projectProperties['Fahrplan.URLScheme'], $ticket['fahrplan_id']) . ' ' . $ticket['fahrplan_id']; ?>]
| <?php echo $ticket['title']; ?>

<?php foreach ($profiles as $profile): ?>
<?php if (isset($encodings[$ticket['fahrplan_id']][$profile['id']])): ?>
| <?php echo '[' . $projectProperties['Export.Mirror'] . $profile['mirror_folder'] . '/' . $this->Properties->getFilename(array('Fahrplan.ID' => $ticket['fahrplan_id'], 'Record.Language' => $ticket['record_language'], 'Fahrplan.Slug' => $ticket['fahrplan_slug'])) . '.' . $profile['extension'] . ' ' . $profile['slug'] . ']'; ?>

<?php else: ?>
| -
<?php endif; ?>
<?php endforeach; ?>
|-
<?php endforeach; ?>
<?php endif; ?>
|}

Last update: --~~~~
<!-- </released-lectures> -->