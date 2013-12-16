BEGIN;

SET ROLE TO postgres;

CREATE OR REPLACE VIEW view_parent_tickets AS
	SELECT 
		t.*, 
		to_timestamp(ticket_fahrplan_starttime(t.id)) as time_start, 
		(to_timestamp(ticket_fahrplan_starttime(t.id)) + p.value::time)::timestamp as time_end, 
		ticket_progress(t.id) AS ticket_progress 
	FROM 
		tbl_ticket t 
	LEFT JOIN 
		tbl_ticket_property p ON p.ticket_id = t.id AND p.name = 'Fahrplan.Duration' 
	WHERE 
		t.ticket_type = 'meta';

COMMIT;