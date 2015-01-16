BEGIN;

SET ROLE TO postgres;

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
			ticket_state_initial(param_project_id, 'encoding') AS ticket_state, 
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
			ticket_state_initial(param_project_id, 'encoding') AS ticket_state, 
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

COMMIT;
