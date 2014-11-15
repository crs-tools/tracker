BEGIN;

UPDATE tbl_ticket SET title = NULL WHERE parent_id IS NOT NULL;

ALTER TABLE "public"."tbl_comment" ADD COLUMN "referenced_ticket_id" bigint;
ALTER TABLE "public"."tbl_comment" ADD CONSTRAINT "tbl_comment_log_fkt_referenced_ticket_fk" FOREIGN KEY ("referenced_ticket_id") REFERENCES "public"."tbl_ticket" ("id") ON UPDATE CASCADE ON DELETE SET NULL;
ALTER TABLE "public"."tbl_comment" ADD CONSTRAINT "tbl_comment_log_fkt_referenced_ticket_fk" FOREIGN KEY ("referenced_ticket_id") REFERENCES tbl_ticket (id) MATCH SIMPLE ON UPDATE CASCADE ON DELETE SET NULL;

# Move comments from children to parent, set reference
UPDATE tbl_comment SET referenced_ticket_id = ticket_id, ticket_id = (SELECT parent_id FROM tbl_ticket WHERE tbl_ticket.id = tbl_comment.ticket_id)
WHERE id IN (
	SELECT tbl_comment.id FROM tbl_comment
	INNER JOIN tbl_ticket ON tbl_comment.ticket_id = tbl_ticket.id
	WHERE tbl_ticket.parent_id IS NOT NULL
);

COMMIT;