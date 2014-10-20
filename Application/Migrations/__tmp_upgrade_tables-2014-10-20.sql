CREATE OR REPLACE FUNCTION update_ticket_progress_and_next_state()
  RETURNS trigger AS
$BODY$
  DECLARE
   next_state record;
  BEGIN
    next_state := ticket_state_next(NEW.project_id, NEW.ticket_type, NEW.ticket_state);

    NEW.ticket_state_next := next_state.ticket_state;
    NEW.service_executable := next_state.service_executable;

    IF (NEW.parent_id IS NOT NULL) THEN
      UPDATE tbl_ticket SET progress = ticket_progress(NEW.parent_id) WHERE id = NEW.parent_id;
    END IF;

  RETURN NEW;
  END;
$BODY$
  LANGUAGE plpgsql;

DROP TRIGGER progress_trigger1 ON tbl_ticket;
CREATE TRIGGER progress_trigger1 BEFORE INSERT OR UPDATE OF ticket_state ON tbl_ticket FOR EACH ROW EXECUTE PROCEDURE update_ticket_progress_and_next_state();

CREATE OR REPLACE FUNCTION update_all_tickets_progress_and_next_state()
  RETURNS trigger AS
$BODY$
  BEGIN

    UPDATE tbl_ticket t SET
      (progress, ticket_state_next, service_executable)
        = (tp, (n).ticket_state, (n).service_executable)
    FROM (SELECT id, ticket_state_next(t2.project_id, t2.ticket_type, t2.ticket_state) AS n, ticket_progress(t2.id) as tp FROM tbl_ticket t2) AS x
    WHERE t.id = x.id;

  RETURN NULL;
  END;
$BODY$
LANGUAGE plpgsql VOLATILE;


