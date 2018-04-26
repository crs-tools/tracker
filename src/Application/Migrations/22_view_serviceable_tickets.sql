BEGIN;

SET ROLE TO postgres;

DROP VIEW IF EXISTS view_serviceable_tickets;

CREATE OR REPLACE VIEW view_serviceable_tickets AS 
	SELECT 
		t.*,
		pstart.value::timestamp with time zone AS time_start,
		pstart.value::timestamp with time zone + pdur.value::time without time zone::interval AS time_end,
		(SELECT value FROM tbl_ticket_property WHERE ticket_id = COALESCE(t.parent_id,t.id) AND name = 'Fahrplan.Room') as room,
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
		tbl_project pj ON pj.id = t.project_id
	LEFT JOIN tbl_project_encoding_profile pep ON pep.project_id = pj.id AND pep.encoding_profile_version_id = t.encoding_profile_version_id

	WHERE
		t.ticket_type != 'meta' AND
		pt.ticket_state = 'staged' AND
		pt.failed = false AND
		t.failed = false AND
		COALESCE(pep.priority, 1) > 0 AND
		COALESCE(ticket_depending_encoding_ticket_state(t.id),pj.dependent_ticket_trigger_state) >= pj.dependent_ticket_trigger_state
	ORDER BY
		ticket_priority(t.id) DESC;

COMMIT;
