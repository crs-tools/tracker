CREATE OR REPLACE FUNCTION create_missing_recording_tickets(param_project_id bigint) RETURNS integer AS $$
  DECLARE
	row_count integer;
  BEGIN
	row_count := 0;

	IF NOT EXISTS
		(SELECT 1
		FROM tbl_project_ticket_state s
		WHERE s.project_id = param_project_id AND s.ticket_type = 'recording'::enum_ticket_type)
	THEN RETURN row_count;
	END IF;

	INSERT INTO tbl_ticket (parent_id, project_id, fahrplan_id, ticket_type, ticket_state)
		(SELECT
			t1.id as parent_id,
			t1.project_id,
			t1.fahrplan_id,
			'recording' as ticket_type,
			ticket_state_initial(param_project_id, 'recording') AS ticket_state
		FROM
			tbl_ticket t1
		LEFT JOIN
			tbl_ticket t2 ON t2.parent_id = t1.id AND t2.ticket_type = 'recording'
		WHERE
			t1.ticket_type = 'meta' AND
			t1.project_id = param_project_id
		GROUP BY 
			t1.id 
		HAVING COUNT(t2.id) = 0
		)
	;

	GET DIAGNOSTICS row_count = ROW_COUNT;
	RETURN row_count;
  END;
$$ LANGUAGE plpgsql;

