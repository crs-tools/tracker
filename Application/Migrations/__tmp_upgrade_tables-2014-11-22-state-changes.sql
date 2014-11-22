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

-- set inserted encoding tickets state to "ready to encode", if corresponding recording ticket is in "finalized"
CREATE OR REPLACE FUNCTION set_encoding_ticket_state() RETURNS trigger AS $$
BEGIN
  IF NEW.ticket_type = 'encoding' AND 'finalized' = COALESCE(                                                                                                                        (SELECT ticket_state FROM tbl_ticket tt WHERE tt.ticket_type='recording' AND tt.parent_id=NEW.parent_id),
    'scheduled') THEN
    NEW.ticket_state := 'ready to encode';
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS set_encoding_ticket_state ON tbl_ticket;
CREATE TRIGGER set_encoding_ticket_state BEFORE INSERT ON tbl_ticket FOR EACH ROW EXECUTE PROCEDURE set_encoding_ticket_state();


