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

-- trigger function to update ticket progress

CREATE OR REPLACE FUNCTION update_ticket_progress_and_next_state()
  RETURNS trigger AS
$BODY$
  BEGIN
    UPDATE tbl_ticket SET 
      progress = ticket_progress(NEW.id),
      ticket_state_next = (
        SELECT ticket_state_next.ticket_state FROM ticket_state_next(NEW.project_id, NEW.ticket_type, NEW.ticket_state)
      ),
      service_executable = COALESCE((
        SELECT ticket_state_next.service_executable FROM ticket_state_next(NEW.project_id, NEW.ticket_type, NEW.ticket_state)
      ), false)
      WHERE id = NEW.id;
    UPDATE tbl_ticket SET progress = ticket_progress(NEW.parent_id)
      WHERE NEW.parent_id IS NOT NULL AND id = NEW.parent_id;
  RETURN NULL;
  END;
$BODY$
LANGUAGE plpgsql VOLATILE;

-- trigger function to update progress of all tickets

CREATE OR REPLACE FUNCTION update_all_tickets_progress_and_next_state()
  RETURNS trigger AS
$BODY$
  BEGIN
    UPDATE tbl_ticket t SET 
      progress = ticket_progress(id),
      ticket_state_next = (
        SELECT ticket_state_next.ticket_state FROM ticket_state_next(t.project_id, t.ticket_type, t.ticket_state)
      ), 
      service_executable = COALESCE((
        SELECT COALESCE(ticket_state_next.service_executable, false) FROM ticket_state_next(t.project_id, t.ticket_type, t.ticket_state)
      ),false);
  RETURN NULL;
  END;
$BODY$
LANGUAGE plpgsql VOLATILE;

-- tickets
CREATE TABLE tbl_ticket
(
  id bigserial NOT NULL,
  parent_id bigint,
  project_id bigint NOT NULL,
  title character varying(128) NOT NULL,
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
  repairing character varying(128),
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

-- trigger
CREATE TRIGGER valid_handle BEFORE INSERT OR UPDATE ON tbl_ticket FOR EACH ROW EXECUTE PROCEDURE valid_handle();
CREATE TRIGGER progress_trigger1 AFTER INSERT OR UPDATE OF ticket_state ON tbl_ticket FOR EACH ROW EXECUTE PROCEDURE update_ticket_progress_and_next_state();
CREATE TRIGGER progress_trigger2 AFTER INSERT OR UPDATE OR DELETE ON tbl_project_ticket_state FOR EACH STATEMENT EXECUTE PROCEDURE update_all_tickets_progress_and_next_state();

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

-- update child tickets title, when title of meta ticket is changed
CREATE OR REPLACE FUNCTION update_child_ticket_title() RETURNS trigger AS $$
  DECLARE
	profile record;
  BEGIN
	IF NEW.ticket_type = 'meta' AND NEW.title <> OLD.title THEN
		-- update encoding tickets: set title to "<title> (<encoding_profile.name>)"
		UPDATE tbl_ticket t SET title = (SELECT CONCAT(NEW.title,' (',ep.name,')') FROM tbl_encoding_profile ep JOIN tbl_encoding_profile_version epv ON epv.encoding_profile_id = ep.id WHERE t.encoding_profile_version_id = epv.id) WHERE ticket_type = 'encoding' AND parent_id = NEW.id;
	END IF;
	RETURN NEW;
  END;
$$
LANGUAGE plpgsql;

CREATE TRIGGER update_child_ticket_title AFTER UPDATE ON tbl_ticket FOR EACH ROW EXECUTE PROCEDURE update_child_ticket_title();

-- update encoding tickets state to "ready to encode", if recording ticket changes state to "finalized"
CREATE OR REPLACE FUNCTION update_encoding_ticket_state() RETURNS trigger AS $$
DECLARE
  profile record;
BEGIN
  IF NEW.ticket_type = 'recording' AND NEW.ticket_state <> OLD.ticket_state AND NEW.ticket_state = 'finalized' THEN
    UPDATE tbl_ticket SET ticket_state = 'ready to encode' WHERE ticket_type = 'encoding' AND parent_id = NEW.parent_id AND ticket_state = 'material needed';
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_encoding_ticket_state AFTER UPDATE ON tbl_ticket FOR EACH ROW EXECUTE PROCEDURE update_encoding_ticket_state();

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
