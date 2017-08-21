BEGIN;

SET ROLE TO postgres;

DROP FUNCTION ticket_state_next(bigint, enum_ticket_type, enum_ticket_state);
CREATE OR REPLACE FUNCTION ticket_state_next(param_project_id bigint, param_ticket_type enum_ticket_type, param_ticket_state enum_ticket_state, param_ticket_id bigint default NULL)
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
		ts1.sort ASC
	LIMIT 1;
	IF NOT FOUND THEN
		RETURN QUERY SELECT NULL::enum_ticket_state, false;
	END IF;
END
$$
LANGUAGE plpgsql;

DROP FUNCTION ticket_state_previous(bigint, enum_ticket_type, enum_ticket_state);
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

DROP FUNCTION ticket_state_commence(bigint, enum_ticket_type);
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
		SELECT * INTO next_state FROM ticket_state_next(param_project_id, param_ticket_type, ret, param_ticket_id);
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

CREATE OR REPLACE FUNCTION update_ticket_next_state()
	RETURNS trigger AS
$BODY$
DECLARE
	next_state record;
BEGIN
	next_state := ticket_state_next(NEW.project_id, NEW.ticket_type, NEW.ticket_state, NEW.id);

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
				 SELECT id, ticket_state_next(t2.project_id, t2.ticket_type, t2.ticket_state, t2.id) AS n, ticket_progress(t2.id) as tp
				 FROM tbl_ticket t2
				 WHERE t2.project_id = param_project_id AND param_project_id IS NOT NULL
			 ) AS x
	WHERE t.id = x.id;

END;
$BODY$
LANGUAGE plpgsql VOLATILE;

ALTER TABLE tbl_project_ticket_state ADD COLUMN skip_on_dependent BOOLEAN NOT NULL DEFAULT FALSE;

COMMIT;