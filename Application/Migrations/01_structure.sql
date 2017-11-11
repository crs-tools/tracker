BEGIN;

CREATE TABLE tbl_ticket_type
(
  id bigint NOT NULL,
  "name" character varying(64) NOT NULL,
  disabled boolean NOT NULL DEFAULT false,
  CONSTRAINT tbl_ticket_type_pk PRIMARY KEY (id),
  CONSTRAINT tbl_ticket_type_name_uq UNIQUE (name)
)
WITHOUT OIDS;

ALTER TABLE tbl_ticket_type OWNER TO c3tt;

CREATE TABLE tbl_user
(
  id bigserial NOT NULL,
  "name" character varying(64) NOT NULL,
  "password" character varying(32) NOT NULL,
  "password_salt" character varying(32) NOT NULL,
  hash character varying(32) NOT NULL,
  remember_token char(32) NOT NULL,
  role character varying(32) DEFAULT NULL,
  failed_login_count integer DEFAULT 0,
  last_seen timestamp without time zone DEFAULT NULL,
  halted_until timestamp without time zone,
  hostname character varying(256),
  next_ping_command character varying(256),
  next_ping_command_reason character varying(256),
  CONSTRAINT tbl_user_pk PRIMARY KEY (id),
  CONSTRAINT tbl_user_name_uq UNIQUE (name)
)
WITHOUT OIDS;

ALTER TABLE tbl_user OWNER TO c3tt;

CREATE TABLE tbl_state
(
  id bigint NOT NULL,
  "name" character varying(64) NOT NULL,
  ticket_type_id bigint NOT NULL,
  percent_progress double precision DEFAULT 0.0,
  CONSTRAINT tbl_state_pk PRIMARY KEY (id),
  CONSTRAINT tbl_ticket_type_fk FOREIGN KEY (ticket_type_id)
      REFERENCES tbl_ticket_type (id) MATCH SIMPLE
      ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT tbl_state_name_uq UNIQUE (name, ticket_type_id)
)
WITHOUT OIDS;

ALTER TABLE tbl_state OWNER TO c3tt;

CREATE TABLE tbl_project
(
  id bigserial NOT NULL,
  title character varying(256) NOT NULL,
  slug character varying(64) NOT NULL,
  read_only boolean NOT NULL DEFAULT false,
  CONSTRAINT tbl_project_pk PRIMARY KEY (id),
  CONSTRAINT tbl_project_slug_uk UNIQUE (slug)
)
WITHOUT OIDS;

ALTER TABLE tbl_project OWNER TO c3tt;

CREATE TABLE tbl_project_property
(
  project_id bigint NOT NULL,
  "name" ltree NOT NULL,
  "value" character varying(8196) NOT NULL,
  CONSTRAINT tbl_project_property_pk PRIMARY KEY (project_id, name),
  CONSTRAINT tbl_project_property_project_fk FOREIGN KEY (project_id)
      REFERENCES tbl_project (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITHOUT OIDS;

ALTER TABLE tbl_project_property OWNER TO c3tt;

CREATE TABLE tbl_project_language
(
  project_id bigint NOT NULL,
  "language" character varying(50) NOT NULL,
  "description" character varying(256) NOT NULL,
  CONSTRAINT tbl_project_language_pk PRIMARY KEY (project_id, language),
  CONSTRAINT tbl_project_language_project_fk FOREIGN KEY (project_id)
      REFERENCES tbl_project (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITHOUT OIDS;

ALTER TABLE tbl_project_language OWNER TO c3tt;

CREATE OR REPLACE FUNCTION check_valid_inheritance(param_parent_id BIGINT, param_fahrplan_id INT) RETURNS BOOLEAN AS
$BODY$
DECLARE
BEGIN
  IF param_parent_id IS NULL THEN
    RETURN TRUE;
  END IF;

  PERFORM id FROM tbl_ticket WHERE id = param_parent_id AND parent_id IS NOT NULL;
  IF FOUND THEN
    RAISE NOTICE 'the parent has got a parent. only one level of inheritance';
    RETURN FALSE;
  END IF;

  PERFORM id FROM tbl_ticket WHERE id = param_parent_id AND fahrplan_id <> param_fahrplan_id;
  IF FOUND THEN
    RAISE NOTICE 'the fahrplan_id of the parent differs from the fahrplan_id of the child';
    RETURN FALSE;
  END IF;
  
  RETURN TRUE;
END
$BODY$ LANGUAGE 'plpgsql' VOLATILE;

ALTER FUNCTION check_valid_inheritance(BIGINT, INT) OWNER TO c3tt;

CREATE TABLE tbl_encoding_profile
(
  id bigserial NOT NULL,
  project_id bigint NOT NULL,
  "name" character varying(256) NOT NULL,
  slug character varying(64),
  extension character varying(16),
  mirror_folder character varying(256),
  xml_template text,
  approved boolean NOT NULL DEFAULT false,
  priority float4 NOT NULL DEFAULT 1,
  CONSTRAINT tbl_encoding_profile_pk PRIMARY KEY (id),
  CONSTRAINT tbl_encoding_profile_project_fk FOREIGN KEY (project_id)
      REFERENCES tbl_project (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tbl_encoding_profile_slug_uk UNIQUE (slug,project_id)
)
WITHOUT OIDS;

ALTER TABLE tbl_encoding_profile OWNER TO c3tt;

CREATE TABLE tbl_ticket
(
  id bigserial NOT NULL,
  parent_id bigint,
  project_id bigint NOT NULL,
  title character varying(128) NOT NULL,
  fahrplan_id int NOT NULL,
  priority real NOT NULL,
  type_id bigint NOT NULL,
  state_id bigint NOT NULL,
  encoding_profile_id bigint,
  user_id bigint,
  created timestamp without time zone NOT NULL DEFAULT now(),
  modified timestamp without time zone NOT NULL DEFAULT now(),
  failed boolean NOT NULL DEFAULT false,
  needs_attention boolean NOT NULL DEFAULT false,
  CONSTRAINT tbl_ticket_pk PRIMARY KEY (id),
  CONSTRAINT tbl_ticket_parent_fk FOREIGN KEY (parent_id)
      REFERENCES tbl_ticket (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tbl_ticket_project_fk FOREIGN KEY (project_id)
      REFERENCES tbl_project (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tbl_ticket_state_fk FOREIGN KEY (state_id)
      REFERENCES tbl_state (id) MATCH SIMPLE
      ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT tbl_ticket_type_fk FOREIGN KEY (type_id)
      REFERENCES tbl_ticket_type (id) MATCH SIMPLE
      ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT tbl_ticket_encoding_profile_fk FOREIGN KEY (encoding_profile_id)
      REFERENCES tbl_encoding_profile (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tbl_ticket_user_fk FOREIGN KEY (user_id)
      REFERENCES tbl_user (id) MATCH SIMPLE
      ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT tbl_ticket_encoding_profile_check CHECK (
      CASE WHEN type_id = 2 THEN encoding_profile_id IS NOT NULL ELSE TRUE END),
  CONSTRAINT ticket_valid_inheritence 
      CHECK (check_valid_inheritance(parent_id,fahrplan_id) IS TRUE)
)
WITHOUT OIDS;

ALTER TABLE tbl_ticket OWNER TO c3tt;

CREATE OR REPLACE FUNCTION set_fahrplan_id() RETURNS trigger AS $BODY$
DECLARE
	fid VARCHAR;
BEGIN
	IF NEW.parent_id IS NOT NULL THEN
		SELECT fahrplan_id INTO fid FROM tbl_ticket WHERE id = NEW.parent_id;
		IF FOUND THEN
			NEW.fahrplan_id := fid;
		END IF;
	END IF;
	RETURN NEW;
END;
$BODY$ LANGUAGE plpgsql;

CREATE TRIGGER set_fahrplan_id BEFORE INSERT OR UPDATE ON tbl_ticket FOR EACH ROW EXECUTE PROCEDURE set_fahrplan_id();

CREATE TABLE tbl_ticket_property
(
  ticket_id bigint NOT NULL,
  "name" ltree NOT NULL,
  "value" character varying(8196) NOT NULL,
  CONSTRAINT tbl_ticket_property_pk PRIMARY KEY (ticket_id, name),
  CONSTRAINT tbl_ticket_property_ticket_fk FOREIGN KEY (ticket_id)
      REFERENCES tbl_ticket (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITHOUT OIDS;

ALTER TABLE tbl_ticket_property OWNER TO c3tt;

CREATE TABLE tbl_comment
(
  id bigserial NOT NULL,
  ticket_id bigint NOT NULL,
  user_id bigint NOT NULL,
  origin_user_name character varying,
  created timestamp without time zone NOT NULL DEFAULT now(),
  "comment" text,
  user_set_failed BOOLEAN NOT NULL DEFAULT FALSE,
  user_set_needs_attention BOOLEAN NOT NULL DEFAULT FALSE,
  origin_user_name varchar,
  CONSTRAINT tbl_comment_pk PRIMARY KEY (id),
  CONSTRAINT tbl_comment_log_fkt_ticket_fk FOREIGN KEY (ticket_id)
      REFERENCES tbl_ticket (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tbl_comment_user_fk FOREIGN KEY (user_id)
      REFERENCES tbl_user (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITHOUT OIDS;

ALTER TABLE tbl_comment OWNER TO c3tt;


CREATE TABLE tbl_log
(
  id bigserial NOT NULL,
  ticket_id bigint NOT NULL,
  created timestamp without time zone NOT NULL DEFAULT now(),
  from_state_id bigint,
  to_state_id bigint,
  user_id bigint NOT NULL,
  comment_id bigint,
  "comment" text,
  event character varying(255) NOT NULL,
  CONSTRAINT tbl_log_pk PRIMARY KEY (id),
  CONSTRAINT tbl_log_state_from_fk FOREIGN KEY (from_state_id)
      REFERENCES tbl_state (id) MATCH SIMPLE
      ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT tbl_log_state_to_fk FOREIGN KEY (to_state_id)
      REFERENCES tbl_state (id) MATCH SIMPLE
      ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT tbl_log_ticket_fk FOREIGN KEY (ticket_id)
      REFERENCES tbl_ticket (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tbl_log_user_id FOREIGN KEY (user_id)
      REFERENCES tbl_user (id) MATCH SIMPLE
      ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT tbl_log_comment_fk FOREIGN KEY (comment_id)
      REFERENCES tbl_comment (id) MATCH SIMPLE
      ON UPDATE SET NULL ON DELETE SET NULL
)
WITHOUT OIDS;

CREATE INDEX tbl_log_event_idx ON tbl_log USING btree (event);	

ALTER TABLE tbl_log OWNER TO c3tt;

CREATE TABLE tbl_log_service
(
  id bigserial NOT NULL,
  user_id bigint NOT NULL,
  created timestamp without time zone NOT NULL DEFAULT now(),
  output_delta text,
  ticket_id bigint,
  progress double precision,
  CONSTRAINT tbl_log_service_pkey PRIMARY KEY (id ),
  CONSTRAINT tbl_log_service_ticket_id_fkey FOREIGN KEY (ticket_id)
      REFERENCES tbl_ticket (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT tbl_log_service_user_id_fkey FOREIGN KEY (user_id)
      REFERENCES tbl_user (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)	
WITHOUT OIDS;

ALTER TABLE tbl_log_service OWNER TO c3tt;

CREATE TABLE tbl_log_message
(
  id bigserial NOT NULL,
  event character varying(256) NOT NULL,
  message character varying(512),
  rpc boolean NOT NULL DEFAULT false,
  feed_message varchar(512),
  feed_include_log bool DEFAULT false,
  feed_message_multiple varchar(512),
  CONSTRAINT tbl_log_message_pkey PRIMARY KEY (id ),
  CONSTRAINT tbl_log_message_event_key UNIQUE (event )
)
WITHOUT OIDS;

ALTER TABLE tbl_log_message OWNER TO c3tt;

COMMIT;
