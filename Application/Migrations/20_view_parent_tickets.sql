BEGIN;

SET ROLE TO postgres;

DROP VIEW IF EXISTS view_parent_tickets;

CREATE OR REPLACE VIEW view_parent_tickets AS
	SELECT 
		t.*, 
		pstart.value::timestamp with time zone AS time_start,
		pstart.value::timestamp with time zone + pdur.value::time without time zone::interval AS time_end,
		t.progress AS ticket_progress 
	FROM 
		tbl_ticket t 
	LEFT JOIN 
		tbl_ticket_property pdur ON pdur.ticket_id = t.id AND pdur.name = 'Fahrplan.Duration'
	LEFT JOIN
		tbl_ticket_property pstart ON pstart.ticket_id = t.id AND pstart.name = 'Fahrplan.DateTime'::ltree
	WHERE 
		t.ticket_type = 'meta';

COMMIT;
