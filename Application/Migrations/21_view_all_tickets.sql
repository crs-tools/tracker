BEGIN;

SET ROLE TO postgres;

DROP VIEW IF EXISTS view_all_tickets;

CREATE OR REPLACE VIEW view_all_tickets AS 
	SELECT 
		t.*, 
		pstart.value::timestamp with time zone AS time_start,
		pstart.value::timestamp with time zone + pdur.value::time without time zone::interval AS time_end,
		t.progress AS ticket_progress,
		(SELECT tr.ticket_state FROM tbl_ticket tr WHERE tr.ticket_type = 'recording'::enum_ticket_type AND (tr.parent_id = t.id OR tr.parent_id = t.parent_id) LIMIT 1) AS recording_ticket_state
	FROM
		tbl_ticket t
	LEFT JOIN
		tbl_ticket_property pdur ON pdur.ticket_id = t.id AND pdur.name = 'Fahrplan.Duration'::ltree
	LEFT JOIN
		tbl_ticket_property pstart ON pstart.ticket_id = t.id AND pstart.name = 'Fahrplan.DateTime'::ltree
	ORDER BY
		t.project_id ASC, time_start ASC, t.parent_id ASC;

COMMIT;
