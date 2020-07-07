DROP TABLE IF EXISTS portfolio_positions CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE portfolio_positions (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
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
    
    item_counter        bigint NOT NULL DEFAULT 1 CHECK (counter >= 1),
    
    counter             bigint NOT NULL DEFAULT 1 CHECK (counter >= 1),
    
    profile_id          bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE, -- If profile id gets deleted set to DEFAULT; if updated, cascade
    						
    symbol_id           bigint NOT NULL
    						REFERENCES quote_symbols (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    position            smallint NOT NULL DEFAULT 1 CHECK (position = 1 OR position = -1),
    shares              double precision NOT NULL DEFAULT 1,
    --allocation          real NOT NULL,
    price               numeric,
    purchase_date       date,
    upper_limit         numeric,
    lower_limit         numeric,
    notes               text,
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't'
    
    CONSTRAINT valid_space CHECK ((com_id > 0 AND net_id = 0 AND via_id = 0) OR (com_id = 0 AND net_id > 0 AND via_id = 0) OR (com_id = 0 AND net_id = 0 AND via_id > 0)),
    FOREIGN KEY (com_id, net_id, via_id, matrix_counter, item_counter) REFERENCES portfolio_portfolios (com_id, net_id, via_id, matrix_counter, counter) MATCH SIMPLE ON DELETE CASCADE ON UPDATE CASCADE
);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX portfolio_positions_com_net_via_matrix_counter_counter_x ON portfolio_positions (com_id, net_id, via_id, matrix_counter, item_counter, counter);
--CREATE INDEX portfolio_positions_com_matrix_counter_profile_active_x ON portfolio_positions (com_id, matrix_counter, profile_id, active);
--CREATE INDEX portfolio_positions_net_matrix_counter_profile_active_x ON portfolio_positions (net_id, matrix_counter, profile_id, active);
--CREATE INDEX portfolio_positions_via_matrix_counter_profile_active_x ON portfolio_positions (via_id, matrix_counter, profile_id, active);

ALTER TABLE public.portfolio_positions OWNER TO vmdbuser;

-- Dynamic check module_matrix
--CREATE TRIGGER "portfolio_positions_check_module_matrix_trigger" BEFORE INSERT ON "portfolio_positions" FOR EACH ROW EXECUTE PROCEDURE "check_module_matrix" ();

--Update the updated field on updates
CREATE TRIGGER "portfolio_positions_updated_trigger" BEFORE UPDATE ON "portfolio_positions" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Dynamic counter increment
CREATE TRIGGER "portfolio_positions_counter_trigger" BEFORE INSERT ON "portfolio_positions" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'com_id', 'net_id', 'via_id', 'matrix_counter', 'item_counter');

--Update the total_item_count in module_matrix
CREATE TRIGGER "portfolio_positions_total_item_count_trigger" AFTER INSERT OR UPDATE OR DELETE ON "portfolio_positions" FOR EACH ROW EXECUTE PROCEDURE "update_total_count" ('portfolio_portfolios', 'total_position_count', 'false', 'com_id', 'com_id', 'net_id', 'net_id', 'via_id', 'via_id', 'module_id', 'module_id', 'matrix_counter', 'matrix_counter', 'counter', 'item_counter');


----------------------------------------------------------------------------------------------------


