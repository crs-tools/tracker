CREATE TYPE enum_import_auth_type AS ENUM (
	'basic',
	'header');

ALTER TABLE tbl_import ADD COLUMN auth_type enum_import_auth_type;
ALTER TABLE tbl_import ADD COLUMN auth_user character varying(256);
ALTER TABLE tbl_import ADD COLUMN auth_password character varying(256);
ALTER TABLE tbl_import ADD COLUMN auth_header text;
