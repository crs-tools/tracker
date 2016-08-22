-- unfortunately needed because of not null constraint and the lack of a sensible default:

TRUNCATE TABLE tbl_project_worker_group;

ALTER TABLE tbl_project_worker_group ADD COLUMN ticket_type enum_ticket_type NOT NULL;
ALTER TABLE tbl_project_worker_group ADD COLUMN ticket_state enum_ticket_state NOT NULL;
ALTER TABLE tbl_project_worker_group DROP CONSTRAINT tbl_project_worker_group_pk;
ALTER TABLE tbl_project_worker_group ADD CONSTRAINT tbl_project_worker_group_pk
 PRIMARY KEY (project_id, worker_group_id, ticket_type, ticket_state);


-- unfortunately needed because of not null constraint and the lack of a sensible default:
TRUNCATE TABLE tbl_project_worker_group_filter;

ALTER TABLE tbl_project_worker_group_filter ADD COLUMN ticket_type enum_ticket_type NOT NULL;
ALTER TABLE tbl_project_worker_group_filter ADD COLUMN ticket_state enum_ticket_state NOT NULL;
ALTER TABLE tbl_project_worker_group_filter DROP CONSTRAINT tbl_project_worker_group_filter_pk;
ALTER TABLE tbl_project_worker_group_filter ADD CONSTRAINT tbl_project_worker_group_filter_pk
 PRIMARY KEY (id, project_id, worker_group_id, ticket_type, ticket_state, property_key);

