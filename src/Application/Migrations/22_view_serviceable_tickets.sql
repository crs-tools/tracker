BEGIN;

SET ROLE TO postgres;

DROP VIEW IF EXISTS view_serviceable_tickets;

CREATE OR REPLACE VIEW view_serviceable_tickets AS 
	SELECT 
		t.*,
		pstart.value::timestamp with time zone AS time_start,
		pstart.value::timestamp with time zone + pdur.value::time without time zone::interval AS time_end,
		proom.value as room,
		t.ticket_state_next AS next_state,
		t.service_executable AS next_state_service_executable,
		CASE WHEN t.parent_id IS NOT NULL THEN
			pt.priority * t.priority *
			COALESCE(extract(EPOCH FROM CURRENT_TIMESTAMP) / extract(EPOCH FROM pstart.value::timestamp with time zone), 1) *
			COALESCE(pep.priority, 1)
		ELSE
			t.priority * COALESCE(extract(EPOCH FROM CURRENT_TIMESTAMP) / extract(EPOCH FROM pstart.value::timestamp with time zone), 1)
		END as calculated_priority
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
		tbl_encoding_profile_version epv ON epv.id = t.encoding_profile_version_id
	LEFT JOIN
		tbl_encoding_profile ep ON epv.encoding_profile_id = ep.id
	LEFT JOIN
		tbl_encoding_profile ep2 ON ep2.id = ep.depends_on
	LEFT JOIN LATERAL
		(
			SELECT array_agg(id) as ids FROM tbl_encoding_profile_version
			GROUP BY encoding_profile_id
			HAVING encoding_profile_id = ep2.id
		) epv2 ON true
	LEFT JOIN
		tbl_ticket tmaster ON
			tmaster.parent_id = t.parent_id AND
			tmaster.encoding_profile_version_id = ANY (epv2.ids)
	LEFT JOIN
		tbl_ticket_state masterstate ON
			((tmaster.id IS NULL AND masterstate.ticket_type = 'encoding')
			OR masterstate.ticket_type = tmaster.ticket_type) AND
			masterstate.ticket_state = COALESCE(tmaster.ticket_state, pj.dependent_ticket_trigger_state)

	WHERE
		pj.read_only = false AND
		t.ticket_type IN ('recording','encoding','ingest') AND
		pt.ticket_state = 'staged' AND
		pt.failed = false AND
		t.failed = false AND
		COALESCE(tmaster.failed, false) = false AND
		COALESCE(pep.priority, 1) > 0 AND
		masterstate.sort >= wantedstate.sort
	ORDER BY
		calculated_priority DESC;

COMMIT;
