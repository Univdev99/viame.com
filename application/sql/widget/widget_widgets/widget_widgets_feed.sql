DROP TABLE IF EXISTS widget_widgets_feed CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE widget_widgets_feed (
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
    
    widget_counter      bigint NOT NULL CHECK (widget_counter >= 1),
    
    modified            bigint NOT NULL DEFAULT CEIL(EXTRACT(EPOCH FROM now())),
    lifetime            bigint NOT NULL DEFAULT 3600,
    
    data                text
    
    CONSTRAINT valid_space CHECK ((com_id > 0 AND net_id = 0 AND via_id = 0) OR (com_id = 0 AND net_id > 0 AND via_id = 0) OR (com_id = 0 AND net_id = 0 AND via_id > 0))
);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX widget_widgets_feed_com_net_via_counter_x ON widget_widgets_feed (com_id, net_id, via_id, widget_counter);

ALTER TABLE public.widget_widgets_feed OWNER TO vmdbuser;


----------------------------------------------------------------------------------------------------


