BEGIN;

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

CREATE OR REPLACE FUNCTION create_missing_encoding_tickets(param_project_id bigint, param_encoding_profile_id bigint) RETURNS integer AS $$
  DECLARE
	row_count integer;
  BEGIN
	row_count := 0;
	IF param_encoding_profile_id IS NULL THEN 
		INSERT INTO tbl_ticket (parent_id, project_id, fahrplan_id, priority, ticket_type, ticket_state, encoding_profile_version_id)
		(SELECT 
			t1.id as parent_id, 
			t1.project_id, 
			t1.fahrplan_id, 
			pep.priority, 
			'encoding' as ticket_type, 
			'material needed' AS ticket_state, 
			pep.encoding_profile_version_id 
		FROM 
			tbl_project_encoding_profile pep
		JOIN 
			tbl_encoding_profile_version epv ON pep.encoding_profile_version_id = epv.id
		JOIN 
			tbl_encoding_profile ep ON epv.encoding_profile_id = ep.id
		LEFT OUTER JOIN 
			tbl_ticket t1 ON pep.project_id = t1.project_id 
		LEFT JOIN 
			tbl_ticket t2 ON t2.parent_id = t1.id AND t2.encoding_profile_version_id = epv.id 
		WHERE 
			t1.ticket_type = 'meta' AND 
			t2.id IS NULL AND 
			pep.project_id = param_project_id 
		ORDER BY t1.id ASC, ep.id ASC);
		GET DIAGNOSTICS row_count = ROW_COUNT;
	ELSE
		INSERT INTO tbl_ticket (parent_id, project_id, fahrplan_id, priority, ticket_type, ticket_state, encoding_profile_version_id)
		(SELECT 
			t1.id as parent_id, 
			t1.project_id, 
			t1.fahrplan_id, 
			pep.priority, 
			'encoding' as ticket_type, 
			'material needed' AS ticket_state, 
			pep.encoding_profile_version_id 
		FROM 
			tbl_project_encoding_profile pep
		JOIN 
			tbl_encoding_profile_version epv ON pep.encoding_profile_version_id = epv.id
		JOIN 
			tbl_encoding_profile ep ON epv.encoding_profile_id = ep.id
		LEFT OUTER JOIN 
			tbl_ticket t1 ON pep.project_id = t1.project_id 
		LEFT JOIN 
			tbl_ticket t2 ON t2.parent_id = t1.id AND t2.encoding_profile_version_id = epv.id 
		WHERE 
			t1.ticket_type = 'meta' AND 
			t2.id IS NULL AND 
			pep.project_id = param_project_id AND
			epv.encoding_profile_id = param_encoding_profile_id
		ORDER BY t1.id ASC, ep.id ASC);
		GET DIAGNOSTICS row_count = ROW_COUNT;
	END IF;
	return row_count;
  END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION create_missing_recording_tickets(param_project_id bigint) RETURNS integer AS $$
  DECLARE
	row_count integer;
  BEGIN
	row_count := 0;

	 INSERT INTO tbl_ticket (parent_id, project_id, fahrplan_id, ticket_type, ticket_state)
		(SELECT
       t1.id as parent_id,
       t1.project_id,
       t1.fahrplan_id,
       'recording' as ticket_type,
       'scheduled' AS ticket_state
     FROM
         tbl_ticket t1
         LEFT JOIN
         tbl_ticket t2 ON t2.parent_id = t1.id AND t2.ticket_type = 'recording'
     WHERE
       t1.ticket_type = 'meta' AND
       t1.project_id = param_project_id
     GROUP BY t1.id HAVING COUNT(t2.id) = 0);
		GET DIAGNOSTICS row_count = ROW_COUNT;
	  return row_count;
  END;
$$ LANGUAGE plpgsql;

ALTER TABLE tbl_ticket ALTER COLUMN title DROP NOT NULL;
DROP TRIGGER IF EXISTS update_child_ticket_title ON tbl_ticket;
DROP FUNCTION IF EXISTS update_child_ticket_title();

COMMIT;
