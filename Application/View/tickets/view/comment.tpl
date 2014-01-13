<li class="event comment left">
	<p><?php echo nl2br($this->h($comment['comment'])); ?></p>
	<strong>– <?php echo $this->h($comment['user_name']); ?></strong>
	<?php if (User::isAllowed('tickets', 'delete_comment', $comment['id'], $comment['handle_id'])) {
		echo $this->linkTo('tickets', 'delete_comment', $comment, $project, 'delete');
	} ?>
	<span class="date"><?php echo (new DateTime($comment['created']))->format('d.m.Y H:i'); ?></span>
	<span class="spine"></span>
</li>