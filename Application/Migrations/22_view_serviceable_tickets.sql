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
  LEFT JOIN
      tbl_ticket_property p ON p.ticket_id = COALESCE(t.parent_id,t.id) AND p.name = 'Fahrplan.Duration'
  ORDER BY
		ticket_priority(t.id) DESC;
COMMIT;