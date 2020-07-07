DROP TABLE IF EXISTS module_views_counts CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE module_views_counts (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    com_id              bigint NOT NULL DEFAULT 0
    						REFERENCES system_communities (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    net_id              bigint NOT NULL DEFAULT 0
    						REFERENCES network_networks (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    via_id              bigint NOT NULL DEFAULT 0
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
    module_id           bigint NOT NULL DEFAULT 0
    						REFERENCES module_modules (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
    matrix_counter      bigint NOT NULL DEFAULT 0 CHECK (matrix_counter >= 0),
    
    item_counter        bigint NOT NULL DEFAULT 0 CHECK (item_counter >= 0),
    
    profile_id          bigint NOT NULL DEFAULT 0
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
    ip_address          inet,
    
    active              bool DEFAULT 't',
    
    CONSTRAINT valid_space CHECK ((com_id > 0 AND net_id = 0 AND via_id = 0) OR (com_id = 0 AND net_id > 0 AND via_id = 0) OR (com_id = 0 AND net_id = 0 AND via_id > 0)),
    FOREIGN KEY (com_id, net_id, via_id, module_id, matrix_counter) REFERENCES module_matrix (com_id, net_id, via_id, module_id, counter) MATCH SIMPLE ON UPDATE CASCADE ON DELETE CASCADE
    -- Cant foreign key against module_template as there is nothing in there.  Everything is inherited from there.  module_matrix is better than nothing i guess...
);

ALTER TABLE public.module_views_counts OWNER TO vmdbuser;

--Update the total_views_count in module_template
CREATE TRIGGER "module_views_counts_total_views_count_trigger" AFTER INSERT ON "module_views_counts" FOR EACH ROW EXECUTE PROCEDURE "update_total_count" ('module_template', 'total_views_count', 'false', 'com_id', 'com_id', 'net_id', 'net_id', 'via_id', 'via_id', 'module_id', 'module_id', 'matrix_counter', 'matrix_counter', 'counter', 'item_counter');


----------------------------------------------------------------------------------------------------


