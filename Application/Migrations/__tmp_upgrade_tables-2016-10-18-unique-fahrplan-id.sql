-- partial unique index
CREATE UNIQUE INDEX unique_fahrplan_id ON tbl_ticket (project_id, fahrplan_id) WHERE parent_id IS NULL;

