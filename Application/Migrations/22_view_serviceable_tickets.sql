BEGIN;

SET ROLE TO postgres;

CREATE OR REPLACE VIEW view_serviceable_tickets AS 
	SELECT 
		t.*, 
		(SELECT ticket_state FROM ticket_state_next(t.project_id, t.ticket_type, t.ticket_state)) AS next_state,
		(SELECT service_executable FROM ticket_state_next(t.project_id, t.ticket_type, t.ticket_state)) AS next_state_service_executable
	FROM
		tbl_ticket t
	ORDER BY
		ticket_priority(t.id) DESC;
COMMIT;