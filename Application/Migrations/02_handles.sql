BEGIN;

SET ROLE TO postgres;

----------------------------------------
--- users, worker groups and workers ---
----------------------------------------

CREATE TABLE tbl_handle
(
  id bigserial NOT NULL,
  last_seen timestamp with time zone NOT NULL DEFAULT now(),
  name character varying(128) NOT NULL,
  CONSTRAINT tbl_handle_pkey PRIMARY KEY (id)
)
WITHOUT OIDS;

CREATE OR REPLACE FUNCTION valid_handle() RETURNS TRIGGER AS $$
BEGIN
  IF NEW.handle_id IS NULL THEN
    RETURN NEW;
  END IF;
  IF NOT EXISTS(SELECT id FROM tbl_handle WHERE id = NEW.handle_id)
		THEN RAISE EXCEPTION 'Handle % not found', NEW.handle_id;
	END IF;
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TABLE tbl_user
(
  id bigint NOT NULL DEFAULT nextval('tbl_handle_id_seq'::regclass),
  last_seen timestamp with time zone,
  name character varying(128) NOT NULL,
  password character(60) DEFAULT NULL::bpchar,
  persistence_token character(32) DEFAULT NULL::bpchar,
  remember_token character(32) DEFAULT NULL::bpchar,
  role character varying(32) DEFAULT 'user'::character varying,
  failed_login_count integer DEFAULT 0,
  last_login timestamp with time zone,
  CONSTRAINT tbl_user_pk PRIMARY KEY (id),
  CONSTRAINT tbl_user_name_uq UNIQUE (name),
  CONSTRAINT tbl_user_persistence_token_key UNIQUE (persistence_token),
  CONSTRAINT tbl_user_remember_token_key UNIQUE (remember_token)
)
INHERITS (tbl_handle)
WITHOUT OIDS;

CREATE TABLE tbl_worker_group
(
  id bigserial NOT NULL,
  title character varying(256) NOT NULL,
  token character(32) NOT NULL,
  secret character(32) NOT NULL,
  CONSTRAINT tbl_worker_group_pk PRIMARY KEY (id),
  CONSTRAINT tbl_worker_group_token_uq UNIQUE (token)
)
WITHOUT OIDS;

CREATE TABLE tbl_worker
(
  id bigint NOT NULL DEFAULT nextval('tbl_handle_id_seq'::regclass),
  last_seen timestamp with time zone NOT NULL DEFAULT now(),
  name character varying(128) NOT NULL,
  worker_group_id bigint NOT NULL,
  description character varying(256),
  CONSTRAINT tbl_worker_pkey PRIMARY KEY (id),
  CONSTRAINT tbl_worker_worker_group_id_fkey FOREIGN KEY (worker_group_id)
      REFERENCES tbl_worker_group (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
INHERITS (tbl_handle)
WITHOUT OIDS;

COMMIT;
