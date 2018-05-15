BEGIN;

SET ROLE TO postgres;

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

COMMIT;
