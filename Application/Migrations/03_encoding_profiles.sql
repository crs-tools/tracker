BEGIN;

SET ROLE TO postgres;

-------------------------
--- encoding profiles ---
-------------------------

CREATE TABLE tbl_encoding_profile
(
  id bigserial NOT NULL,
  name character varying(256) NOT NULL,
  slug character varying(64),
  extension character varying(16),
  mirror_folder character varying(256),
  CONSTRAINT tbl_encoding_profile_pk PRIMARY KEY (id)
)
WITHOUT OIDS;

CREATE TABLE tbl_encoding_profile_version
(
  id bigserial NOT NULL,
  encoding_profile_id bigint NOT NULL,
  revision bigint NOT NULL DEFAULT 1,
  created timestamp without time zone NOT NULL DEFAULT now(),
  description character varying(4096),
  xml_template text NOT NULL,
  CONSTRAINT tbl_encoding_profile_version_pkey PRIMARY KEY (id),
  CONSTRAINT tbl_encoding_profile_version_encoding_profile_id_fkey FOREIGN KEY (encoding_profile_id)
      REFERENCES tbl_encoding_profile (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT tbl_encoding_profile_version_encoding_profile_id_revision_key UNIQUE (encoding_profile_id, revision)
)
WITHOUT OIDS;

CREATE OR REPLACE FUNCTION increment_encoding_profile_revision() RETURNS trigger AS
$BODY$
  DECLARE
	rev integer;
  BEGIN
	SELECT COALESCE(MAX(revision),0) + 1 INTO rev FROM tbl_encoding_profile_version WHERE encoding_profile_id = NEW.encoding_profile_id;

	NEW.revision := rev;
	
	RETURN NEW;
  END;
$BODY$
  LANGUAGE plpgsql VOLATILE;

CREATE TRIGGER increment_encoding_profile_revision BEFORE INSERT ON tbl_encoding_profile_version FOR EACH ROW EXECUTE PROCEDURE increment_encoding_profile_revision();

COMMIT;