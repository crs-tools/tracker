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
		depender_ticket_state.sort < dependee_ticket_state.sort AND
		dependee_ticket_state.sort >= configured_trigger_state.sort INTO satisfaction
	FROM
		tbl_ticket depender_ticket
	JOIN
		tbl_ticket_state depender_ticket_state ON
			depender_ticket_state.ticket_type = depender_ticket.ticket_type AND
			depender_ticket_state.ticket_state = depender_ticket.ticket_state
	JOIN
		tbl_project project ON depender_ticket.project_id = project.id
	JOIN
		tbl_ticket_state dependee_ticket_state ON
			dependee_ticket_state.ticket_type = depender_ticket.ticket_type AND
			dependee_ticket_state.ticket_state = dependee_state
	JOIN
		tbl_ticket_state configured_trigger_state ON
			configured_trigger_state.ticket_type = depender_ticket.ticket_type AND
			configured_trigger_state.ticket_state = project.dependee_ticket_trigger_state
	WHERE
		depender_ticket.id = param_depender_ticket_id AND
		-- if given ticket is no encoding ticket, function will return NULL
		depender_ticket.ticket_type = 'encoding';

	RETURN satisfaction;
END
$$
LANGUAGE plpgsql;

COMMIT;
