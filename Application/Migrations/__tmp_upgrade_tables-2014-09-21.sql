-- costraint against empty property names
alter table tbl_ticket_property ADD CONSTRAINT name_length CHECK (char_length(name::text) > 0);

-- timezone stuff
alter table tbl_handle alter column last_seen TYPE timestamp with time zone;
alter table tbl_user alter column last_login TYPE timestamp with time zone;

alter table tbl_encoding_profile_version alter column created TYPE timestamp with time zone;

DROP VIEW IF EXISTS view_serviceable_tickets; 
DROP VIEW IF EXISTS view_all_tickets; 
DROP VIEW IF EXISTS view_parent_tickets; 

alter table tbl_project alter column created  TYPE timestamp with time zone;
alter table tbl_project alter column modified TYPE timestamp with time zone;

alter table tbl_ticket alter column created  TYPE timestamp with time zone;
alter table tbl_ticket alter column modified TYPE timestamp with time zone;

alter table tbl_comment alter column created  TYPE timestamp with time zone;

alter table tbl_log alter column created  TYPE timestamp with time zone;


-- performance stuff



CREATE OR REPLACE FUNCTION update_ticket_progress_and_next_state()
  RETURNS trigger AS
$BODY$
  BEGIN
    UPDATE tbl_ticket SET
      progress = ticket_progress(NEW.id),
      ticket_state_next = (
        SELECT ticket_state_next.ticket_state FROM ticket_state_next(NEW.project_id, NEW.ticket_type, NEW.ticket_state)
      ),
      service_executable = (
        SELECT COALESCE(ticket_state_next.service_executable, false) FROM ticket_state_next(NEW.project_id, NEW.ticket_type, NEW.ticket_state)
      )
      WHERE id = NEW.id;
    UPDATE tbl_ticket SET progress = ticket_progress(NEW.parent_id)
      WHERE NEW.parent_id IS NOT NULL AND id = NEW.parent_id;
  RETURN NULL;
  END;
$BODY$
LANGUAGE plpgsql VOLATILE;



CREATE OR REPLACE FUNCTION update_all_tickets_progress_and_next_state()
  RETURNS trigger AS
$BODY$
  BEGIN
    UPDATE tbl_ticket t SET
      progress = ticket_progress(id),
      ticket_state_next = (
        SELECT ticket_state_next.ticket_state FROM ticket_state_next(t.project_id, t.ticket_type, t.ticket_state)
      ),
      service_executable = (
        SELECT COALESCE(ticket_state_next.service_executable, false) FROM ticket_state_next(t.project_id, t.ticket_type, t.ticket_state)
      );
  RETURN NULL;
  END;
$BODY$
LANGUAGE plpgsql VOLATILE;


alter table tbl_ticket drop column ticket_next_state ;
alter table tbl_ticket add column ticket_state_next enum_ticket_state;
alter table tbl_ticket add column service_executable boolean NOT NULL default false;
alter table tbl_ticket add column progress double precision not null default 0.0;

CREATE TRIGGER progress_trigger1 AFTER INSERT OR UPDATE OF ticket_state ON tbl_ticket FOR EACH ROW EXECUTE PROCEDURE update_ticket_progress_and_next_state();
CREATE TRIGGER progress_trigger2 AFTER INSERT OR UPDATE OR DELETE ON tbl_project_ticket_state FOR EACH STATEMENT EXECUTE PROCEDURE update_all_tickets_progress_and_next_state();



-- reexecute 11_*.sql
-- reexecute 20_*.sql
-- reexecute 21_*.sql
-- reexecute 22_*.sql

\i 11_function_ticket_fahrplan_starttime.sql
\i 20_view_parent_tickets.sql
\i 21_view_all_tickets.sql
\i 22_view_serviceable_tickets.sql


