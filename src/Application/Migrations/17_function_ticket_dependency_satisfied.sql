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
		masterstate.sort >= wantedstate.sort INTO satisfaction
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
	JOIN
		tbl_project p ON t.project_id = p.id
	JOIN
		tbl_ticket_state masterstate ON
			masterstate.ticket_type = t2.ticket_type AND
			masterstate.ticket_state = t2.ticket_state
	JOIN
		tbl_ticket_state wantedstate ON
			wantedstate.ticket_type = t2.ticket_type AND
			wantedstate.ticket_state = p.dependent_ticket_trigger_state
	WHERE
		t.id = param_ticket_id;

	RETURN satisfaction;
END
$$
LANGUAGE plpgsql;

COMMIT;
