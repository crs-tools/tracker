BEGIN;

SET ROLE TO postgres;

---------------
--- project ---
---------------

CREATE TABLE tbl_project
(
  id bigserial NOT NULL,
  title character varying(256) NOT NULL CHECK (char_length(title::text) > 0),
  slug character varying(64) NOT NULL CHECK (char_length(slug::text) > 0),
  read_only boolean NOT NULL DEFAULT false,
  created timestamp with time zone NOT NULL DEFAULT now(),
  modified timestamp with time zone NOT NULL DEFAULT now(),
  CONSTRAINT tbl_project_pk PRIMARY KEY (id),
  CONSTRAINT tbl_project_slug_uk UNIQUE (slug)
)
WITHOUT OIDS;

CREATE TABLE tbl_project_language
(
  project_id bigint NOT NULL,
  language character varying(50) NOT NULL CHECK (char_length(language::text) > 0),
  description character varying(256) NOT NULL,
  CONSTRAINT tbl_project_language_pk PRIMARY KEY (project_id, language),
  CONSTRAINT tbl_project_language_project_fk FOREIGN KEY (project_id)
      REFERENCES tbl_project (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITHOUT OIDS;

CREATE TABLE tbl_project_property
(
  project_id bigint NOT NULL,
  name ltree NOT NULL CHECK (char_length(name::text) > 0),
  value character varying(8196) NOT NULL,
  CONSTRAINT tbl_project_property_pk PRIMARY KEY (project_id, name),
  CONSTRAINT tbl_project_property_project_fk FOREIGN KEY (project_id)
      REFERENCES tbl_project (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITHOUT OIDS;

CREATE TABLE tbl_project_ticket_state
(
  project_id bigint NOT NULL,
  ticket_type enum_ticket_type NOT NULL,
  ticket_state enum_ticket_state NOT NULL,
  service_executable boolean NOT NULL DEFAULT false,
  CONSTRAINT tbl_project_ticket_state_pk PRIMARY KEY (project_id, ticket_type, ticket_state),
  CONSTRAINT tbl_project_ticket_state_project_fk FOREIGN KEY (project_id)
      REFERENCES tbl_project (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITHOUT OIDS;

CREATE TABLE tbl_project_worker_group
(
  project_id bigint NOT NULL,
  worker_group_id bigint NOT NULL,
  CONSTRAINT tbl_project_worker_group_pk PRIMARY KEY (project_id, worker_group_id),
  CONSTRAINT tbl_project_worker_group_group_fk FOREIGN KEY (worker_group_id)
      REFERENCES tbl_worker_group (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tbl_project_worker_group_project_fk FOREIGN KEY (project_id)
      REFERENCES tbl_project (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITHOUT OIDS;

CREATE TABLE tbl_project_worker_group_filter
(
  id bigserial NOT NULL,
  project_id bigint NOT NULL,
  worker_group_id bigint NOT NULL,
  property_key ltree NOT NULL CHECK (char_length(property_key::text) > 0),
  property_value character varying(8196) NOT NULL,
  CONSTRAINT tbl_project_worker_group_filter_pk PRIMARY KEY (id, project_id, worker_group_id, property_key),
  CONSTRAINT tbl_project_worker_group_filter_group_fk FOREIGN KEY (worker_group_id)
      REFERENCES tbl_worker_group (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tbl_project_worker_group_filter_project_fk FOREIGN KEY (project_id)
      REFERENCES tbl_project (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITHOUT OIDS;

CREATE TABLE tbl_project_encoding_profile
(
  project_id bigint NOT NULL,
  encoding_profile_version_id bigint NOT NULL,
  priority double precision NOT NULL DEFAULT 1,
  CONSTRAINT tbl_project_encoding_profile_pkey PRIMARY KEY (project_id, encoding_profile_version_id),
  CONSTRAINT tbl_project_encoding_profile_encoding_profile_version_id_fkey FOREIGN KEY (encoding_profile_version_id)
      REFERENCES tbl_encoding_profile_version (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT tbl_project_encoding_profile_project_id_fkey FOREIGN KEY (project_id)
      REFERENCES tbl_project (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITHOUT OIDS;

COMMIT;
