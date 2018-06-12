BEGIN;

CREATE OR REPLACE FUNCTION update_ticket_progress()
  RETURNS trigger AS
$BODY$
  BEGIN
    IF TG_OP = 'DELETE' THEN
      UPDATE tbl_ticket SET progress = ticket_progress(OLD.parent_id) WHERE id = OLD.parent_id;
    ELSE
      UPDATE tbl_ticket SET progress = ticket_progress(NEW.id) WHERE id = NEW.id;
      UPDATE tbl_ticket SET progress = ticket_progress(NEW.parent_id) WHERE id = NEW.parent_id;
	END IF;
  RETURN NULL;
  END
$BODY$
LANGUAGE plpgsql VOLATILE;

DROP TRIGGER IF EXISTS progress_trigger1 ON tbl_ticket;

CREATE TRIGGER progress_trigger
 AFTER INSERT OR DELETE OR UPDATE OF ticket_state, parent_id
 ON tbl_ticket
 FOR EACH ROW EXECUTE PROCEDURE update_ticket_progress();

COMMIT;
