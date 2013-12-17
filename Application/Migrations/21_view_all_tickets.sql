BEGIN;

SET ROLE TO postgres;

CREATE OR REPLACE VIEW view_all_tickets AS 
	SELECT 
		t.*, 
		to_timestamp(ticket_fahrplan_starttime(t.id)) AS time_start, 
		(to_timestamp(ticket_fahrplan_starttime(t.id)) + p.value::time)::timestamp AS time_end,
		ticket_progress(t.id) AS ticket_progress,
		(SELECT tr.ticket_state FROM tbl_ticket tr WHERE tr.ticket_type = 'recording'::enum_ticket_type AND (tr.parent_id = t.id OR tr.parent_id = t.parent_id) LIMIT 1) AS recording_ticket_state
	FROM
		tbl_ticket t
	LEFT JOIN
		tbl_ticket_property p ON p.ticket_id = t.id AND p.name = 'Fahrplan.Duration'
	ORDER BY
		t.project_id ASC, time_start ASC, t.parent_id ASC;

COMMIT;