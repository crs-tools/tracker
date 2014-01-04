BEGIN;

SET ROLE TO postgres;

CREATE OR REPLACE FUNCTION ticket_depending_encoding_ticket_state(param_ticket_id bigint)
  RETURNS enum_ticket_state AS
  $$
DECLARE
	state enum_ticket_state;
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
	RETURN state;
END
$$
LANGUAGE plpgsql;

COMMIT;