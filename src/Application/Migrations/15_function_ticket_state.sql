BEGIN;

SET ROLE TO postgres;

CREATE OR REPLACE FUNCTION ticket_state_next(param_ticket_id bigint)
  RETURNS TABLE(ticket_state enum_ticket_state, service_executable boolean) AS
  $$
DECLARE
BEGIN
	RETURN QUERY
	SELECT
		pts.ticket_state, pts.service_executable
	FROM
		tbl_ticket t
	JOIN
		tbl_ticket_state ts_this ON ts_this.ticket_type = t.ticket_type AND ts_this.ticket_state = t.ticket_state
	JOIN
		tbl_project_ticket_state pts ON pts.project_id = t.project_id AND pts.ticket_type = t.ticket_type
	JOIN
		tbl_ticket_state ts_other ON ts_other.ticket_type = pts.ticket_type AND ts_other.ticket_state = pts.ticket_state
	WHERE
		t.id = param_ticket_id AND
		ts_other.sort > ts_this.sort AND
		(pts.skip_on_dependent = FALSE OR
		( /* is master encoding ticket */
			SELECT ep.depends_on
			FROM tbl_ticket t
			JOIN tbl_encoding_profile_version epv ON epv.id = t.encoding_profile_version_id
			JOIN tbl_encoding_profile ep ON ep.id = epv.encoding_profile_id
			WHERE t.id = param_ticket_id
		) IS NULL )
	ORDER BY ts_other.sort ASC
	LIMIT 1;
  IF NOT FOUND THEN
    RETURN QUERY SELECT NULL::enum_ticket_state, false;
  END IF;
END
$$
LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION ticket_state_previous(param_ticket_id bigint)
  RETURNS TABLE(ticket_state enum_ticket_state, service_executable boolean) AS
  $$
DECLARE
BEGIN
	RETURN QUERY
	SELECT
		pts.ticket_state, pts.service_executable
	FROM
		tbl_ticket t
	JOIN
		tbl_ticket_state ts_this ON ts_this.ticket_type = t.ticket_type AND ts_this.ticket_state = t.ticket_state
	JOIN
		tbl_project_ticket_state pts ON pts.project_id = t.project_id AND pts.ticket_type = t.ticket_type
	JOIN
		tbl_ticket_state ts_other ON ts_other.ticket_type = pts.ticket_type AND ts_other.ticket_state = pts.ticket_state
	WHERE
		t.id = param_ticket_id AND
		ts_other.sort < ts_this.sort AND
		(pts.skip_on_dependent = FALSE OR
		( /* is master encoding ticket */
			SELECT ep.depends_on
			FROM tbl_ticket t
			JOIN tbl_encoding_profile_version epv ON epv.id = t.encoding_profile_version_id
			JOIN tbl_encoding_profile ep ON ep.id = epv.encoding_profile_id
			WHERE t.id = param_ticket_id
		) IS NULL )
	ORDER BY ts_other.sort DESC
	LIMIT 1;
  IF NOT FOUND THEN
    RETURN QUERY SELECT NULL::enum_ticket_state, false;
  END IF;
END
$$
LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION ticket_state_previous(param_project_id bigint, param_ticket_type enum_ticket_type, param_ticket_state enum_ticket_state, param_ticket_id bigint default NULL)
  RETURNS TABLE(ticket_state enum_ticket_state, service_executable boolean) AS
  $$
DECLARE
BEGIN
	RETURN QUERY
	SELECT
		pts.ticket_state, pts.service_executable
	FROM
		tbl_ticket_state ts1
	JOIN
		tbl_project_ticket_state pts ON pts.ticket_type = ts1.ticket_type AND pts.ticket_state = ts1.ticket_state
	JOIN
		tbl_ticket_state ts2 ON ts1.ticket_type = ts2.ticket_type AND ts1.sort < ts2.sort
	WHERE
		pts.project_id = param_project_id AND
		ts2.ticket_type = param_ticket_type AND
		ts2.ticket_state = param_ticket_state AND
		(pts.skip_on_dependent = false OR
		( /* is master encoding ticket */
			SELECT ep.depends_on
			FROM tbl_ticket t
			JOIN tbl_encoding_profile_version epv ON epv.id = t.encoding_profile_version_id
			JOIN tbl_encoding_profile ep ON ep.id = epv.encoding_profile_id
			WHERE t.id = param_ticket_id
		) IS NULL
		)
  ORDER BY
    ts1.sort DESC
	LIMIT 1;
  IF NOT FOUND THEN
    RETURN QUERY SELECT NULL::enum_ticket_state, false;
  END IF;
END
$$
LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION ticket_state_initial(param_project_id bigint, param_ticket_type enum_ticket_type)
  RETURNS enum_ticket_state AS
  $$
DECLARE
  ret enum_ticket_state;
BEGIN
  ret := 
    (SELECT ts.ticket_state
    FROM
      tbl_ticket_state ts
    JOIN
      tbl_project_ticket_state pts ON pts.ticket_type = ts.ticket_type AND pts.ticket_state = ts.ticket_state
    WHERE
      pts.project_id = param_project_id AND
      ts.ticket_type = param_ticket_type AND
      ts.sort > 0
    ORDER BY ts.sort ASC
    LIMIT 1);

  IF ret IS NULL THEN
    ret := (SELECT ts.ticket_state
            FROM
              tbl_ticket_state ts
            WHERE
              ts.sort = 0 AND
              ts.ticket_type = param_ticket_type);
  END IF;

  IF ret IS NULL THEN
    RAISE WARNING 'No default ticket state for project!';
    ret := NULL::enum_ticket_state;
  END IF;

  RETURN ret;
END
$$
LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION ticket_state_commence(param_project_id bigint, param_ticket_type enum_ticket_type, param_ticket_id bigint)
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
    SELECT * INTO next_state FROM ticket_state_next(param_ticket_id);
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

COMMIT;
