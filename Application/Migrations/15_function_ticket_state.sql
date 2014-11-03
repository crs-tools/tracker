BEGIN;

SET ROLE TO postgres;

CREATE OR REPLACE FUNCTION ticket_state_next(param_project_id bigint, param_ticket_type enum_ticket_type, param_ticket_state enum_ticket_state)
  RETURNS TABLE(ticket_state enum_ticket_state, service_executable boolean) AS
  $$
DECLARE
BEGIN
	RETURN QUERY
	SELECT
		pts.ticket_state, pts.service_executable
	FROM
		tbl_ticket_state ts1
	JOIN
		tbl_project_ticket_state pts ON pts.ticket_type = ts1.ticket_type AND pts.ticket_state = ts1.ticket_state
	JOIN
		tbl_ticket_state ts2 ON ts1.ticket_type = ts2.ticket_type AND ts1.sort > ts2.sort
	WHERE
		pts.project_id = param_project_id AND
		ts2.ticket_type = param_ticket_type AND
		ts2.ticket_state = param_ticket_state
  ORDER BY
    ts1.sort ASC
	LIMIT 1;
  IF NOT FOUND THEN
    RETURN QUERY SELECT NULL::enum_ticket_state, false;
  END IF;
END
$$
LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION ticket_state_previous(param_project_id bigint, param_ticket_type enum_ticket_type, param_ticket_state enum_ticket_state)
  RETURNS TABLE(ticket_state enum_ticket_state, service_executable boolean) AS
  $$
DECLARE
BEGIN
	RETURN QUERY
	SELECT
		pts.ticket_state, pts.service_executable
	FROM
		tbl_ticket_state ts1
	JOIN
		tbl_project_ticket_state pts ON pts.ticket_type = ts1.ticket_type AND pts.ticket_state = ts1.ticket_state
	JOIN
		tbl_ticket_state ts2 ON ts1.ticket_type = ts2.ticket_type AND ts1.sort < ts2.sort
	WHERE
		pts.project_id = param_project_id AND
		ts2.ticket_type = param_ticket_type AND
		ts2.ticket_state = param_ticket_state
  ORDER BY
    ts1.sort DESC
	LIMIT 1;
  IF NOT FOUND THEN
    RETURN QUERY SELECT NULL::enum_ticket_state, false;
  END IF;
END
$$
LANGUAGE plpgsql;

COMMIT;
