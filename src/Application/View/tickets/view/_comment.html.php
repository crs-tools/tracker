<li class="event comment left">
	<p><?php echo nl2br(h($comment['comment'])); ?></p>
	
	<div class="meta">
		<span class="description">
			by <strong><?= h($comment['user_name']); ?></strong>
			
			<?php if ($comment['referenced_ticket_id'] !== null): ?>
				<span class="reference">on
					<?php if ($comment['referenced_ticket_id'] === $ticket['id']) {
						echo 'this ticket';
					} else {
						echo $this->linkTo('tickets', 'view', $comment->ReferencedTicket, $project, h($comment->ReferencedTicket->getTitleSuffix()));
					} ?>
				</span>
			<?php endif; ?>
			
			<span class="date"><?= timeAgo($comment['created']); ?></span>
			
			<?php if (User::isAllowed('tickets', 'delete_comment', $comment['id'], $comment['handle_id'])) {
				echo $this->linkTo('tickets', 'delete_comment', $comment, $project, 'delete');
			} ?>
		</span>
		<span class="spine"></span>
	</div>
</li>