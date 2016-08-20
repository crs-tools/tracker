-- copy all needs_attention on master tickets:
UPDATE tbl_ticket
 SET needs_attention = true
 WHERE ticket_type = 'meta'
  AND id IN 
   (SELECT DISTINCT parent_id FROM tbl_ticket t2
    WHERE parent_id IS NOT NULL 
     AND needs_attention = true)
;

-- now clear the flag on the children
UPDATE tbl_ticket
 SET needs_attention = false
 WHERE ticket_type <> 'meta'
;

