BEGIN;

CREATE TABLE tbl_encoding_profile_property
(
  encoding_profile_id bigint NOT NULL,
  name ltree NOT NULL CHECK (char_length(name::text) > 0),
  value character varying(8196) NOT NULL,
  CONSTRAINT tbl_encoding_profile_property_pk PRIMARY KEY (encoding_profile_id, name),
  CONSTRAINT tbl_encoding_profile_property_project_fk FOREIGN KEY (encoding_profile_id)
      REFERENCES tbl_encoding_profile (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITHOUT OIDS;

INSERT INTO tbl_encoding_profile_property
	SELECT id AS encoding_profile_id, 'EncodingProfile.Extension' AS name, extension as value
  FROM tbl_encoding_profile
  WHERE mirror_folder != '';

INSERT INTO tbl_encoding_profile_property
	SELECT id AS encoding_profile_id, 'EncodingProfile.MirrorFolder' AS name, mirror_folder as value
  FROM tbl_encoding_profile
  WHERE mirror_folder != '';

COMMIT;