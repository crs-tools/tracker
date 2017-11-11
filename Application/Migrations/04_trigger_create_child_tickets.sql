BEGIN;

CREATE OR REPLACE FUNCTION create_child_tickets() RETURNS trigger AS $$
  DECLARE
	profile record;
  BEGIN
	IF NEW.type_id = 1 THEN 
		INSERT INTO tbl_ticket (parent_id, project_id, title, fahrplan_id, priority, type_id, state_id, encoding_profile_id) (SELECT NEW.id AS parent_id, NEW.project_id, CONCAT(NEW.title,' (',e.name,')') AS title, NEW.fahrplan_id, 1, 2, 12, e.id as encoding_profile_id FROM tbl_encoding_profile e WHERE project_id = NEW.project_id);
	END IF;
	RETURN NEW;
  END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER create_child_tickets AFTER INSERT ON tbl_ticket FOR EACH ROW EXECUTE PROCEDURE create_child_tickets();

COMMIT;