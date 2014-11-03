CREATE TABLE tbl_import
(
  id bigserial NOT NULL,
  project_id bigint NOT NULL,
  user_id bigint NOT NULL,
  url text NOT NULL,
  xml xml NOT NULL,
  version character varying(128) NOT NULL,
  rooms json,
  created timestamp with time zone NOT NULL,
  finished timestamp with time zone,
  PRIMARY KEY (id),
  CONSTRAINT tbl_import_user_fk FOREIGN KEY (user_id)
    REFERENCES tbl_user (id)
    ON UPDATE SET NULL ON DELETE SET NULL,
  CONSTRAINT tbl_import_project_fk FOREIGN KEY (project_id)
    REFERENCES tbl_project (id) MATCH SIMPLE
    ON UPDATE CASCADE ON DELETE CASCADE
) WITH OIDS;