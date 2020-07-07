DROP TABLE IF EXISTS module_template CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE module_template (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    modified            timestamp WITH TIME ZONE CHECK (modified >= creation),
    published           timestamp WITH TIME ZONE CHECK (modified >= creation),
    
    module_id           bigint NOT NULL DEFAULT 0
    						REFERENCES module_modules (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
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
    
    matrix_counter      bigint NOT NULL DEFAULT 1 CHECK (matrix_counter >= 1),
    
    counter             bigint NOT NULL DEFAULT 1 CHECK (counter >= 1),
    
    profile_id          bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE, -- If profile id gets deleted set to DEFAULT; if updated, cascade
    
    ip_address          inet,
    
    symbols             bigint[],
    
    title               varchar(512) NOT NULL,
    heading             varchar(512),
    summary             varchar(1024),
    more_link           varchar(32),
    widget_image_url    varchar(512),
    
    meta_title          varchar(256),
    meta_description    varchar(512),
    meta_keywords       varchar(512),
    
    content             text,
    
    allow_comments      boolean DEFAULT 't',
    allow_ratings       boolean DEFAULT 't',
    
    total_comments_count    bigint DEFAULT 0 CHECK (total_comments_count >= 0),
    total_ratings_count     bigint DEFAULT 0 CHECK (total_ratings_count >= 0),
    rating                  real DEFAULT 0 CHECK (rating >= 0),
    
    total_views_count    bigint DEFAULT 0 CHECK (total_views_count >= 0),
    
    activation          timestamp WITH TIME ZONE CHECK (activation >= creation),
    expiration          timestamp WITH TIME ZONE CHECK (expiration >= creation AND (activation ISNULL OR expiration > activation)),
    
    show_on_fail        boolean DEFAULT 'f',
    
    quip                text,
    search              tsvector,
    
    google_news_approved        boolean DEFAULT 'f',
    
    acl                 boolean,
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't'
    
    CONSTRAINT valid_space CHECK ((com_id > 0 AND net_id = 0 AND via_id = 0) OR (com_id = 0 AND net_id > 0 AND via_id = 0) OR (com_id = 0 AND net_id = 0 AND via_id > 0)),
    FOREIGN KEY (com_id, net_id, via_id, module_id, matrix_counter) REFERENCES module_matrix (com_id, net_id, via_id, module_id, counter) MATCH SIMPLE ON DELETE CASCADE ON UPDATE CASCADE
);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX module_template_com_net_via_module_matrix_counter_counter_x ON module_template (com_id, net_id, via_id, module_id, matrix_counter, counter);
--CREATE INDEX module_template_com_matrix_counter_profile_active_x ON module_template (com_id, matrix_counter, profile_id, active);
--CREATE INDEX module_template_net_matrix_counter_profile_active_x ON module_template (net_id, matrix_counter, profile_id, active);
--CREATE INDEX module_template_via_matrix_counter_profile_active_x ON module_template (via_id, matrix_counter, profile_id, active);

CREATE INDEX module_template_search_index ON module_template USING gin(search);

ALTER TABLE public.module_template OWNER TO vmdbuser;

-- Dynamic check module_matrix
CREATE TRIGGER "module_template_check_module_matrix_trigger" BEFORE INSERT ON "module_template" FOR EACH ROW EXECUTE PROCEDURE "check_module_matrix" ();

--Update the updated field on updates
CREATE TRIGGER "module_template_updated_trigger" BEFORE UPDATE ON "module_template" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Dynamic counter increment
CREATE TRIGGER "module_template_counter_trigger" BEFORE INSERT ON "module_template" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'com_id', 'net_id', 'via_id', 'matrix_counter');

-- Update the search field
CREATE TRIGGER "module_template_update_search_trigger" BEFORE INSERT OR UPDATE ON "module_template" FOR EACH ROW EXECUTE PROCEDURE "update_search" ('search', 'content', 'A', 'title', 'B', 'heading', 'C', 'summary', 'C', 'meta_title', 'D', 'meta_description', 'D', 'meta_keywords', 'D');

--Update the total_item_count in module_matrix
CREATE TRIGGER "module_template_total_item_count_trigger" AFTER INSERT OR UPDATE OR DELETE ON "module_template" FOR EACH ROW EXECUTE PROCEDURE "update_total_count" ('module_matrix', 'total_item_count', 'false', 'com_id', 'com_id', 'net_id', 'net_id', 'via_id', 'via_id', 'module_id', 'module_id', 'counter', 'matrix_counter');


----------------------------------------------------------------------------------------------------


