BEGIN;

SET ROLE TO postgres;

CREATE OR REPLACE FUNCTION ticket_dependee_ticket_state_satisfied(param_depender_ticket_id bigint)
  RETURNS boolean AS
  $$
DECLARE
	satisfaction boolean;
	dependee_state enum_ticket_state;
BEGIN
	SELECT ticket_dependee_ticket_state(param_depender_ticket_id)
		INTO dependee_state;

	SELECT
		dependee_ticket_state.sort >= configured_trigger_state.sort INTO satisfaction
	FROM
		tbl_ticket depender_ticket
	JOIN
		tbl_project project ON depender_ticket.project_id = project.id
	JOIN
		-- if given ticket is no encoding ticket, function will return NULL
		tbl_ticket_state dependee_ticket_state ON
			depender_ticket.ticket_type = 'encoding' AND
			dependee_ticket_state.ticket_state = dependee_state
	JOIN
		tbl_ticket_state configured_trigger_state ON
			depender_ticket.ticket_type = 'encoding' AND
			configured_trigger_state.ticket_state = project.dependent_ticket_trigger_state
	WHERE
		depender_ticket.id = param_depender_ticket_id;

	RETURN satisfaction;
END
$$
LANGUAGE plpgsql;

COMMIT;
