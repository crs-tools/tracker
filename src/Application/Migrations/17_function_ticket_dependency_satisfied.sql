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
		s1.sort >= s2.sort INTO satisfaction
	FROM
		tbl_ticket t
	JOIN
		tbl_project p ON t.project_id = p.id
	JOIN
		tbl_ticket_state s1 ON t.ticket_type = 'encoding' AND t.ticket_state = s1.ticket_state
	JOIN
		tbl_ticket_state s2 ON t.ticket_type = 'encoding' AND p.dependent_ticket_trigger_state = s2.ticket_state
	WHERE
		t.id = param_ticket_id;

	RETURN satisfaction;
END
$$
LANGUAGE plpgsql;

COMMIT;
