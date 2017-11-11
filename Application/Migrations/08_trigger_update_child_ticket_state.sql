BEGIN;

CREATE OR REPLACE FUNCTION update_child_ticket_state() RETURNS trigger AS $$
  DECLARE
	profile record;
  BEGIN
	IF NEW.type_id = 1 AND NEW.state_id <> OLD.state_id THEN
		IF NEW.state_id = 11 THEN -- set to copied
			-- update encoding tickets: set state from "material needed" to "ready to encode"
			UPDATE tbl_ticket SET state_id = 13 WHERE type_id = 2 AND parent_id = NEW.id AND state_id = 12;
		END IF;
	END IF;
	RETURN NEW;
  END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_child_ticket_state AFTER UPDATE ON tbl_ticket FOR EACH ROW EXECUTE PROCEDURE update_child_ticket_state();

COMMIT;