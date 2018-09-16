
\i 18_function_ticket_dependency_missing.sql

\i 22_view_serviceable_tickets.sql

DO language plpgsql $$
BEGIN
  RAISE WARNING 'DO NOT FORGET: it is very likely that you have to give GRANT on the recreated view_serviceable_tickets to your tracker DB user after executing this script!';
END
$$;
