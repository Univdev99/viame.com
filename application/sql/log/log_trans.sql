DROP TABLE IF EXISTS log_trans CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE log_trans (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    identifier          varchar(128),
    
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
    acl_counter         bigint NOT NULL DEFAULT 1 CHECK (acl_counter >= 1),
    
    profile_id          bigint NOT NULL DEFAULT 0
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE, -- If profile id gets deleted set to DEFAULT; if updated, cascade
    ip_address          inet,
    
    amount              real NOT NULL,
    
    referrer_profile_id bigint
    						REFERENCES profile_profiles (id)
    						ON DELETE SET DEFAULT
    						ON UPDATE CASCADE,
    						
    revenue_share_override  smallint CHECK (revenue_share_override >= 0 AND revenue_share_override <= 100),
    
    message             text,
    raw_result          text,
    serialized_result   text,
    
    active              bool DEFAULT 't'
    
    CONSTRAINT valid_space CHECK ((com_id > 0 AND net_id = 0 AND via_id = 0) OR (com_id = 0 AND net_id > 0 AND via_id = 0) OR (com_id = 0 AND net_id = 0 AND via_id > 0)),
    FOREIGN KEY (com_id, net_id, via_id, module_id, matrix_counter) REFERENCES module_matrix (com_id, net_id, via_id, module_id, counter) MATCH SIMPLE ON DELETE CASCADE ON UPDATE CASCADE
);

--CREATE INDEX log_trans_com_id_x ON log_trans (com_id);
--CREATE INDEX log_trans_net_id_x ON log_trans (net_id);
--CREATE INDEX log_trans_via_id_x ON log_trans (via_id);
--CREATE INDEX log_trans_profile_id_x ON log_trans (profile_id);

ALTER TABLE public.log_trans OWNER TO vmdbuser;


----------------------------------------------------------------------------------------------------


