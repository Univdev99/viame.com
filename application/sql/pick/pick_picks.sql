DROP TABLE IF EXISTS pick_picks CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE pick_picks (
    content             text,
    
    symbol_id           bigint NOT NULL
    						REFERENCES quote_symbols (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    position            smallint NOT NULL DEFAULT 1 CHECK (position = 1 OR position = -1),
    allocation          real NOT NULL CHECK (allocation > 0 AND allocation <= 100),
    
    risk                smallint,
    target_date         date,
    target_price        numeric,
    timeframe           smallint,
    suggested_stop_loss numeric,
    trailing_stop_loss              real,
    trailing_stop_loss_type         smallint DEFAULT 0,
    trailing_stop_loss_activation   numeric,
    live_stop_loss      boolean DEFAULT 'f',
    notes               text,
    
    holding             smallint,
    disclosure          text,

    open_datestamp      timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    open_temp_price     numeric NOT NULL,
    open_price          numeric,
    close_datestamp     timestamp WITH TIME ZONE,
    close_temp_price    numeric,
    close_price         numeric,
    
    partial_close_parent_id bigint,
    
    --CONSTRAINT valid_space CHECK ((com_id > 0 AND net_id = 0 AND via_id = 0) OR (com_id = 0 AND net_id > 0 AND via_id = 0) OR (com_id = 0 AND net_id = 0 AND via_id > 0)),
    FOREIGN KEY (com_id, net_id, via_id, module_id, matrix_counter) REFERENCES module_matrix (com_id, net_id, via_id, module_id, counter) MATCH SIMPLE ON DELETE CASCADE ON UPDATE CASCADE
)
INHERITS (module_template);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX pick_picks_com_net_via_matrix_counter_counter_x ON pick_picks (com_id, net_id, via_id, matrix_counter, counter);
--CREATE INDEX pick_picks_com_matrix_counter_profile_active_x ON pick_picks (com_id, matrix_counter, profile_id, active);
--CREATE INDEX pick_picks_net_matrix_counter_profile_active_x ON pick_picks (net_id, matrix_counter, profile_id, active);
--CREATE INDEX pick_picks_via_matrix_counter_profile_active_x ON pick_picks (via_id, matrix_counter, profile_id, active);

CREATE INDEX pick_picks_search_index ON pick_picks USING gin(search);

ALTER TABLE public.pick_picks OWNER TO vmdbuser;

-- Dynamic check module_matrix
CREATE TRIGGER "pick_picks_check_module_matrix_trigger" BEFORE INSERT ON "pick_picks" FOR EACH ROW EXECUTE PROCEDURE "check_module_matrix" ();

--Update the updated field on updates
CREATE TRIGGER "pick_picks_updated_trigger" BEFORE UPDATE ON "pick_picks" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Dynamic counter increment
CREATE TRIGGER "pick_picks_counter_trigger" BEFORE INSERT ON "pick_picks" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'com_id', 'net_id', 'via_id', 'matrix_counter');

-- Update the search field
CREATE TRIGGER "pick_picks_update_search_trigger" BEFORE INSERT OR UPDATE ON "pick_picks" FOR EACH ROW EXECUTE PROCEDURE "update_search" ('search', 'content', 'A', 'title', 'B', 'heading', 'C', 'summary', 'C', 'notes', 'meta_title', 'D', 'meta_description', 'D', 'meta_keywords', 'D', 'disclosure', 'D');

--Update the total_item_count in module_matrix
CREATE TRIGGER "pick_picks_total_item_count_trigger" AFTER INSERT OR UPDATE OR DELETE ON "pick_picks" FOR EACH ROW EXECUTE PROCEDURE "update_total_count" ('module_matrix', 'total_item_count', 'false', 'com_id', 'com_id', 'net_id', 'net_id', 'via_id', 'via_id', 'module_id', 'module_id', 'counter', 'matrix_counter');

-- Trigger to sync the symbols field with the symbol id
-- Function to make symbols equal to array with current symbol_id
DROP FUNCTION IF EXISTS "sync_pick_symbols" () CASCADE; -- ALSO DROPS ALL TRIGGERS TO THIS FUNCTION
CREATE OR REPLACE FUNCTION "sync_pick_symbols" () RETURNS trigger AS $$
  BEGIN
    NEW.symbols := ARRAY[NEW.symbol_id];
    RETURN NEW;
  END;
$$ LANGUAGE 'plpgsql';
CREATE TRIGGER "pick_picks_sync_symbols_trigger" BEFORE INSERT OR UPDATE ON "pick_picks" FOR EACH ROW EXECUTE PROCEDURE "sync_pick_symbols" ();


----------------------------------------------------------------------------------------------------


