BEGIN;

SET ROLE TO postgres;

-------------------------------
--- ticket types and states ---
-------------------------------
CREATE TYPE enum_ticket_type AS ENUM (
	'meta', 
	'recording', 
	'encoding', 
	'ingest');

CREATE TYPE enum_ticket_state AS ENUM(
	'archived', 
	'archiving', 
	'checked', 
	'checking', 
	'closed', 
	'cut', 
	'cutting', 
	'encoded',
	'encoding', 
	'finalized', 
	'finalizing', 
	'gone', 
	'incomplete', 
	'ingested', 
	'ingesting', 
	'locked', 
	'material needed', 
	'postencoded', 
	'postencoding', 
	'postprocessed', 
	'postprocessing', 
	'prepared', 
	'preparing', 
	'ready to archive', 
	'ready to encode', 
	'ready to ingest', 
	'ready to release',
	'ready to remove', 
	'recorded', 
	'recording', 
	'released', 
	'releasing', 
	'removing', 
	'scheduled', 
	'staged', 
	'staging');

CREATE TABLE tbl_ticket_state
(
  ticket_type enum_ticket_type NOT NULL,
  ticket_state enum_ticket_state NOT NULL,
  sort bigint NOT NULL DEFAULT 1,
  percent_progress double precision DEFAULT 0.0,
  service_executable boolean NOT NULL DEFAULT false,
  CONSTRAINT tbl_ticket_state_pk PRIMARY KEY (ticket_type, ticket_state)
)
WITHOUT OIDS;

CREATE OR REPLACE FUNCTION increment_ticket_state_sort() RETURNS trigger AS
$BODY$
  DECLARE
	srt integer;
  BEGIN
	SELECT COALESCE(MAX(sort),0) + 1 INTO srt FROM tbl_ticket_state WHERE ticket_type = NEW.ticket_type;

	NEW.sort := srt;
	
	RETURN NEW;
  END;
$BODY$
  LANGUAGE plpgsql VOLATILE;

CREATE TRIGGER increment_ticket_state_sort BEFORE INSERT ON tbl_ticket_state FOR EACH ROW EXECUTE PROCEDURE increment_ticket_state_sort();


-- meta tickets
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('meta', 'staging', 0, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('meta', 'staged', 50, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('meta', 'closed', 50, false, false);

-- recording tickets
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('recording', 'locked', 0, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('recording', 'scheduled', 5, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('recording', 'recording', 40, true, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('recording', 'recorded', 5, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('recording', 'preparing', 10, true, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('recording', 'prepared', 5, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('recording', 'cutting', 30, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('recording', 'cut', 5, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('recording', 'finalizing', 10, true, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('recording', 'finalized', 5, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('recording', 'ready to archive', 0, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('recording', 'archiving', 0, true, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('recording', 'archived', 0, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('recording', 'ready to remove', 0, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('recording', 'removing', 0, true, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('recording', 'gone', 0, false, false);

-- encoding tickets
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('encoding', 'material needed', 0, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('encoding', 'ready to encode', 5, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('encoding', 'encoding', 50, true, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('encoding', 'encoded', 5, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('encoding', 'postencoding', 10, true, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('encoding', 'postencoded', 5, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('encoding', 'checking', 20, false, true);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('encoding', 'checked', 5, false, true);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('encoding', 'postprocessing', 10, true, true);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('encoding', 'postprocessed', 5, false, true);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('encoding', 'ready to release', 5, false, true);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('encoding', 'releasing', 20, true, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('encoding', 'released', 5, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('encoding', 'ready to remove', 0, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('encoding', 'removing', 0, true, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('encoding', 'gone', 0, false, false);

-- ingest tickets
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('ingest', 'incomplete', 0, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('ingest', 'ready to ingest', 5, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('ingest', 'ingesting', 50, true, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('ingest', 'ingested', 5, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('ingest', 'finalizing', 10, true, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('ingest', 'finalized', 5, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('ingest', 'ready to archive', 5, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('ingest', 'archiving', 30, true, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('ingest', 'archived', 5, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('ingest', 'ready to remove', 5, false, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('ingest', 'removing', 20, true, false);
INSERT INTO tbl_ticket_state (ticket_type, ticket_state, percent_progress, service_executable, skippable_on_dependent) VALUES ('ingest', 'gone', 5, false, false);

-- mark non-initial states by giving sort <= 0
UPDATE tbl_ticket_state SET sort = 0 where ticket_state = 'locked';
UPDATE tbl_ticket_state SET sort = 0 where ticket_state = 'incomplete';

COMMIT;
