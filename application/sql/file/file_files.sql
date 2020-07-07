DROP TABLE IF EXISTS file_files CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE file_files (
    file_id             varchar(256) NOT NULL,
    file_dir            varchar(256),
    
    file_name           varchar(256),
    file_type           varchar(256),
    file_size           bigint,
    
    public_location     boolean,
    
    --CONSTRAINT valid_space CHECK ((com_id > 0 AND net_id = 0 AND via_id = 0) OR (com_id = 0 AND net_id > 0 AND via_id = 0) OR (com_id = 0 AND net_id = 0 AND via_id > 0)),
    FOREIGN KEY (com_id, net_id, via_id, module_id, matrix_counter) REFERENCES module_matrix (com_id, net_id, via_id, module_id, counter) MATCH SIMPLE ON DELETE CASCADE ON UPDATE CASCADE
)
INHERITS (module_template);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX file_files_com_net_via_matrix_counter_counter_x ON file_files (com_id, net_id, via_id, matrix_counter, counter);
CREATE UNIQUE INDEX file_files_file_id_file_dir_x ON file_files (file_id, file_dir);
--CREATE INDEX file_files_com_matrix_counter_profile_active_x ON file_files (com_id, matrix_counter, profile_id, active);
--CREATE INDEX file_files_net_matrix_counter_profile_active_x ON file_files (net_id, matrix_counter, profile_id, active);
--CREATE INDEX file_files_via_matrix_counter_profile_active_x ON file_files (via_id, matrix_counter, profile_id, active);

CREATE INDEX file_files_search_index ON file_files USING gin(search);

ALTER TABLE public.file_files OWNER TO vmdbuser;

-- Dynamic check module_matrix
CREATE TRIGGER "file_files_check_module_matrix_trigger" BEFORE INSERT ON "file_files" FOR EACH ROW EXECUTE PROCEDURE "check_module_matrix" ();

--Update the updated field on updates
CREATE TRIGGER "file_files_updated_trigger" BEFORE UPDATE ON "file_files" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Dynamic counter increment
CREATE TRIGGER "file_files_counter_trigger" BEFORE INSERT ON "file_files" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'com_id', 'net_id', 'via_id', 'matrix_counter');

-- Update the search field
CREATE TRIGGER "file_files_update_search_trigger" BEFORE INSERT OR UPDATE ON "file_files" FOR EACH ROW EXECUTE PROCEDURE "update_search" ('search', 'file_name', 'C', 'title', 'B', 'heading', 'C', 'summary', 'C', 'meta_title', 'D', 'meta_description', 'D', 'meta_keywords', 'D');

--Update the total_item_count in module_matrix
CREATE TRIGGER "file_files_total_item_count_trigger" AFTER INSERT OR UPDATE OR DELETE ON "file_files" FOR EACH ROW EXECUTE PROCEDURE "update_total_count" ('module_matrix', 'total_item_count', 'false', 'com_id', 'com_id', 'net_id', 'net_id', 'via_id', 'via_id', 'module_id', 'module_id', 'counter', 'matrix_counter');


----------------------------------------------------------------------------------------------------


