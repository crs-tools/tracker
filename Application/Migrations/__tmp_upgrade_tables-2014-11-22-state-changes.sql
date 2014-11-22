DROP TRIGGER IF EXISTS progress_trigger2 ON tbl_project_ticket_state;

DROP FUNCTION IF EXISTS update_all_tickets_progress_and_next_state();

CREATE OR REPLACE FUNCTION update_all_tickets_progress_and_next_state(param_project_id bigint)
  RETURNS VOID AS
$BODY$
  BEGIN

    UPDATE tbl_ticket t SET
      (progress, ticket_state_next, service_executable)
        = (tp, (n).ticket_state, (n).service_executable)
    FROM (
      SELECT id, ticket_state_next(t2.project_id, t2.ticket_type, t2.ticket_state) AS n, ticket_progress(t2.id) as tp
      FROM tbl_ticket t2
      WHERE t2.project_id = param_project_id AND param_project_id IS NOT NULL
    ) AS x
    WHERE t.id = x.id;

  END;
$BODY$
LANGUAGE plpgsql VOLATILE;

-- update encoding tickets state to "ready to encode", if recording ticket changes state to "finalized"
CREATE OR REPLACE FUNCTION update_encoding_ticket_state() RETURNS trigger AS $$
BEGIN
  IF NEW.ticket_type = 'recording'
    AND NEW.ticket_state <> OLD.ticket_state
    AND NEW.ticket_state = 'finalized'
    AND 'staged' = COALESCE(
      (SELECT ticket_state FROM tbl_ticket tt WHERE tt.ticket_type='meta' AND tt.id=NEW.parent_id),
      'staging')
    THEN
    UPDATE tbl_ticket SET ticket_state = 'ready to encode' WHERE ticket_type = 'encoding' AND parent_id = NEW.parent_id AND ticket_state = 'material needed';
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION set_encoding_ticket_state() RETURNS trigger AS $$
BEGIN
  IF NEW.ticket_type = 'encoding'
    AND 'finalized' = COALESCE(
      (SELECT ticket_state FROM tbl_ticket tt WHERE tt.ticket_type='recording' AND tt.parent_id=NEW.parent_id),
      'scheduled')
    AND 'staged' = COALESCE(
      (SELECT ticket_state FROM tbl_ticket tt WHERE tt.ticket_type='meta' AND tt.id=NEW.parent_id),
      'staging')
    THEN
    NEW.ticket_state := 'ready to encode';
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS set_encoding_ticket_state ON tbl_ticket;
CREATE TRIGGER set_encoding_ticket_state BEFORE INSERT ON tbl_ticket FOR EACH ROW EXECUTE PROCEDURE set_encoding_ticket_state();

\i 15_function_ticket_state.sql
\i 10_function_create_missing_encoding_tickets.sql
\i 14_function_create_missing_recording_tickets.sql


