BEGIN;

SET ROLE TO postgres;

ALTER TABLE tbl_project
  ADD COLUMN dependent_ticket_trigger_state enum_ticket_state
  NOT NULL DEFAULT 'released';

COMMIT;


