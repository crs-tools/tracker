BEGIN;

SET ROLE TO postgres;

CREATE OR REPLACE FUNCTION ticket_depending_encoding_ticket_state_satisfied(param_ticket_id bigint)
  RETURNS boolean AS
  $$
DECLARE
	satisfaction boolean;
BEGIN
	SELECT
		dependent_ticket_state.sort >= configured_trigger_state.sort INTO satisfaction
	FROM
		tbl_ticket param_ticket
	JOIN
		tbl_project project ON param_ticket.project_id = project.id
	JOIN
		-- if given ticket is no encoding ticket, function will return NULL
		tbl_ticket_state dependent_ticket_state ON
			param_ticket.ticket_type = 'encoding' AND
			dependent_ticket_state.ticket_state = param_ticket.ticket_state
	JOIN
		tbl_ticket_state configured_trigger_state ON
			param_ticket.ticket_type = 'encoding' AND
			configured_trigger_state.ticket_state = project.dependent_ticket_trigger_state
	WHERE
		param_ticket.id = param_ticket_id;

	RETURN satisfaction;
END
$$
LANGUAGE plpgsql;

COMMIT;
