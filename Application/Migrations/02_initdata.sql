BEGIN;

-- tbl_ticket_type
INSERT INTO tbl_ticket_type (id, name, disabled) VALUES (1, 'recording task', false);
INSERT INTO tbl_ticket_type (id, name, disabled) VALUES (2, 'encoding task', false);
INSERT INTO tbl_ticket_type (id, name, disabled) VALUES (3, 'miscellaneous task', false);

-- tbl_state
-- recording task
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (1, 'locked', 1, 0);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (2, 'scheduled', 1, 0);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (3, 'recording', 1, 12.5);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (4, 'recorded', 1, 25);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (5, 'merging', 1, 37.5);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (6, 'merged', 1, 50);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (7, 'cutting', 1, 62.5);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (8, 'fixing', 1, 50);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (9, 'cut', 1, 75);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (10, 'copying', 1, 87.5);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (11, 'copied', 1, 100);

-- encoding task
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (12, 'material needed', 2, 0);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (13, 'ready to encode', 2, 5);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (14, 'encoding', 2, 10);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (15, 'encoded', 2, 55);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (16, 'tagging', 2, 60);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (17, 'tagged', 2, 65);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (18, 'checking', 2, 70);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (19, 'checked', 2, 75);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (20, 'postprocessing', 2, 80);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (21, 'postprocessed', 2, 85);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (22, 'ready to release', 2, 90);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (23, 'releasing', 2, 95);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (24, 'released', 2, 100);
-- miscellaneous task
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (25, 'open', 3, 0);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (26, 'inprogress', 3, 50);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (27, 'resolved', 3, 100);
INSERT INTO tbl_state (id, name, ticket_type_id, percent_progress) VALUES (28, 'wontfix', 3, 100);

INSERT INTO tbl_log_message (id, event, message, rcp, feed_message, feed_include_log, feed_message_multiple) VALUES ('7', 'RPC.Log', null, 't', null, 'f', null);
INSERT INTO tbl_log_message (id, event, message, rcp, feed_message, feed_include_log, feed_message_multiple) VALUES ('12', 'Comment.Add', 'Comment added.', 'f', '{user_name} commented on {id}.', 'f', null);
INSERT INTO tbl_log_message (id, event, message, rcp, feed_message, feed_include_log, feed_message_multiple) VALUES ('17', 'Action.Fix', 'Fixed.', 'f', '{user_name} fixed ticket {id}.', 'f', null);
INSERT INTO tbl_log_message (id, event, message, rcp, feed_message, feed_include_log, feed_message_multiple) VALUES ('11', 'Encoding.Reset', 'Encoding task has been reset.', 'f', '{user_name} reset encoding task {id}.', 'f', null);
INSERT INTO tbl_log_message (id, event, message, rcp, feed_message, feed_include_log, feed_message_multiple) VALUES ('10', 'Recording.Reset', 'Task has been reset.', 'f', '{user_name} reset recording task {id}.', 'f', null);
INSERT INTO tbl_log_message (id, event, message, rcp, feed_message, feed_include_log, feed_message_multiple) VALUES ('6', 'RPC.State.Next', 'State changed to {to_state}.', 't', '{user_name} set ticket {id} as {to_state}.', 'f', '{user_name} set {tickets} as {to_state}.');
INSERT INTO tbl_log_message (id, event, message, rcp, feed_message, feed_include_log, feed_message_multiple) VALUES ('3', 'RPC.Processing.Start', '{to_State} started.', 't', '{user_name} started {to_state} ticket {id}.', 'f', '{user_name} started {to_state} {tickets}.');
INSERT INTO tbl_log_message (id, event, message, rcp, feed_message, feed_include_log, feed_message_multiple) VALUES ('18', 'Action.Cut.Failed', 'Cutting failed.', 'f', '{user_name} failed to cut {id}.', 'f', '{user_name} failed to cut {tickets}.');
INSERT INTO tbl_log_message (id, event, message, rcp, feed_message, feed_include_log, feed_message_multiple) VALUES ('20', 'Action.Cut.Expand', 'Expanded recording time.', 'f', '{user_name} expanded ticket {id} recording time.', 'f', null);
INSERT INTO tbl_log_message (id, event, message, rcp, feed_message, feed_include_log, feed_message_multiple) VALUES ('2', 'RPC.Property.Set', 'Properties changed.', 't', '{user_name} altered properties of ticket {id}.', 'f', '{user_name} altered properties of {tickets}.');
INSERT INTO tbl_log_message (id, event, message, rcp, feed_message, feed_include_log, feed_message_multiple) VALUES ('1', 'RPC.Ping.Command', 'Issued command.', 't', 'Issued command to {user_name} regarding ticket {id}:', 't', null);
INSERT INTO tbl_log_message (id, event, message, rcp, feed_message, feed_include_log, feed_message_multiple) VALUES ('14', 'Action.Check', 'Encoding checked.', 'f', '{user_name} checked encoding ticket {id}.', 'f', '{user_name} checked {tickets}.');
INSERT INTO tbl_log_message (id, event, message, rcp, feed_message, feed_include_log, feed_message_multiple) VALUES ('5', 'RPC.Processing.Failed', '{to_State} failed.', 't', '{user_name} failed {from_state} ticket {id}.', 'f', '{user_name} failed {from_state} {tickets}.');
INSERT INTO tbl_log_message (id, event, message, rcp, feed_message, feed_include_log, feed_message_multiple) VALUES ('13', 'Encoding.Parent.Reset', 'Task has been reset while recording task was beeing reset.', 'f', '{user_name} reset ticket {id} while recording task was beeing reset.', 'f', '{user_name} reset {tickets} while recording tasks were beeing reset.');
INSERT INTO tbl_log_message (id, event, message, rcp, feed_message, feed_include_log, feed_message_multiple) VALUES ('4', 'RPC.Processing.Done', '{from_State} finished.', 't', '{user_name} finished {from_state} ticket {id}.', 'f', '{user_name} finished {from_state} {tickets}.');
INSERT INTO tbl_log_message (id, event, message, rcp, feed_message, feed_include_log, feed_message_multiple) VALUES ('15', 'Action.Check.Failed', 'Encoding check failed.', 'f', '{user_name} failed to check encoding ticket {id}.', 'f', '{user_name} failed to check {tickets}.');
INSERT INTO tbl_log_message (id, event, message, rcp, feed_message, feed_include_log, feed_message_multiple) VALUES ('16', 'Action.Cut', 'Recording cut.', 'f', '{user_name} cut recording ticket {id}.', 'f', '{user_name} cut {tickets}.');
INSERT INTO tbl_log_message (id, event, message, rcp, feed_message, feed_include_log, feed_message_multiple) VALUES ('9', 'Created', 'Ticket created.', 'f', '{user_name} created ticket {id}.', 'f', '{user_name} created {tickets}.');

COMMIT;