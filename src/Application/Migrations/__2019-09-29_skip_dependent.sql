BEGIN;
	
SET ROLE TO postgres;

ALTER TABLE tbl_project_ticket_state
  ADD COLUMN skip_on_dependent boolean NOT NULL DEFAULT false;

ALTER TABLE tbl_ticket_state
  ADD COLUMN skippable_on_dependent boolean NOT NULL DEFAULT false;


UPDATE tbl_ticket_state
SET skippable_on_dependent = true
WHERE ticket_type = 'encoding'
  AND ticket_state IN ('checking', 'checked', 'postprocessing', 'postprocessed', 'ready to release');

DROP FUNCTION IF EXISTS ticket_state_next(bigint);
DROP FUNCTION IF EXISTS ticket_state_next(bigint, enum_ticket_type);
DROP FUNCTION IF EXISTS ticket_state_next(bigint, enum_ticket_type, enum_ticket_state);

DROP FUNCTION IF EXISTS ticket_state_previous(bigint);
DROP FUNCTION IF EXISTS ticket_state_previous(bigint, enum_ticket_type);
DROP FUNCTION IF EXISTS ticket_state_previous(bigint, enum_ticket_type, enum_ticket_state);

DROP FUNCTION IF EXISTS ticket_state_commence(bigint, enum_ticket_type);
DROP FUNCTION IF EXISTS ticket_state_commence(bigint);


CREATE OR REPLACE FUNCTION update_ticket_next_state()
  RETURNS trigger AS
$BODY$
  DECLARE
   next_state record;
  BEGIN
    next_state := ticket_state_next(NEW.id, NEW.ticket_state);

    NEW.ticket_state_next := next_state.ticket_state;
    NEW.service_executable := next_state.service_executable;

    RETURN NEW;
  END
$BODY$
LANGUAGE plpgsql VOLATILE;


CREATE OR REPLACE FUNCTION update_all_tickets_progress_and_next_state(param_project_id bigint)
  RETURNS VOID AS
$BODY$
  BEGIN

    UPDATE tbl_ticket t SET
      (progress, ticket_state_next, service_executable)
        = (tp, (n).ticket_state, (n).service_executable)
    FROM (
      SELECT id, ticket_state_next(t2.id) AS n, ticket_progress(t2.id) as tp
      FROM tbl_ticket t2
      WHERE t2.project_id = param_project_id AND param_project_id IS NOT NULL
    ) AS x
    WHERE t.id = x.id;

  END;
$BODY$
LANGUAGE plpgsql VOLATILE;


CREATE OR REPLACE FUNCTION ticket_state_next(param_ticket_id bigint, param_ticket_state enum_ticket_state DEFAULT NULL)
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
		tbl_ticket_state ts_this ON ts_this.ticket_type = t.ticket_type AND ts_this.ticket_state = COALESCE(param_ticket_state, t.ticket_state)
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
			INNER JOIN tbl_encoding_profile_version epv ON epv.id = t.encoding_profile_version_id
			INNER JOIN tbl_encoding_profile ep ON ep.id = epv.encoding_profile_id
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

CREATE OR REPLACE FUNCTION ticket_state_previous(param_ticket_id bigint, param_ticket_state enum_ticket_state DEFAULT NULL)
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
		tbl_ticket_state ts_this ON ts_this.ticket_type = t.ticket_type AND ts_this.ticket_state = COALESCE(param_ticket_state, t.ticket_state)
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


CREATE OR REPLACE FUNCTION ticket_state_commence(param_ticket_id bigint)
  RETURNS enum_ticket_state AS
$$
DECLARE
  var_project_id bigint;
  var_ticket_type enum_ticket_type;
  ret enum_ticket_state;
  next_state record;
BEGIN
  SELECT
    project_id, ticket_type INTO var_project_id, var_ticket_type
  FROM
    tbl_ticket t
  WHERE
    t.id = param_ticket_id;

  -- special case: meta ticket, since it has no serviceable states
  IF var_ticket_type = 'meta' THEN
    RETURN 'staged'::enum_ticket_state;
  END IF;

  ret := (SELECT ticket_state_initial(var_project_id, var_ticket_type));

  WHILE ret IS NOT NULL LOOP
    SELECT * INTO next_state FROM ticket_state_next(param_ticket_id, ret);
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
