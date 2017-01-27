ALTER TABLE "public"."tbl_worker"
ADD CONSTRAINT tbl_worker_name_group_uq UNIQUE (name, worker_group_id)
;

