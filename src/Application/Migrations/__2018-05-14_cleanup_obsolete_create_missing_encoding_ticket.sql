BEGIN;

SET ROLE TO postgres;

DROP FUNCTION create_missing_encoding_ticket(bigint, bigint);

COMMIT;