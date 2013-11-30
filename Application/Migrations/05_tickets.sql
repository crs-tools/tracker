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

-- tickets
CREATE TABLE tbl_ticket
(
  id bigserial NOT NULL,
  parent_id bigint,
  project_id bigint NOT NULL,
  title character varying(128) NOT NULL,
  fahrplan_id integer NOT NULL,
  priority real NOT NULL,
  ticket_type enum_ticket_type NOT NULL,
  ticket_state enum_ticket_state NOT NULL,
  encoding_profile_version_id bigint,
  handle_id bigint,
  created timestamp without time zone NOT NULL DEFAULT now(),
  modified timestamp without time zone NOT NULL DEFAULT now(),
  failed boolean NOT NULL DEFAULT false,
  needs_attention boolean NOT NULL DEFAULT false,
  repairing character varying(128),
  CONSTRAINT tbl_ticket_pk PRIMARY KEY (id),
  CONSTRAINT tbl_ticket_encoding_profile_version_fk FOREIGN KEY (encoding_profile_version_id)
      REFERENCES tbl_encoding_profile_version (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tbl_ticket_handle_id_fkey FOREIGN KEY (handle_id)
      REFERENCES tbl_handle (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
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

-- trigger
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

CREATE TABLE tbl_ticket_property
(
  ticket_id bigint NOT NULL,
  name ltree NOT NULL,
  value character varying(8196) NOT NULL,
  CONSTRAINT tbl_ticket_property_pk PRIMARY KEY (ticket_id, name),
  CONSTRAINT tbl_ticket_property_ticket_fk FOREIGN KEY (ticket_id)
      REFERENCES tbl_ticket (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITHOUT OIDS;

COMMIT;