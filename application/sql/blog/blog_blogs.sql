DROP TABLE IF EXISTS blog_blogs CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE blog_blogs (
    content             text NOT NULL,
    
    --CONSTRAINT valid_space CHECK ((com_id > 0 AND net_id = 0 AND via_id = 0) OR (com_id = 0 AND net_id > 0 AND via_id = 0) OR (com_id = 0 AND net_id = 0 AND via_id > 0)),
    FOREIGN KEY (com_id, net_id, via_id, module_id, matrix_counter) REFERENCES module_matrix (com_id, net_id, via_id, module_id, counter) MATCH SIMPLE ON DELETE CASCADE ON UPDATE CASCADE
)
INHERITS (module_template);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX blog_blogs_com_net_via_matrix_counter_counter_x ON blog_blogs (com_id, net_id, via_id, matrix_counter, counter);
--CREATE INDEX blog_blogs_com_matrix_counter_profile_active_x ON blog_blogs (com_id, matrix_counter, profile_id, active);
--CREATE INDEX blog_blogs_net_matrix_counter_profile_active_x ON blog_blogs (net_id, matrix_counter, profile_id, active);
--CREATE INDEX blog_blogs_via_matrix_counter_profile_active_x ON blog_blogs (via_id, matrix_counter, profile_id, active);

CREATE INDEX blog_blogs_search_index ON blog_blogs USING gin(search);

ALTER TABLE public.blog_blogs OWNER TO vmdbuser;

-- Dynamic check module_matrix
CREATE TRIGGER "blog_blogs_check_module_matrix_trigger" BEFORE INSERT ON "blog_blogs" FOR EACH ROW EXECUTE PROCEDURE "check_module_matrix" ();

--Update the updated field on updates
CREATE TRIGGER "blog_blogs_updated_trigger" BEFORE UPDATE ON "blog_blogs" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Dynamic counter increment
CREATE TRIGGER "blog_blogs_counter_trigger" BEFORE INSERT ON "blog_blogs" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'com_id', 'net_id', 'via_id', 'matrix_counter');

-- Update the search field
CREATE TRIGGER "blog_blogs_update_search_trigger" BEFORE INSERT OR UPDATE ON "blog_blogs" FOR EACH ROW EXECUTE PROCEDURE "update_search" ('search', 'content', 'A', 'title', 'B', 'heading', 'C', 'summary', 'C', 'meta_title', 'D', 'meta_description', 'D', 'meta_keywords', 'D');

--Update the total_item_count in module_matrix
CREATE TRIGGER "blog_blogs_total_item_count_trigger" AFTER INSERT OR UPDATE OR DELETE ON "blog_blogs" FOR EACH ROW EXECUTE PROCEDURE "update_total_count" ('module_matrix', 'total_item_count', 'false', 'com_id', 'com_id', 'net_id', 'net_id', 'via_id', 'via_id', 'module_id', 'module_id', 'counter', 'matrix_counter');


----------------------------------------------------------------------------------------------------


