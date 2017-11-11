BEGIN;

CREATE OR REPLACE FUNCTION getTicketPriority(param_ticket_id bigint) RETURNS float AS $$
DECLARE
  priority float;
BEGIN
  SELECT 
	CASE WHEN t.parent_id IS NOT NULL THEN 
		pt.priority * t.priority * extract(EPOCH FROM CURRENT_TIMESTAMP) / getTicketStartTimestamp(pt.id)
	ELSE
		t.priority * extract(EPOCH FROM CURRENT_TIMESTAMP) / getTicketStartTimestamp(t.id)
	END INTO priority
  FROM tbl_ticket t
  LEFT JOIN tbl_ticket pt ON pt.id = t.parent_id
  WHERE t.id = param_ticket_id;

  IF priority IS NULL THEN
	priority := 1;
  END IF;

  RETURN priority;
END
$$ LANGUAGE plpgsql;

COMMIT;