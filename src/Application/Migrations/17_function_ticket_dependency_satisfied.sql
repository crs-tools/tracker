BEGIN;

SET ROLE TO postgres;

CREATE OR REPLACE FUNCTION ticket_depending_encoding_ticket_state_satisfied(param_ticket_id bigint)
  RETURNS boolean AS
  $$
DECLARE
	state enum_ticket_state;
	satisfaction boolean;
BEGIN
	SELECT
		t2.ticket_state INTO state
	FROM
		tbl_ticket t
	JOIN
		tbl_encoding_profile_version epv ON epv.id = t.encoding_profile_version_id
	JOIN
		tbl_encoding_profile ep ON epv.encoding_profile_id = ep.id
	JOIN
		tbl_encoding_profile ep2 ON ep2.id = ep.depends_on
	JOIN
		tbl_encoding_profile_version epv2 ON epv2.encoding_profile_id = ep2.id
	JOIN
		tbl_ticket t2 ON t2.encoding_profile_version_id = epv2.id AND t2.parent_id = t.parent_id
	WHERE
		t.id = param_ticket_id;

	SELECT
		dependent_ticket_state.sort >= configured_trigger_state.sort INTO satisfaction
	FROM
		tbl_ticket t
	JOIN
		tbl_project p ON t.project_id = p.id
	JOIN
		-- if given ticket is no encoding ticket, function will return NULL
		tbl_ticket_state dependent_ticket_state ON
			t.ticket_type = 'encoding' AND
			dependent_ticket_state.ticket_state = t.ticket_state
	JOIN
		tbl_ticket_state configured_trigger_state ON
			t.ticket_type = 'encoding' AND
			configured_trigger_state.ticket_state = p.dependent_ticket_trigger_state
	WHERE
		t.id = param_ticket_id;

	RETURN satisfaction;
END
$$
LANGUAGE plpgsql;

COMMIT;
