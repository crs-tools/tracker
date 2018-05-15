BEGIN;

SET ROLE TO postgres;

---------------
--- tickets ---
---------------

-- check contraint function
CREATE OR REPLACE FUNCTION check_fahrplan_inheritance(param_parent_id bigint, param_fahrplan_id integer) RETURNS boolean AS
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
$BODY$
LANGUAGE plpgsql VOLATILE;

-- trigger function to update ticket next state and service flag

CREATE OR REPLACE FUNCTION update_ticket_next_state()
  RETURNS trigger AS
$BODY$
  DECLARE
   next_state record;
  BEGIN
    next_state := ticket_state_next(NEW.project_id, NEW.ticket_type, NEW.ticket_state);

    NEW.ticket_state_next := next_state.ticket_state;
    NEW.service_executable := next_state.service_executable;

    RETURN NEW;
  END
$BODY$
LANGUAGE plpgsql VOLATILE;

-- trigger function to update ticket progress, eventually parent's progress too

CREATE OR REPLACE FUNCTION update_ticket_progress()
  RETURNS trigger AS
$BODY$
  BEGIN
    UPDATE tbl_ticket SET progress = ticket_progress(NEW.id) WHERE id = NEW.id;
    IF (NEW.parent_id IS NOT NULL) THEN
      UPDATE tbl_ticket SET progress = ticket_progress(NEW.parent_id) WHERE id = NEW.parent_id;
    END IF;
    RETURN NEW;
  END
$BODY$
LANGUAGE plpgsql VOLATILE;

-- function to update progress of all tickets - must be called manually!

CREATE OR REPLACE FUNCTION update_all_tickets_progress_and_next_state(param_project_id bigint)
  RETURNS VOID AS
$BODY$
  BEGIN

    UPDATE tbl_ticket t SET
      (progress, ticket_state_next, service_executable)
        = (tp, (n).ticket_state, (n).service_executable)
    FROM (
      SELECT id, ticket_state_next(t2.project_id, t2.ticket_type, t2.ticket_state) AS n, ticket_progress(t2.id) as tp
      FROM tbl_ticket t2
      WHERE t2.project_id = param_project_id AND param_project_id IS NOT NULL
    ) AS x
    WHERE t.id = x.id;

  END;
$BODY$
LANGUAGE plpgsql VOLATILE;

-- tickets
CREATE TABLE tbl_ticket
(
  id bigserial NOT NULL,
  parent_id bigint,
  project_id bigint NOT NULL,
  import_id bigint,
  title text,
  fahrplan_id integer NOT NULL,
  priority real NOT NULL DEFAULT 1,
  ticket_type enum_ticket_type NOT NULL,
  ticket_state enum_ticket_state NOT NULL,
  ticket_state_next enum_ticket_state,
  service_executable boolean NOT NULL default false,
  encoding_profile_version_id bigint,
  handle_id bigint,
  created timestamp with time zone NOT NULL DEFAULT now(),
  modified timestamp with time zone NOT NULL DEFAULT now(),
  failed boolean NOT NULL DEFAULT false,
  needs_attention boolean NOT NULL DEFAULT false,
  progress double precision not null default 0.0,
  CONSTRAINT tbl_ticket_pk PRIMARY KEY (id),
  CONSTRAINT tbl_ticket_encoding_profile_version_fk FOREIGN KEY (encoding_profile_version_id)
      REFERENCES tbl_encoding_profile_version (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tbl_ticket_parent_fk FOREIGN KEY (parent_id)
      REFERENCES tbl_ticket (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tbl_ticket_project_fk FOREIGN KEY (project_id)
      REFERENCES tbl_project (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tbl_ticket_import_fk FOREIGN KEY (import_id)
        REFERENCES tbl_import (id) MATCH SIMPLE
        ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT tbl_ticket_state_fk FOREIGN KEY (ticket_type, ticket_state, project_id)
      REFERENCES tbl_project_ticket_state (ticket_type, ticket_state, project_id) MATCH SIMPLE
      ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT tbl_ticket_encoding_profile_check CHECK (
CASE
    WHEN ticket_type = 'encoding'::enum_ticket_type THEN encoding_profile_version_id IS NOT NULL
    ELSE true
END),
  CONSTRAINT ticket_valid_inheritence CHECK (check_fahrplan_inheritance(parent_id, fahrplan_id) IS TRUE)
)
WITHOUT OIDS;

-- indexes
CREATE INDEX tbl_ticket_project_id_idx ON tbl_ticket USING btree(project_id);
CREATE INDEX tbl_ticket_fahrplan_id_idx ON tbl_ticket USING btree(fahrplan_id);
CREATE INDEX tbl_ticket_handle_id_idx ON tbl_ticket USING btree(handle_id);

-- partial unique index
CREATE UNIQUE INDEX unique_fahrplan_id ON tbl_ticket (project_id, fahrplan_id) WHERE parent_id IS NULL;

-- trigger
CREATE TRIGGER valid_handle BEFORE INSERT OR UPDATE ON tbl_ticket FOR EACH ROW EXECUTE PROCEDURE valid_handle();
CREATE TRIGGER state_trigger BEFORE INSERT OR UPDATE OF ticket_state ON tbl_ticket FOR EACH ROW EXECUTE PROCEDURE update_ticket_next_state();
CREATE TRIGGER progress_trigger1 AFTER INSERT OR UPDATE OF ticket_state ON tbl_ticket FOR EACH ROW EXECUTE PROCEDURE update_ticket_progress();

CREATE OR REPLACE FUNCTION inherit_fahrplan_id() RETURNS trigger AS
$BODY$
DECLARE
	fid VARCHAR;
BEGIN
	IF NEW.parent_id IS NOT NULL THEN
		SELECT fahrplan_id INTO fid FROM tbl_ticket WHERE id = NEW.parent_id;
		NEW.fahrplan_id := fid;
	END IF;
	RETURN NEW;
END;
$BODY$
LANGUAGE plpgsql VOLATILE;

CREATE TRIGGER inherit_fahrplan_id BEFORE INSERT OR UPDATE ON tbl_ticket FOR EACH ROW EXECUTE PROCEDURE inherit_fahrplan_id();

-- update encoding tickets state to "ready to encode", if recording ticket changes state to "finalized"
CREATE OR REPLACE FUNCTION update_encoding_ticket_state() RETURNS trigger AS $$
BEGIN
  IF NEW.ticket_type = 'recording' 
    AND NEW.ticket_state <> OLD.ticket_state 
    AND NEW.ticket_state = 'finalized'
    AND NEW.failed = false
    AND 'staged' = COALESCE(
      (SELECT ticket_state FROM tbl_ticket tt WHERE tt.ticket_type='meta' AND tt.id=NEW.parent_id),
      'staging') 
    THEN
    UPDATE tbl_ticket SET ticket_state = 'ready to encode' WHERE ticket_type = 'encoding' AND parent_id = NEW.parent_id AND ticket_state = 'material needed';
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_encoding_ticket_state AFTER UPDATE ON tbl_ticket FOR EACH ROW EXECUTE PROCEDURE update_encoding_ticket_state();

-- set inserted encoding tickets state to "ready to encode", if corresponding recording ticket is in "finalized"
CREATE OR REPLACE FUNCTION set_encoding_ticket_state() RETURNS trigger AS $$
BEGIN
  IF NEW.ticket_type = 'encoding' 
    AND 'finalized' = COALESCE(
      (SELECT ticket_state FROM tbl_ticket tt WHERE tt.ticket_type='recording' AND tt.parent_id=NEW.parent_id),
      'scheduled')
    AND false = COALESCE(
      (SELECT failed FROM tbl_ticket tt WHERE tt.ticket_type='recording' AND tt.parent_id=NEW.parent_id),
      true)
    AND 'staged' = COALESCE(
      (SELECT ticket_state FROM tbl_ticket tt WHERE tt.ticket_type='meta' AND tt.id=NEW.parent_id),
      'staging') 
    THEN
    NEW.ticket_state := 'ready to encode';
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER set_encoding_ticket_state BEFORE INSERT ON tbl_ticket FOR EACH ROW EXECUTE PROCEDURE set_encoding_ticket_state();

-- ticket properties
CREATE TABLE tbl_ticket_property
(
  ticket_id bigint NOT NULL,
  name ltree NOT NULL CHECK (char_length(name::text) > 0),
  value character varying(8196) NOT NULL,
  CONSTRAINT tbl_ticket_property_pk PRIMARY KEY (ticket_id, name),
  CONSTRAINT tbl_ticket_property_ticket_fk FOREIGN KEY (ticket_id)
      REFERENCES tbl_ticket (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITHOUT OIDS;

COMMIT;
