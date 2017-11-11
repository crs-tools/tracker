BEGIN;

CREATE OR REPLACE FUNCTION create_missing_child_tickets() RETURNS void AS $$
BEGIN
	INSERT INTO tbl_ticket (parent_id, project_id, title, fahrplan_id, priority, type_id, state_id, encoding_profile_id) (SELECT t1.id as parent_id, t1.project_id, CONCAT(t1.title,' (',p1.name,')') AS title, t1.fahrplan_id, 1 AS priority, 2 AS type_id, 12 AS state_id, p1.id AS encoding_profile_id FROM tbl_encoding_profile p1 LEFT OUTER JOIN tbl_ticket t1 ON p1.project_id = t1.project_id LEFT JOIN tbl_ticket t2 ON t2.parent_id = t1.id AND t2.encoding_profile_id = p1.id WHERE t1.type_id = 1 AND t2.id IS NULL ORDER BY t1.id);
END
$$ LANGUAGE plpgsql;

ALTER FUNCTION create_missing_child_tickets() OWNER TO c3tt;

COMMIT;


