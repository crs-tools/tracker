﻿BEGIN;

SET ROLE TO postgres;

CREATE OR REPLACE FUNCTION ticket_dependee_missing(param_depender_ticket_id bigint)
  RETURNS boolean AS
  $$
DECLARE
	result boolean;
BEGIN

	SELECT
		epv.id IS NOT NULL -- check this is actually an encoding ticket
		AND ep.depends_on IS NOT NULL -- check that this is a ticket of a depending profile
		AND dependee.id IS NULL -- this is the error condition to return true on
		INTO result
	FROM
		tbl_ticket depender
	LEFT JOIN
		tbl_encoding_profile_version epv ON epv.id = depender.encoding_profile_version_id
	LEFT JOIN
		tbl_encoding_profile ep ON epv.encoding_profile_id = ep.id
	LEFT JOIN
		tbl_encoding_profile ep2 ON ep2.id = ep.depends_on
	LEFT JOIN
		tbl_encoding_profile_version epv2 ON epv2.encoding_profile_id = ep2.id
	INNER JOIN
		tbl_ticket dependee ON dependee.encoding_profile_version_id = epv2.id AND dependee.parent_id = depender.parent_id
	WHERE
		depender.id = param_depender_ticket_id;

	RETURN COALESCE(result, false);

END
$$
LANGUAGE plpgsql;

COMMIT;
