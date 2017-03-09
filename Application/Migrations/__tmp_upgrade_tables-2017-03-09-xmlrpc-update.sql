CREATE OR REPLACE FUNCTION ticket_state_commence(param_project_id bigint, param_ticket_type enum_ticket_type)
  RETURNS enum_ticket_state AS
$$
DECLARE
  ret enum_ticket_state;
  next_state record;
BEGIN
  -- special case: meta ticket, since it has no serviceable states
  IF param_ticket_type = 'meta' THEN
    RETURN 'staged'::enum_ticket_state;
  END IF;

  ret := (SELECT ticket_state_initial(param_project_id, param_ticket_type));

  WHILE ret IS NOT NULL LOOP
    SELECT * INTO next_state FROM ticket_state_next(param_project_id, param_ticket_type, ret);
    IF NOT FOUND THEN
      ret := NULL;
      EXIT;
    END IF;

    -- exit, if serviceable state is found
    EXIT WHEN next_state.service_executable IS TRUE;

    -- otherwise set current state as possible commence state
    ret := next_state.ticket_state;
  END LOOP;

  RETURN ret;
END
$$
LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION create_missing_encoding_ticket(param_ticket_id bigint, param_encoding_profile_id bigint) RETURNS integer AS $$
DECLARE
	encoding_ticket_id integer;
BEGIN
	SELECT t2.id INTO encoding_ticket_id
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
		t1.id = param_ticket_id AND
		t1.ticket_type = 'meta' AND
		epv.encoding_profile_id = param_encoding_profile_id;

	IF encoding_ticket_id IS NULL THEN
		INSERT INTO tbl_ticket (parent_id, project_id, fahrplan_id, priority, ticket_type, ticket_state, encoding_profile_version_id)
			(SELECT
				 t1.id as parent_id,
				 t1.project_id,
				 t1.fahrplan_id,
				 pep.priority,
				 'encoding' as ticket_type,
				 ticket_state_initial(t1.project_id, 'encoding') AS ticket_state,
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
				 t1.id = param_ticket_id AND
				 t1.ticket_type = 'meta' AND
				 t2.id IS NULL AND
				 epv.encoding_profile_id = param_encoding_profile_id
			 ORDER BY t1.id ASC, ep.id ASC)
		RETURNING id INTO encoding_ticket_id;
	END IF;

	return encoding_ticket_id;
END;
$$ LANGUAGE plpgsql;