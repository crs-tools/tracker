BEGIN;

CREATE OR REPLACE FUNCTION getTicketProgress(param_ticket_id bigint) RETURNS float AS $$
DECLARE
  progress float;
BEGIN
  SELECT SUM(percent_progress) / COUNT(id) INTO progress FROM (
	SELECT t.id, s.percent_progress FROM tbl_ticket t JOIN tbl_state s ON t.state_id = s.id WHERE t.id = param_ticket_id
	UNION 
	SELECT t.id, s.percent_progress FROM tbl_ticket t JOIN tbl_state s ON t.state_id = s.id WHERE t.parent_id = param_ticket_id
  ) as all_tickets;
  IF progress IS NULL THEN
	progress := 0;
  END IF;

  RETURN progress;
END
$$ LANGUAGE plpgsql;

COMMIT;