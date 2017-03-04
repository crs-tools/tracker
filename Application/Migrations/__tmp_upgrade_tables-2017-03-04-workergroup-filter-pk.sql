-- remove old pk
ALTER TABLE tbl_project_worker_group_filter DROP CONSTRAINT tbl_project_worker_group_filter_pk;

-- split into id-PK and unique contraint
ALTER TABLE tbl_project_worker_group_filter ADD CONSTRAINT tbl_project_worker_group_filter_pk PRIMARY KEY (id);
ALTER TABLE tbl_project_worker_group_filter ADD CONSTRAINT tbl_project_worker_group_filter_uq UNIQUE (project_id, worker_group_id, property_key);
