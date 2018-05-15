BEGIN;

SET ROLE TO postgres;

CREATE INDEX tbl_ticket_parent_id_idx ON tbl_ticket USING hash(parent_id);
CREATE INDEX tbl_ticket_view_servicable_idx ON tbl_ticket USING btree(failed, service_executable, ticket_type);

COMMIT;

BEGIN;

ALTER TABLE tbl_project
  RENAME COLUMN dependent_ticket_trigger_state
  TO dependee_ticket_trigger_state;

DROP VIEW IF EXISTS view_serviceable_tickets;
CREATE OR REPLACE VIEW view_serviceable_tickets AS 
	SELECT 
		t.*,
		pstart.value::timestamp with time zone AS time_start,
		pstart.value::timestamp with time zone + pdur.value::time without time zone::interval AS time_end,
		proom.value AS room,
		parent.priority * t.priority *
			COALESCE(
				extract(EPOCH FROM CURRENT_TIMESTAMP) / 
				extract(EPOCH FROM pstart.value::timestamp with time zone)
			, 1) * COALESCE(pep.priority, 1) AS calculated_priority
	FROM
		tbl_ticket t
	JOIN
		tbl_ticket parent ON parent.id = t.parent_id
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
		tbl_ticket_state configured_trigger_state ON
			configured_trigger_state.ticket_type = 'encoding' AND
			configured_trigger_state.ticket_state = pj.dependee_ticket_trigger_state
	LEFT JOIN
		tbl_encoding_profile_version epv ON epv.id = t.encoding_profile_version_id
	LEFT JOIN
		tbl_encoding_profile ep ON epv.encoding_profile_id = ep.id
	LEFT JOIN
		tbl_encoding_profile dependee_profile ON dependee_profile.id = ep.depends_on
	-- Using lateral join to split evaluation at this point. The tables and
	-- corresponding WHERE clauses up to this JOIN do already filter out most 
	-- of the ticket candidates so that the following JOINs do not exhaust ressources.
	LEFT JOIN LATERAL
		-- Since different projects can have different versions of encoding profiles
		-- assigned, no straight join is possible here. Selecting array of ids here
		-- to be used in following JOIN clause.
		(
			SELECT array_agg(id) as ids FROM tbl_encoding_profile_version
			GROUP BY encoding_profile_id
			HAVING encoding_profile_id = dependee_profile.id
		) dependee_profile_version ON true
	LEFT JOIN
		tbl_ticket dependee_ticket ON
			dependee_ticket.parent_id = t.parent_id AND
			dependee_ticket.encoding_profile_version_id = ANY (dependee_profile_version.ids)
	LEFT JOIN
		tbl_ticket_state dependee_ticket_state ON
			-- The case of empty dependee_ticket row is handled by the COALESCE in the WHERE clause.
			dependee_ticket_state.ticket_type = dependee_ticket.ticket_type AND
			dependee_ticket_state.ticket_state = dependee_ticket.ticket_state

	WHERE
		pj.read_only = false AND
		t.ticket_type IN ('recording','encoding','ingest') AND
		parent.ticket_state = 'staged' AND
		parent.failed = false AND
		t.failed = false AND
		COALESCE(dependee_ticket.failed, false) = false AND
		COALESCE(pep.priority, 1) > 0 AND
		COALESCE(dependee_ticket_state.sort >= configured_trigger_state.sort, true)
	ORDER BY
		calculated_priority DESC;

DROP FUNCTION IF EXISTS ticket_depending_encoding_ticket_state(bigint);
DROP FUNCTION IF EXISTS ticket_depending_encoding_ticket_state_satisfied(bigint);


CREATE OR REPLACE FUNCTION ticket_dependee_ticket_state(param_depender_ticket_id bigint)
  RETURNS enum_ticket_state AS
  $$
DECLARE
	state enum_ticket_state;
BEGIN
	SELECT
		dependee.ticket_state INTO state
	FROM
		tbl_ticket depender
	JOIN
		tbl_encoding_profile_version epv ON epv.id = depender.encoding_profile_version_id
	JOIN
		tbl_encoding_profile ep ON epv.encoding_profile_id = ep.id
	JOIN
		tbl_encoding_profile ep2 ON ep2.id = ep.depends_on
	JOIN
		tbl_encoding_profile_version epv2 ON epv2.encoding_profile_id = ep2.id
	JOIN
		tbl_ticket dependee ON dependee.encoding_profile_version_id = epv2.id AND dependee.parent_id = depender.parent_id
	WHERE
		depender.id = param_depender_ticket_id;
	RETURN state;
END
$$
LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION ticket_dependee_ticket_state_satisfied(param_depender_ticket_id bigint)
  RETURNS boolean AS
  $$
DECLARE
	satisfaction boolean;
	dependee_state enum_ticket_state;
BEGIN
	SELECT ticket_dependee_ticket_state(param_depender_ticket_id)
		INTO dependee_state;

	SELECT
		dependee_ticket_state.sort >= configured_trigger_state.sort INTO satisfaction
	FROM
		tbl_ticket depender_ticket
	JOIN
		tbl_project project ON depender_ticket.project_id = project.id
	JOIN
		-- if given ticket is no encoding ticket, function will return NULL
		tbl_ticket_state dependee_ticket_state ON
			depender_ticket.ticket_type = 'encoding' AND
			dependee_ticket_state.ticket_state = dependee_state
	JOIN
		tbl_ticket_state configured_trigger_state ON
			depender_ticket.ticket_type = 'encoding' AND
			configured_trigger_state.ticket_state = project.dependee_ticket_trigger_state
	WHERE
		depender_ticket.id = param_depender_ticket_id;

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

