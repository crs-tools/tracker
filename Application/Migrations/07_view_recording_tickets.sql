BEGIN;

CREATE OR REPLACE VIEW view_recording_tickets AS
  SELECT t.*, to_timestamp(getTicketStartTimestamp(t.id)) as time_start, (to_timestamp(getTicketStartTimestamp(t.id)) + p.value::time)::timestamp as time_end FROM tbl_ticket t LEFT JOIN tbl_ticket_property p ON p.ticket_id = t.id AND p.name = 'Fahrplan.Duration' WHERE type_id = 1;

ALTER VIEW view_recording_tickets OWNER TO c3tt;
COMMIT;