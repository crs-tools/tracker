BEGIN;

SET ROLE TO postgres;

CREATE TABLE tbl_log
(
  id bigserial NOT NULL,
  ticket_id bigint NOT NULL,
  created timestamp without time zone NOT NULL DEFAULT now(),
  from_state enum_ticket_state,
  to_state enum_ticket_state,
  handle_id bigint NOT NULL,
  "comment" text,
  event character varying(255) NOT NULL,
  CONSTRAINT tbl_log_pk PRIMARY KEY (id),
  CONSTRAINT tbl_log_ticket_fk FOREIGN KEY (ticket_id)
  REFERENCES tbl_ticket (id) MATCH SIMPLE
  ON UPDATE CASCADE ON DELETE CASCADE
)
WITHOUT OIDS;

-- trigger
CREATE TRIGGER valid_handle BEFORE INSERT OR UPDATE ON tbl_log FOR EACH ROW EXECUTE PROCEDURE valid_handle();

COMMIT;