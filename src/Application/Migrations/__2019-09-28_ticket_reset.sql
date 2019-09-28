BEGIN;
	
SET ROLE TO postgres;
	
-- function also resets handle_id and failed flag
CREATE OR REPLACE FUNCTION ticket_reset(param_ticket_id bigint, param_ticket_types enum_ticket_type[] DEFAULT array[]::enum_ticket_type[])
  RETURNS TABLE (ticket_id bigint,  from_state enum_ticket_state, to_state enum_ticket_state, follows_meta_ticket boolean, follows_encoding_ticket boolean) AS
$$
DECLARE
  ticket tbl_ticket%rowtype;
  inital_state enum_ticket_state;
BEGIN
  --first get the full given ticket
  SELECT * INTO ticket FROM tbl_ticket where id = param_ticket_id;

  IF NOT FOUND THEN
    RAISE WARNING 'ticket with id % does not exist', param_ticket_id;
    RETURN;
  END IF;

  --reset the given ticket to inital state, but only if it matches the ticket type filter
  IF array_length(param_ticket_types, 1) IS NULL OR (array_position(param_ticket_types, ticket.ticket_type) IS NOT NULL) THEN
    inital_state := ticket_state_initial(ticket.project_id, ticket.ticket_type);
    UPDATE tbl_ticket SET (failed, handle_id) = (FALSE, NULL) WHERE id = ticket.id;
    IF inital_state <> ticket.ticket_state THEN
      UPDATE tbl_ticket SET ticket_state=inital_state WHERE id = param_ticket_id;
      ticket_id := ticket.id;
      from_state := ticket.ticket_state;
      to_state := inital_state;
      follows_meta_ticket := FALSE;
      follows_encoding_ticket := FALSE;
      RETURN NEXT;
    END IF;
  END IF;

  --reset the children of the given ticket
  FOR ticket IN
  SELECT * FROM tbl_ticket WHERE parent_id = param_ticket_id AND (array_length(param_ticket_types, 1) IS NULL OR (array_position(param_ticket_types, ticket_type) IS NOT NULL))
  LOOP
    inital_state := ticket_state_initial(ticket.project_id, ticket.ticket_type);
    UPDATE tbl_ticket SET (failed, handle_id) = (FALSE, NULL) WHERE id = ticket.id;
    IF inital_state <> ticket.ticket_state THEN
      UPDATE tbl_ticket SET ticket_state = inital_state WHERE id = ticket.id;
      ticket_id := ticket.id;
      from_state := ticket.ticket_state;
      to_state := inital_state;
      follows_meta_ticket := TRUE;
      follows_encoding_ticket := FALSE;
      RETURN NEXT;
    END IF;
  END LOOP;

  --reset all tickets with encoding profiles depending on given ticket's profile
  FOR ticket IN
    SELECT
      t2.*
    FROM
      tbl_ticket t2
    JOIN
      tbl_encoding_profile_version epv2 ON t2.encoding_profile_version_id = epv2.id
    JOIN (
      SELECT
        encoding_profile_all_dependees(epv.encoding_profile_id) AS id,
        tt.parent_id
      FROM
        tbl_ticket tt
      JOIN
        tbl_encoding_profile_version epv ON tt.encoding_profile_version_id = epv.id
      WHERE
        tt.id = param_ticket_id AND
        (array_length(param_ticket_types, 1) IS NULL OR (array_position(param_ticket_types, tt.ticket_type) IS NOT NULL))
    ) pd ON pd.id = epv2.encoding_profile_id AND t2.parent_id = pd.parent_id
  LOOP
    inital_state := ticket_state_initial(ticket.project_id, ticket.ticket_type);
    UPDATE tbl_ticket SET (failed, handle_id) = (FALSE, NULL) WHERE id = ticket.id;
    IF inital_state <> ticket.ticket_state THEN
      UPDATE tbl_ticket SET ticket_state = inital_state WHERE id = ticket.id;
      ticket_id := ticket.id;
      from_state := ticket.ticket_state;
      to_state := inital_state;
      follows_meta_ticket := FALSE;
      follows_encoding_ticket := TRUE;
      RETURN NEXT;
    END IF;
  END LOOP;

END
$$
LANGUAGE plpgsql;

-- function returns a table with all encoding profiles, which depend
-- on the given encoding profile

-- second parameter is optional and is used in recursive to prevent endless loop

CREATE OR REPLACE FUNCTION encoding_profile_all_dependees(param_profile_id bigint, param_known_ids bigint[] DEFAULT array[]::bigint[])
  RETURNS TABLE (id bigint) AS
$$
DECLARE
  p_id bigint;
  dependee_profile_ids bigint[];
BEGIN
  IF array_dims(param_known_ids) ISNULL THEN
    param_known_ids := array[param_profile_id];
  END IF;

  FOR p_id IN
  SELECT p.id
  FROM tbl_encoding_profile p
  WHERE depends_on=param_profile_id AND NOT (p.id = ANY(param_known_ids))
  LOOP
    dependee_profile_ids := p_id || dependee_profile_ids;
    dependee_profile_ids := ARRAY(SELECT e.id from encoding_profile_all_dependees(p_id, dependee_profile_ids || param_known_ids) e) || dependee_profile_ids;
  END LOOP;
  RETURN QUERY SELECT DISTINCT unnest(dependee_profile_ids);
END
$$
LANGUAGE plpgsql;

COMMIT;
