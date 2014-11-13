BEGIN;

UPDATE tbl_ticket SET title = NULL WHERE parent_id IS NOT NULL;

COMMIT;