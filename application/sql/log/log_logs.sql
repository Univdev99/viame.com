DROP TABLE IF EXISTS log_logs CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE log_logs (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    com_id              bigint NOT NULL DEFAULT 0
    						REFERENCES system_communities (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,  -- If community id gets deleted set to DEFAULT; if updated, cascade
    net_id              bigint NOT NULL DEFAULT 0
    						REFERENCES network_networks (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,  -- If network id gets deleted set to DEFAULT; if updated, cascade
    via_id              bigint NOT NULL DEFAULT 0
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE, -- If profile id gets deleted set to DEFAULT; if updated, cascade
    module_id           bigint NOT NULL DEFAULT 0
    						REFERENCES module_modules (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE, -- If module id gets deleted set to DEFAULT; if updated, cascade
    matrix_counter      bigint NOT NULL DEFAULT 0 CHECK (matrix_counter >= 0),
    item_counter        bigint NOT NULL DEFAULT 0 CHECK (item_counter >= 0),
    
    profile_id          bigint NOT NULL DEFAULT 0
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE, -- If profile id gets deleted set to DEFAULT; if updated, cascade
    ip_address          inet,
    						
    priority            smallint,
    priority_name       varchar(32),
    
    message             text,
    parameter_values    text[],
    
    active              bool DEFAULT 't'
    
    --CONSTRAINT valid_space CHECK ((com_id > 0 AND net_id = 0 AND via_id = 0) OR (com_id = 0 AND net_id > 0 AND via_id = 0) OR (com_id = 0 AND net_id = 0 AND via_id > 0))
);

--CREATE INDEX log_logs_com_id_x ON log_logs (com_id);
--CREATE INDEX log_logs_net_id_x ON log_logs (net_id);
--CREATE INDEX log_logs_via_id_x ON log_logs (via_id);
--CREATE INDEX log_logs_profile_id_x ON log_logs (profile_id);

ALTER TABLE public.log_logs OWNER TO vmdbuser;


----------------------------------------------------------------------------------------------------


