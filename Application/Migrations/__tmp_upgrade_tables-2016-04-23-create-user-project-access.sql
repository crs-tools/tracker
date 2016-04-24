CREATE TABLE tbl_user_project_restrictions
(
  user_id bigint NOT NULL,
  project_id bigint NOT NULL,
  role character varying(32) DEFAULT 'user'::character varying,
  CONSTRAINT tbl_user_project_access_pk PRIMARY KEY (user_id, project_id),
  CONSTRAINT tbl_user_project_access_user_fk FOREIGN KEY (user_id)
      REFERENCES tbl_user (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tbl_user_project_access_project_fk FOREIGN KEY (project_id)
      REFERENCES tbl_project (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITHOUT OIDS;

ALTER TABLE "public"."tbl_user"
	ADD COLUMN "restrict_project_access" bool NOT NULL DEFAULT FALSE;
