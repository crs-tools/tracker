
DROP VIEW view_parent_tickets;
DROP VIEW view_all_tickets;
DROP VIEW view_serviceable_tickets;

ALTER TABLE tbl_ticket DROP COLUMN repairing;

\i 20_view_parent_tickets.sql
\i 21_view_all_tickets.sql
\i 22_view_serviceable_tickets.sql

