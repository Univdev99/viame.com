DROP TABLE IF EXISTS analysis_analysiss CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE analysis_analysiss (
    content             text NOT NULL,
    
    --sector_id         bigint
    --						REFERENCES company_sectors (id)
    --						ON DELETE CASCADE
    --						ON UPDATE CASCADE, -- If profile id gets deleted set to DEFAULT; if updated, cascade
    
    analysis_action     smallint,
    recommendation      smallint,
    timeframe           smallint,
    risk                smallint,
    holding             smallint,
    disclosure          text,
    
    --CONSTRAINT valid_space CHECK ((com_id > 0 AND net_id = 0 AND via_id = 0) OR (com_id = 0 AND net_id > 0 AND via_id = 0) OR (com_id = 0 AND net_id = 0 AND via_id > 0)),
    FOREIGN KEY (com_id, net_id, via_id, module_id, matrix_counter) REFERENCES module_matrix (com_id, net_id, via_id, module_id, counter) MATCH SIMPLE ON DELETE CASCADE ON UPDATE CASCADE
)
INHERITS (module_template);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX analysis_analysiss_com_net_via_matrix_counter_counter_x ON analysis_analysiss (com_id, net_id, via_id, matrix_counter, counter);
--CREATE INDEX analysis_analysiss_com_matrix_counter_profile_active_x ON analysis_analysiss (com_id, matrix_counter, profile_id, active);
--CREATE INDEX analysis_analysiss_net_matrix_counter_profile_active_x ON analysis_analysiss (net_id, matrix_counter, profile_id, active);
--CREATE INDEX analysis_analysiss_via_matrix_counter_profile_active_x ON analysis_analysiss (via_id, matrix_counter, profile_id, active);

CREATE INDEX analysis_analysiss_search_index ON analysis_analysiss USING gin(search);

ALTER TABLE public.analysis_analysiss OWNER TO vmdbuser;

-- Dynamic check module_matrix
CREATE TRIGGER "analysis_analysiss_check_module_matrix_trigger" BEFORE INSERT ON "analysis_analysiss" FOR EACH ROW EXECUTE PROCEDURE "check_module_matrix" ();

--Update the updated field on updates
CREATE TRIGGER "analysis_analysiss_updated_trigger" BEFORE UPDATE ON "analysis_analysiss" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Dynamic counter increment
CREATE TRIGGER "analysis_analysiss_counter_trigger" BEFORE INSERT ON "analysis_analysiss" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'com_id', 'net_id', 'via_id', 'matrix_counter');

-- Update the search field
CREATE TRIGGER "analysis_analysiss_update_search_trigger" BEFORE INSERT OR UPDATE ON "analysis_analysiss" FOR EACH ROW EXECUTE PROCEDURE "update_search" ('search', 'content', 'A', 'title', 'B', 'heading', 'C', 'summary', 'C', 'meta_title', 'D', 'meta_description', 'D', 'meta_keywords', 'D');

--Update the total_item_count in module_matrix
CREATE TRIGGER "analysis_analysiss_total_item_count_trigger" AFTER INSERT OR UPDATE OR DELETE ON "analysis_analysiss" FOR EACH ROW EXECUTE PROCEDURE "update_total_count" ('module_matrix', 'total_item_count', 'false', 'com_id', 'com_id', 'net_id', 'net_id', 'via_id', 'via_id', 'module_id', 'module_id', 'counter', 'matrix_counter');


----------------------------------------------------------------------------------------------------


