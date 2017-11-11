BEGIN;

SET ROLE TO postgres;

CREATE OR REPLACE FUNCTION ticket_priority(param_ticket_id bigint) RETURNS float AS $$
DECLARE
  priority float;
BEGIN
  SELECT 
	CASE WHEN t.parent_id IS NOT NULL THEN 
		pt.priority * t.priority * COALESCE(extract(EPOCH FROM CURRENT_TIMESTAMP) / ticket_fahrplan_starttime(pt.id), 1) * COALESCE((SELECT pep.priority FROM tbl_project_encoding_profile pep WHERE pep.project_id = t.project_id AND pep.encoding_profile_version_id = t.encoding_profile_version_id),1)
	ELSE
		t.priority * COALESCE(extract(EPOCH FROM CURRENT_TIMESTAMP) / ticket_fahrplan_starttime(t.id), 1)
	END INTO priority
  FROM 
	tbl_ticket t
  LEFT JOIN 
	tbl_ticket pt ON pt.id = t.parent_id
  WHERE 
	t.id = param_ticket_id;

  RETURN priority;
END
$$ LANGUAGE plpgsql;

COMMIT;