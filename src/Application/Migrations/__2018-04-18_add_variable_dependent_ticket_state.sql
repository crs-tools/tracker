BEGIN;

SET ROLE TO postgres;

ALTER TABLE tbl_project
  ADD COLUMN 
  IF NOT EXISTS -- NOTE: this works only with Postgres >= 9.6
  dependent_ticket_trigger_state enum_ticket_state
  NOT NULL DEFAULT 'released';

CREATE OR REPLACE VIEW view_serviceable_tickets AS 
	SELECT 
		t.*,
		pstart.value::timestamp with time zone AS time_start,
		pstart.value::timestamp with time zone + pdur.value::time without time zone::interval AS time_end,
		proom.value as room,
		t.ticket_state_next AS next_state,
		t.service_executable AS next_state_service_executable
	FROM
		tbl_ticket t
	JOIN
		tbl_ticket pt ON pt.id = t.parent_id
	LEFT JOIN 
		tbl_ticket_property pdur ON pdur.ticket_id = COALESCE(t.parent_id, t.id) AND pdur.name = 'Fahrplan.Duration'::ltree
	LEFT JOIN 
		tbl_ticket_property pstart ON pstart.ticket_id = COALESCE(t.parent_id, t.id) AND pstart.name = 'Fahrplan.DateTime'::ltree
	LEFT JOIN
		tbl_ticket_property proom ON proom.ticket_id = COALESCE(t.parent_id,t.id) AND proom.name = 'Fahrplan.Room'::ltree
	LEFT JOIN
		tbl_project pj ON pj.id = t.project_id
	LEFT JOIN
		tbl_project_encoding_profile pep ON pep.project_id = pj.id AND pep.encoding_profile_version_id = t.encoding_profile_version_id
	LEFT JOIN
		tbl_ticket_state state ON state.ticket_type = t.ticket_type AND state.ticket_state = t.ticket_state
	LEFT JOIN
		tbl_ticket_state wantedstate ON wantedstate.ticket_type = 'encoding' AND wantedstate.ticket_state = pj.dependent_ticket_trigger_state
	LEFT JOIN
		tbl_ticket_state masterstate ON
			masterstate.ticket_type = 'encoding' AND
			masterstate.ticket_state = COALESCE(ticket_depending_encoding_ticket_state(t.id),pj.dependent_ticket_trigger_state)

	WHERE
		pj.read_only = false AND
		t.service_executable = true AND
		t.ticket_type != 'meta' AND
		pt.ticket_state = 'staged' AND
		pt.failed = false AND
		t.failed = false AND
		COALESCE(pep.priority, 1) > 0 AND
		masterstate.sort >= wantedstate.sort
	ORDER BY
		ticket_priority(t.id) DESC;

CREATE OR REPLACE FUNCTION ticket_depending_encoding_ticket_state_satisfied(param_ticket_id bigint)
  RETURNS boolean AS
  $$
DECLARE
	state enum_ticket_state;
	satisfaction boolean;
BEGIN
	SELECT
		t2.ticket_state INTO state
	FROM
		tbl_ticket t
	JOIN
		tbl_encoding_profile_version epv ON epv.id = t.encoding_profile_version_id
	JOIN
		tbl_encoding_profile ep ON epv.encoding_profile_id = ep.id
	JOIN
		tbl_encoding_profile ep2 ON ep2.id = ep.depends_on
	JOIN
		tbl_encoding_profile_version epv2 ON epv2.encoding_profile_id = ep2.id
	JOIN
		tbl_ticket t2 ON t2.encoding_profile_version_id = epv2.id AND t2.parent_id = t.parent_id
	WHERE
		t.id = param_ticket_id;

	SELECT
		s1.sort >= s2.sort INTO satisfaction
	FROM
		tbl_ticket t
	JOIN
		tbl_project p ON t.project_id = p.id
	JOIN
		tbl_ticket_state s1 ON t.ticket_type = 'encoding' AND t.ticket_state = s1.ticket_state
	JOIN
		tbl_ticket_state s2 ON t.ticket_type = 'encoding' AND p.dependent_ticket_trigger_state = s2.ticket_state
	WHERE
		t.id = param_ticket_id;

	RETURN satisfaction;
END
$$
LANGUAGE plpgsql;

COMMIT;


DO language plpgsql $$

BEGIN
  RAISE WARNING 'DO NOT FORGET: it is very likely that you have to give GRANT on the recreated view_serviceable_tickets to your tracker DB user after executing this script!';
END
$$;

