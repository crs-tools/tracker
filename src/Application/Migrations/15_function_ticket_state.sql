BEGIN;

SET ROLE TO postgres;

CREATE OR REPLACE FUNCTION ticket_state_next(param_project_id bigint, param_ticket_type enum_ticket_type, param_ticket_state enum_ticket_state)
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
		tbl_ticket_state ts2 ON ts1.ticket_type = ts2.ticket_type AND ts1.sort > ts2.sort
	WHERE
		pts.project_id = param_project_id AND
		ts2.ticket_type = param_ticket_type AND
		ts2.ticket_state = param_ticket_state
  ORDER BY
    ts1.sort ASC
	LIMIT 1;
  IF NOT FOUND THEN
    RETURN QUERY SELECT NULL::enum_ticket_state, false;
  END IF;
END
$$
LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION ticket_state_previous(param_project_id bigint, param_ticket_type enum_ticket_type, param_ticket_state enum_ticket_state)
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
		ts2.ticket_state = param_ticket_state
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

-- function also resets handle_id and failed flag
CREATE OR REPLACE FUNCTION ticket_reset(param_ticket_id bigint, param_ticket_types enum_ticket_type[] DEFAULT array[]::enum_ticket_type[])
  RETURNS TABLE (ticket_id bigint,  from_state enum_ticket_state, to_state enum_ticket_state, follows_meta_ticket boolean, follows_encoding_ticket boolean) AS
$$
DECLARE
  ticket tbl_ticket%rowtype;
  inital_state enum_ticket_state;
BEGIN
  --first get the full given ticket
  SELECT * INTO ticket FROM tbl_ticket where id = param_ticket_id;

  IF NOT FOUND THEN
    RAISE WARNING 'ticket with id % does not exist', param_ticket_id;
    RETURN;
  END IF;

  --reset the given ticket to inital state, but only if it matches the ticket type filter
  IF array_length(param_ticket_types, 1) IS NULL OR (array_position(param_ticket_types, ticket.ticket_type) IS NOT NULL) THEN
    inital_state := ticket_state_initial(ticket.project_id, ticket.ticket_type);
    UPDATE tbl_ticket SET (failed, handle_id) = (FALSE, NULL) WHERE id = ticket.id;
    IF inital_state <> ticket.ticket_state THEN
      UPDATE tbl_ticket SET ticket_state=inital_state WHERE id = param_ticket_id;
      ticket_id := ticket.id;
      from_state := ticket.ticket_state;
      to_state := inital_state;
      follows_meta_ticket := FALSE;
      follows_encoding_ticket := FALSE;
      RETURN NEXT;
    END IF;
  END IF;

  --reset the children of the given ticket
  FOR ticket IN
  SELECT * FROM tbl_ticket WHERE parent_id = param_ticket_id AND (array_length(param_ticket_types, 1) IS NULL OR (array_position(param_ticket_types, ticket_type) IS NOT NULL))
  LOOP
    inital_state := ticket_state_initial(ticket.project_id, ticket.ticket_type);
    UPDATE tbl_ticket SET (failed, handle_id) = (FALSE, NULL) WHERE id = ticket.id;
    IF inital_state <> ticket.ticket_state THEN
      UPDATE tbl_ticket SET ticket_state = inital_state WHERE id = ticket.id;
      ticket_id := ticket.id;
      from_state := ticket.ticket_state;
      to_state := inital_state;
      follows_meta_ticket := TRUE;
      follows_encoding_ticket := FALSE;
      RETURN NEXT;
    END IF;
  END LOOP;

  --reset all tickets with encoding profiles depending on given ticket's profile
  FOR ticket IN
    SELECT
      t2.*
    FROM
      tbl_ticket t2
    JOIN
      tbl_encoding_profile_version epv2 ON t2.encoding_profile_version_id = epv2.id
    JOIN (
      SELECT
        encoding_profile_all_dependees(epv.encoding_profile_id) AS id,
        tt.parent_id
      FROM
        tbl_ticket tt
      JOIN
        tbl_encoding_profile_version epv ON tt.encoding_profile_version_id = epv.id
      WHERE
        tt.id = param_ticket_id AND
        (array_length(param_ticket_types, 1) IS NULL OR (array_position(param_ticket_types, tt.ticket_type) IS NOT NULL))
    ) pd ON pd.id = epv2.encoding_profile_id AND t2.parent_id = pd.parent_id
  LOOP
    inital_state := ticket_state_initial(ticket.project_id, ticket.ticket_type);
    UPDATE tbl_ticket SET (failed, handle_id) = (FALSE, NULL) WHERE id = ticket.id;
    IF inital_state <> ticket.ticket_state THEN
      UPDATE tbl_ticket SET ticket_state = inital_state WHERE id = ticket.id;
      ticket_id := ticket.id;
      from_state := ticket.ticket_state;
      to_state := inital_state;
      follows_meta_ticket := FALSE;
      follows_encoding_ticket := TRUE;
      RETURN NEXT;
    END IF;
  END LOOP;

END
$$
LANGUAGE plpgsql;

COMMIT;