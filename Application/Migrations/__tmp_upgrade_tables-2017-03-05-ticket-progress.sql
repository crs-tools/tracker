CREATE OR REPLACE FUNCTION ticket_state_progress(param_project_id bigint, param_ticket_type enum_ticket_type, param_ticket_state enum_ticket_state) RETURNS float AS $$
DECLARE
  progress float;
BEGIN
	SELECT 
		CEIL(SUM(ts2.percent_progress) / (SELECT GREATEST(1, SUM(ts3.percent_progress)) FROM tbl_ticket_state ts3 JOIN tbl_project_ticket_state pts ON pts.ticket_type = ts3.ticket_type AND pts.ticket_state = ts3.ticket_state WHERE ts3.ticket_type = ts1.ticket_type AND pts.project_id = param_project_id) * 100)
	INTO
		progress
	FROM
		tbl_ticket_state ts1
	JOIN
		tbl_project_ticket_state pts ON pts.ticket_type = ts1.ticket_type AND pts.ticket_state = ts1.ticket_state
	JOIN
		tbl_ticket_state ts2 ON ts1.ticket_type = ts2.ticket_type AND ts1.sort >= ts2.sort
	JOIN
		tbl_project_ticket_state pts2 ON pts2.ticket_type = ts2.ticket_type AND pts2.ticket_state = ts2.ticket_state AND pts2.project_id = pts.project_id
	WHERE
		pts.project_id = param_project_id AND ts1.ticket_type = param_ticket_type AND ts1.ticket_state = param_ticket_state
	GROUP BY
		ts1.ticket_state, ts1.ticket_type, ts1.sort
	;
  IF progress IS NULL THEN
	progress := 0;
  END IF;

  RETURN progress;
END
$$ LANGUAGE plpgsql;

