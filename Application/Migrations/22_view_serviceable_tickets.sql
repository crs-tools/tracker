BEGIN;

SET ROLE TO postgres;

CREATE OR REPLACE VIEW view_serviceable_tickets AS 
	SELECT 
		t.*,
		to_timestamp(ticket_fahrplan_starttime(COALESCE(t.parent_id,t.id))) AS time_start,
		(to_timestamp(ticket_fahrplan_starttime(COALESCE(t.parent_id,t.id))) + p.value::time)::timestamp AS time_end,
		(SELECT value FROM tbl_ticket_property WHERE ticket_id = COALESCE(t.parent_id,t.id) AND name = 'Fahrplan.Room') as room,
		(SELECT ticket_state FROM ticket_state_next(t.project_id, t.ticket_type, t.ticket_state)) AS next_state,
		(SELECT service_executable FROM ticket_state_next(t.project_id, t.ticket_type, t.ticket_state)) AS next_state_service_executable
	FROM
		tbl_ticket t
	JOIN
		tbl_ticket pt ON pt.id = t.parent_id
	LEFT JOIN
		tbl_ticket_property p ON p.ticket_id = COALESCE(t.parent_id,t.id) AND p.name = 'Fahrplan.Duration'
	WHERE
		(t.ticket_state = 'staged' OR pt.ticket_state = 'staged') AND 
		ticket_priority(t.id) > 0 AND 
		COALESCE(ticket_depending_encoding_ticket_state(t.id),'released') = 'released'
	ORDER BY
		ticket_priority(t.id) DESC;

COMMIT;