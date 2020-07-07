DROP TABLE IF EXISTS page_pages CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE page_pages (
    content             text NOT NULL,
    
    disable_cm          boolean,
    disable_sublayouts  boolean,
    disable_layouts     boolean,
    page_code           varchar(32),
    
    parent_id           bigint, --  CHECK (parent_id < counter) Delay Reference Check Until After Table Creation
    
    --CONSTRAINT valid_space CHECK ((com_id > 0 AND net_id = 0 AND via_id = 0) OR (com_id = 0 AND net_id > 0 AND via_id = 0) OR (com_id = 0 AND net_id = 0 AND via_id > 0)),
    FOREIGN KEY (com_id, net_id, via_id, module_id, matrix_counter) REFERENCES module_matrix (com_id, net_id, via_id, module_id, counter) MATCH SIMPLE ON DELETE CASCADE ON UPDATE CASCADE
)
INHERITS (module_template);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX page_pages_com_net_via_matrix_counter_counter_x ON page_pages (com_id, net_id, via_id, matrix_counter, counter);
--CREATE INDEX page_pages_com_matrix_counter_profile_active_x ON page_pages (com_id, matrix_counter, profile_id, active);
--CREATE INDEX page_pages_net_matrix_counter_profile_active_x ON page_pages (net_id, matrix_counter, profile_id, active);
--CREATE INDEX page_pages_via_matrix_counter_profile_active_x ON page_pages (via_id, matrix_counter, profile_id, active);

CREATE INDEX page_pages_search_index ON page_pages USING gin(search);

ALTER TABLE public.page_pages OWNER TO vmdbuser;

ALTER TABLE ONLY page_pages ADD CONSTRAINT page_pages_parent_fkey FOREIGN KEY (com_id, net_id, via_id, matrix_counter, parent_id) REFERENCES page_pages(com_id, net_id, via_id, matrix_counter, counter) ON UPDATE CASCADE ON DELETE CASCADE;

-- Dynamic check module_matrix
CREATE TRIGGER "page_pages_check_module_matrix_trigger" BEFORE INSERT ON "page_pages" FOR EACH ROW EXECUTE PROCEDURE "check_module_matrix" ();

--Update the updated field on updates
CREATE TRIGGER "page_pages_updated_trigger" BEFORE UPDATE ON "page_pages" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Dynamic counter increment
CREATE TRIGGER "page_pages_counter_trigger" BEFORE INSERT ON "page_pages" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'com_id', 'net_id', 'via_id', 'matrix_counter');

-- Update the search field
CREATE TRIGGER "page_pages_update_search_trigger" BEFORE INSERT OR UPDATE ON "page_pages" FOR EACH ROW EXECUTE PROCEDURE "update_search" ('search', 'content', 'A', 'title', 'B', 'heading', 'C', 'summary', 'C', 'meta_title', 'D', 'meta_description', 'D', 'meta_keywords', 'D');

--Update the total_item_count in module_matrix
CREATE TRIGGER "page_pages_total_item_count_trigger" AFTER INSERT OR UPDATE OR DELETE ON "page_pages" FOR EACH ROW EXECUTE PROCEDURE "update_total_count" ('module_matrix', 'total_item_count', 'false', 'com_id', 'com_id', 'net_id', 'net_id', 'via_id', 'via_id', 'module_id', 'module_id', 'counter', 'matrix_counter');


----------------------------------------------------------------------------------------------------


