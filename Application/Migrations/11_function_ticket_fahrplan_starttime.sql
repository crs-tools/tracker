BEGIN;

SET ROLE TO postgres;

CREATE OR REPLACE FUNCTION ticket_fahrplan_starttime(param_ticket_id bigint) RETURNS integer AS $$
DECLARE
  unixtime integer;
BEGIN
  SELECT
  EXTRACT(EPOCH FROM p.value::timestamp with time zone) INTO unixtime 
  FROM tbl_ticket_property p 
  WHERE p.name = 'Fahrplan.DateTime' AND p.ticket_id = param_ticket_id;

  RETURN unixtime;
END
$$ LANGUAGE plpgsql;

COMMIT;
