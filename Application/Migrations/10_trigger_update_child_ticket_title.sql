CREATE OR REPLACE FUNCTION update_child_ticket_title() RETURNS trigger AS $$
  DECLARE
	profile record;
  BEGIN
	IF NEW.type_id = 1 AND NEW.title <> OLD.title THEN
		-- update encoding tickets: set title to "<title> (<encoding_profile.name>)"
		UPDATE tbl_ticket t SET title = (SELECT CONCAT(NEW.title,' (',e.name,')') FROM tbl_encoding_profile e WHERE t.encoding_profile_id = e.id) WHERE type_id = 2 AND parent_id = NEW.id;
	END IF;
	RETURN NEW;
  END;
$$
LANGUAGE plpgsql;

ALTER FUNCTION update_child_ticket_title() OWNER TO c3tt;

CREATE TRIGGER update_child_ticket_title AFTER UPDATE ON tbl_ticket FOR EACH ROW EXECUTE PROCEDURE update_child_ticket_title();