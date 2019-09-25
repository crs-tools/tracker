BEGIN;
	
SET ROLE TO postgres;
	
-- trigger function to set initial ticket state from project settings if it is not given

CREATE OR REPLACE FUNCTION set_ticket_initial_state()
  RETURNS trigger AS
$BODY$
  DECLARE
   next_state record;
  BEGIN
    IF (NEW.ticket_state IS NULL) THEN
      NEW.ticket_state := ticket_state_initial(NEW.project_id, NEW.ticket_type);
    END IF;

    RETURN NEW;
  END
$BODY$
LANGUAGE plpgsql VOLATILE;

DROP TRIGGER IF EXISTS initial_state_trigger ON tbl_ticket;

CREATE TRIGGER initial_state_trigger BEFORE INSERT ON tbl_ticket FOR EACH ROW EXECUTE PROCEDURE set_ticket_initial_state();

COMMIT;

