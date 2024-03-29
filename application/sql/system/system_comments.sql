DROP TABLE IF EXISTS system_comments CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE system_comments (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    com_id              bigint NOT NULL DEFAULT 0
    						REFERENCES system_communities (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,  -- If community id gets deleted set to DEFAULT; if updated, cascade
    net_id              bigint NOT NULL DEFAULT 0
    						REFERENCES network_networks (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,  -- If community id gets deleted set to DEFAULT; if updated, cascade
    via_id              bigint NOT NULL DEFAULT 0
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE, -- If profile id gets deleted set to DEFAULT; if updated, cascade
    
    module_id           bigint NOT NULL DEFAULT 0
    						REFERENCES module_modules (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
    matrix_counter      bigint NOT NULL DEFAULT 1 CHECK (matrix_counter >= 1),
    
    item_counter        bigint NOT NULL DEFAULT 1 CHECK (item_counter >= 1),
    
    counter             bigint NOT NULL DEFAULT 1 CHECK (counter >= 1),
    
    profile_id          bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE SET DEFAULT
    						ON UPDATE CASCADE, -- If profile id gets deleted set to DEFAULT; if updated, cascade
    
    title               varchar(256),
    content             text NOT NULL,
    
    parent_id           bigint, --  CHECK (parent_id < id) Delay Reference Check Until After Table Creation
    
    total_comments_count    bigint DEFAULT 0 CHECK (total_comments_count >= 0),
    total_ratings_count     bigint DEFAULT 0 CHECK (total_ratings_count >= 0),
    rating                  real DEFAULT 0 CHECK (rating >= 0),
    
    search              tsvector,
    
    --status              bool,
    
    ip_address          inet,
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't'
    
    CONSTRAINT valid_space CHECK ((com_id > 0 AND net_id = 0 AND via_id = 0) OR (com_id = 0 AND net_id > 0 AND via_id = 0) OR (com_id = 0 AND net_id = 0 AND via_id > 0)),
    FOREIGN KEY (com_id, net_id, via_id, module_id, matrix_counter) REFERENCES module_matrix (com_id, net_id, via_id, module_id, counter) MATCH SIMPLE ON DELETE CASCADE ON UPDATE CASCADE
);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX system_comments_com_net_via_matrix_counter_counter_x ON system_comments (com_id, net_id, via_id, module_id, matrix_counter, item_counter, counter);
--CREATE INDEX system_comments_com_matrix_counter_profile_active_x ON system_comments (com_id, matrix_counter, profile_id, active);
--CREATE INDEX system_comments_net_matrix_counter_profile_active_x ON system_comments (net_id, matrix_counter, profile_id, active);
--CREATE INDEX system_comments_via_matrix_counter_profile_active_x ON system_comments (via_id, matrix_counter, profile_id, active);

CREATE INDEX system_comments_search_index ON system_comments USING gin(search);

ALTER TABLE public.system_comments OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "system_comments_updated_trigger" BEFORE UPDATE ON "system_comments" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Dynamic counter increment
CREATE TRIGGER "system_comments_counter_trigger" BEFORE INSERT ON "system_comments" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'com_id', 'net_id', 'via_id', 'module_id', 'matrix_counter', 'item_counter');

-- Update the search field
CREATE TRIGGER "system_comments_update_search_trigger" BEFORE INSERT OR UPDATE ON "system_comments" FOR EACH ROW EXECUTE PROCEDURE "update_search" ('search', 'content', 'A');

--Update the total_item_count in module_matrix
CREATE TRIGGER "system_comments_total_item_count_trigger" AFTER INSERT OR UPDATE OR DELETE ON "system_comments" FOR EACH ROW EXECUTE PROCEDURE "update_total_count" ('DYNAMIC', 'total_comments_count', 'false', 'com_id', 'com_id', 'net_id', 'net_id', 'via_id', 'via_id', 'module_id', 'module_id', 'matrix_counter', 'matrix_counter', 'counter', 'item_counter', 'ANDSELF', 'counter', 'parent_id');


----------------------------------------------------------------------------------------------------


