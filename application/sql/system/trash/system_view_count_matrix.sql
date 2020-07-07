DROP TABLE IF EXISTS system_view_count_matrix CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE system_view_count_matrix (
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

    total_view_counter  bigint DEFAULT 0 CHECK (total_view_counter >= 0),
    
    CONSTRAINT valid_space CHECK ((com_id > 0 AND net_id = 0 AND via_id = 0) OR (com_id = 0 AND net_id > 0 AND via_id = 0) OR (com_id = 0 AND net_id = 0 AND via_id > 0))
);

CREATE UNIQUE INDEX system_view_count_matrix_com_net_via_module_counter_x ON system_view_count_matrix (com_id, net_id, via_id, module_id, matrix_counter, item_counter);

ALTER TABLE public.system_view_count_matrix OWNER TO vmdbuser;


----------------------------------------------------------------------------------------------------


