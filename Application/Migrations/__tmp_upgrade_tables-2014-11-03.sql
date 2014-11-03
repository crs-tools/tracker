
-- please re-execute:

\i 15_function_ticket_state.sql
\i 20_view_parent_tickets.sql
\i 21_view_all_tickets.sql


DROP TRIGGER IF EXISTS progress_trigger1 ON tbl_ticket;
DROP FUNCTION IF EXISTS update_ticket_progress_and_next_state();



CREATE OR REPLACE FUNCTION update_ticket_next_state()
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
  END
$BODY$
LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION update_ticket_progress()
  RETURNS trigger AS
$BODY$
  BEGIN
    UPDATE tbl_ticket SET progress = ticket_progress(NEW.id) WHERE id = NEW.id;
    IF (NEW.parent_id IS NOT NULL) THEN
      UPDATE tbl_ticket SET progress = ticket_progress(NEW.parent_id) WHERE id = NEW.parent_id;
    END IF;

  RETURN NEW;
  END
$BODY$
LANGUAGE plpgsql VOLATILE;

DROP TRIGGER IF EXISTS state_trigger ON tbl_ticket;
CREATE TRIGGER state_trigger BEFORE INSERT OR UPDATE OF ticket_state ON tbl_ticket FOR EACH ROW EXECUTE PROCEDURE update_ticket_next_state();

CREATE TRIGGER progress_trigger1 AFTER INSERT OR UPDATE OF ticket_state ON tbl_ticket FOR EACH ROW EXECUTE PROCEDURE update_ticket_progress();


-- bugfix #75

ALTER TABLE tbl_project ADD CONSTRAINT title_length CHECK (char_length(title::text) > 0);
ALTER TABLE tbl_project ADD CONSTRAINT slug_length CHECK (char_length(slug::text) > 0);
ALTER TABLE tbl_project_language ADD CONSTRAINT language_length CHECK (char_length(language::text) > 0);
ALTER TABLE tbl_project_property ADD CONSTRAINT name_length CHECK (char_length(name::text) > 0);

