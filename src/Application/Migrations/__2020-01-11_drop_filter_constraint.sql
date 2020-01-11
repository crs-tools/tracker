BEGIN;

SET ROLE TO postgres;

ALTER TABLE tbl_project_worker_group_filter DROP CONSTRAINT tbl_project_worker_group_filter_uq;

COMMIT;

