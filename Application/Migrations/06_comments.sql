BEGIN;

SET ROLE TO postgres;

CREATE TABLE tbl_comment
(
  id bigserial NOT NULL,
  ticket_id bigint NOT NULL,
  handle_id bigint NOT NULL,
  created timestamp without time zone NOT NULL DEFAULT now(),
  comment text,
  CONSTRAINT tbl_comment_pk PRIMARY KEY (id),
  CONSTRAINT tbl_comment_log_fkt_ticket_fk FOREIGN KEY (ticket_id) REFERENCES tbl_ticket (id) MATCH SIMPLE ON UPDATE CASCADE ON DELETE CASCADE
)
WITHOUT OIDS;
CREATE TRIGGER valid_handle BEFORE INSERT OR UPDATE ON tbl_comment FOR EACH ROW EXECUTE PROCEDURE valid_handle();

COMMIT;
