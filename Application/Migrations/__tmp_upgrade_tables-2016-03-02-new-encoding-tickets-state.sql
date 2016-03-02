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


