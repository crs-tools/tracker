BEGIN;

SET ROLE TO postgres;

-- function returns a table with all encoding profiles, which depend
-- on the given encoding profile

-- second parameter is optional and is used in recursive to prevent endless loop

CREATE OR REPLACE FUNCTION encoding_profile_all_dependees(param_profile_id bigint, param_known_ids bigint[] DEFAULT array[]::bigint[])
  RETURNS TABLE (id bigint) AS
$$
DECLARE
  p_id bigint;
  dependee_profile_ids bigint[];
BEGIN
  IF array_dims(param_known_ids) ISNULL THEN
    param_known_ids := array[param_profile_id];
  END IF;

  FOR p_id IN
  SELECT p.id
  FROM tbl_encoding_profile p
  WHERE depends_on=param_profile_id AND NOT (p.id = ANY(param_known_ids))
  LOOP
    dependee_profile_ids := p_id || dependee_profile_ids;
    dependee_profile_ids := ARRAY(SELECT e.id from encoding_profile_all_dependees(p_id, dependee_profile_ids || param_known_ids) e) || dependee_profile_ids;
  END LOOP;
  RETURN QUERY SELECT DISTINCT unnest(dependee_profile_ids);
END
$$
LANGUAGE plpgsql;

COMMIT;