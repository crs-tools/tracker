BEGIN;

CREATE VIEW view_log AS SELECT l.ticket_id, t.fahrplan_id, p.slug, l.created, s1.name as from_state, s2.name as to_state, u.name, l.event, l.comment FROM tbl_log l JOIN tbl_ticket t ON l.ticket_id = t.id JOIN tbl_project p ON t.project_id = p.id JOIN tbl_user u ON l.user_id = u.id LEFT JOIN tbl_state s1 ON s1.id = l.from_state_id LEFT JOIN tbl_state s2 ON s2.id = l.to_state_id;

ALTER VIEW view_log OWNER TO c3tt;

COMMIT;