BEGIN;

UPDATE tbl_ticket SET title = NULL WHERE parent_id IS NOT NULL;

ALTER TABLE "public"."tbl_comment" ADD COLUMN "referenced_ticket_id" bigint;
ALTER TABLE "public"."tbl_comment" DROP CONSTRAINT IF EXISTS "tbl_comment_log_fkt_referenced_ticket_fk";
ALTER TABLE "public"."tbl_comment" ADD CONSTRAINT "tbl_comment_log_fkt_referenced_ticket_fk" FOREIGN KEY ("referenced_ticket_id") REFERENCES tbl_ticket (id) MATCH SIMPLE ON UPDATE CASCADE ON DELETE SET NULL;

-- Move comments from children to parent, set reference
UPDATE tbl_comment SET referenced_ticket_id = ticket_id, ticket_id = (SELECT parent_id FROM tbl_ticket WHERE tbl_ticket.id = tbl_comment.ticket_id)
WHERE id IN (
	SELECT tbl_comment.id FROM tbl_comment
	INNER JOIN tbl_ticket ON tbl_comment.ticket_id = tbl_ticket.id
	WHERE tbl_ticket.parent_id IS NOT NULL
);

CREATE TABLE tbl_project_worker_group_filter
(
  id bigserial NOT NULL,
  project_id bigint NOT NULL,
  worker_group_id bigint NOT NULL,
  property_key ltree NOT NULL CHECK (char_length(property_key::text) > 0),
  property_value character varying(8196) NOT NULL,
  CONSTRAINT tbl_project_worker_group_filter_pk PRIMARY KEY (id, project_id, worker_group_id, property_key),
  CONSTRAINT tbl_project_worker_group_filter_group_fk FOREIGN KEY (worker_group_id)
      REFERENCES tbl_worker_group (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tbl_project_worker_group_filter_project_fk FOREIGN KEY (project_id)
      REFERENCES tbl_project (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITHOUT OIDS;


COMMIT;
