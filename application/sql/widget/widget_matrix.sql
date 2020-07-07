DROP TABLE IF EXISTS widget_matrix CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE widget_matrix (
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
    
    widget_id           bigint NOT NULL DEFAULT 0
    						REFERENCES widget_widgets (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
    counter             bigint NOT NULL DEFAULT 1 CHECK (counter >= 0),

    profile_id          bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE, -- If profile id gets deleted set to DEFAULT; if updated, cascade
    						
    display             varchar(256),
    display_url         varchar(512),
    secondary           varchar(256),
    secondary_url       varchar(512),
    
    parameter_values    text[],
	
	widget              boolean DEFAULT 't',
	display_cm          boolean,
	
	orderby             real,

	updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't', -- NULL=created but not yet active and not deactivated
    
    CONSTRAINT valid_space CHECK ((com_id > 0 AND net_id = 0 AND via_id = 0) OR (com_id = 0 AND net_id > 0 AND via_id = 0) OR (com_id = 0 AND net_id = 0 AND via_id > 0))
);

CREATE UNIQUE INDEX widget_matrix_com_net_via_widget_counter_x ON widget_matrix (com_id, net_id, via_id, widget_id, counter);
--CREATE INDEX widget_matrix_com_widget_counter_active_x ON widget_matrix (com_id, widget_id, counter, active);
--CREATE INDEX widget_matrix_net_widget_counter_active_x ON widget_matrix (net_id, widget_id, counter, active);
--CREATE INDEX widget_matrix_via_widget_counter_active_x ON widget_matrix (via_id, widget_id, counter, active);

ALTER TABLE public.widget_matrix OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "widget_matrix_updated_trigger" BEFORE UPDATE ON "widget_matrix" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Dynamically update the counter
CREATE TRIGGER "widget_matrix_counter_trigger" BEFORE INSERT ON "widget_matrix" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'com_id', 'net_id', 'via_id', 'widget_id');


----------------------------------------------------------------------------------------------------


