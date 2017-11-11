BEGIN;

--DROP FUNCTION getTicketStartTimestamp(ticket_id bigint);
CREATE OR REPLACE FUNCTION getTicketStartTimestamp(param_ticket_id bigint) RETURNS integer AS $$
DECLARE
  unixtime integer;
BEGIN
  SELECT EXTRACT(EPOCH FROM (p.value::date + p2.value::time)::timestamp) INTO unixtime 
  FROM tbl_ticket_property p 
  JOIN tbl_ticket_property p2 ON p.ticket_id = p2.ticket_id AND p2.name = 'Fahrplan.Start'
  WHERE p.name = 'Fahrplan.Date' AND p.ticket_id = param_ticket_id;

  RETURN unixtime;
END
$$ LANGUAGE plpgsql;



COMMIT;