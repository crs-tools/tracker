BEGIN;

SET ROLE TO postgres;

CREATE OR REPLACE FUNCTION ticket_dependee_ticket_state(param_depender_ticket_id bigint)
  RETURNS enum_ticket_state AS
  $$
DECLARE
	state enum_ticket_state;
BEGIN
	SELECT
		dependee.ticket_state INTO state
	FROM
		tbl_ticket depender
	JOIN
		tbl_encoding_profile_version epv ON epv.id = depender.encoding_profile_version_id
	JOIN
		tbl_encoding_profile ep ON epv.encoding_profile_id = ep.id
	JOIN
		tbl_encoding_profile ep2 ON ep2.id = ep.depends_on
	JOIN
		tbl_encoding_profile_version epv2 ON epv2.encoding_profile_id = ep2.id
	JOIN
		tbl_ticket dependee ON dependee.encoding_profile_version_id = epv2.id AND dependee.parent_id = depender.parent_id
	WHERE
		depender.id = param_depender_ticket_id;
	RETURN state;
END
$$
LANGUAGE plpgsql;

COMMIT;