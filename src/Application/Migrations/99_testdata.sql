BEGIN;

--SET search_path TO test;

INSERT INTO tbl_project (title, slug) VALUES ('test project', 'test');

INSERT INTO tbl_encoding_profile (name, slug, extension) VALUES ('format 1', 'fmt1', 'ext1');
INSERT INTO tbl_encoding_profile (name, slug, extension) VALUES ('format 2', 'fmt2', 'ext2');

INSERT INTO tbl_encoding_profile_version (encoding_profile_id, xml_template) VALUES (1, 'bla');
INSERT INTO tbl_encoding_profile_version (encoding_profile_id, xml_template) VALUES (1, 'bla');
INSERT INTO tbl_encoding_profile_version (encoding_profile_id, xml_template) VALUES (1, 'bla neu');
INSERT INTO tbl_encoding_profile_version (encoding_profile_id, xml_template) VALUES (2, 'blubb');
INSERT INTO tbl_encoding_profile_version (encoding_profile_id, xml_template) VALUES (2, 'blubb');
INSERT INTO tbl_encoding_profile_version (encoding_profile_id, xml_template) VALUES (2, 'blubb');
INSERT INTO tbl_encoding_profile_version (encoding_profile_id, xml_template) VALUES (2, 'blubb neu');

INSERT INTO tbl_project_encoding_profile (project_id, encoding_profile_version_id) VALUES (1, 3);
INSERT INTO tbl_project_encoding_profile (project_id, encoding_profile_version_id) VALUES (1, 7);

INSERT INTO tbl_project_ticket_state (project_id, ticket_type, ticket_state, service_executable) (SELECT 1 as project_id, ticket_type, ticket_state, service_executable FROM tbl_ticket_state WHERE ticket_type <> 'ingest');

INSERT INTO tbl_ticket (project_id, title, fahrplan_id, priority, ticket_type, ticket_state) VALUES (1, 'test ticket', 1, 1, 'meta', 'staging');

INSERT INTO tbl_ticket_property (ticket_id, name, value) VALUES (1, 'Fahrplan.Date', '2010-01-01');
INSERT INTO tbl_ticket_property (ticket_id, name, value) VALUES (1, 'Fahrplan.Start', '10:00');
INSERT INTO tbl_ticket_property (ticket_id, name, value) VALUES (1, 'Fahrplan.Duration', '01:00');

SELECT create_missing_encoding_tickets(1,null);

COMMIT;