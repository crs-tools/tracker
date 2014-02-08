<li class="event comment left">
	<p><?php echo nl2br(h($comment['comment'])); ?></p>
	<strong>– <?php echo h($comment['user_name']); ?></strong>
	<span class="date"><?= timeAgo($comment['created']); ?></span>
	<?php if (User::isAllowed('tickets', 'delete_comment', $comment['id'], $comment['handle_id'])) {
		echo $this->linkTo('tickets', 'delete_comment', $comment, $project, 'delete');
	} ?>
	<span class="spine"></span>
</li>