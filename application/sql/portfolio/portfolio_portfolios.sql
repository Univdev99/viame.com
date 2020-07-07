DROP TABLE IF EXISTS portfolio_portfolios CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE portfolio_portfolios (
    currency_code       varchar(8) DEFAULT 'USD'
                            REFERENCES system_currencies (code)
    						ON DELETE SET DEFAULT
    						ON UPDATE CASCADE,
    
    cash                numeric DEFAULT 0,
    
    total_position_count    bigint DEFAULT 0 CHECK (total_position_count >= 0),
    
    --CONSTRAINT valid_space CHECK ((com_id > 0 AND net_id = 0 AND via_id = 0) OR (com_id = 0 AND net_id > 0 AND via_id = 0) OR (com_id = 0 AND net_id = 0 AND via_id > 0)),
    FOREIGN KEY (com_id, net_id, via_id, module_id, matrix_counter) REFERENCES module_matrix (com_id, net_id, via_id, module_id, counter) MATCH SIMPLE ON DELETE CASCADE ON UPDATE CASCADE
)
INHERITS (module_template);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX portfolio_portfolios_com_net_via_matrix_counter_counter_x ON portfolio_portfolios (com_id, net_id, via_id, matrix_counter, counter);
--CREATE INDEX portfolio_portfolios_com_matrix_counter_profile_active_x ON portfolio_portfolios (com_id, matrix_counter, profile_id, active);
--CREATE INDEX portfolio_portfolios_net_matrix_counter_profile_active_x ON portfolio_portfolios (net_id, matrix_counter, profile_id, active);
--CREATE INDEX portfolio_portfolios_via_matrix_counter_profile_active_x ON portfolio_portfolios (via_id, matrix_counter, profile_id, active);

CREATE INDEX portfolio_portfolios_search_index ON portfolio_portfolios USING gin(search);

ALTER TABLE public.portfolio_portfolios OWNER TO vmdbuser;

-- Dynamic check module_matrix
CREATE TRIGGER "portfolio_portfolios_check_module_matrix_trigger" BEFORE INSERT ON "portfolio_portfolios" FOR EACH ROW EXECUTE PROCEDURE "check_module_matrix" ();

--Update the updated field on updates
CREATE TRIGGER "portfolio_portfolios_updated_trigger" BEFORE UPDATE ON "portfolio_portfolios" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Dynamic counter increment
CREATE TRIGGER "portfolio_portfolios_counter_trigger" BEFORE INSERT ON "portfolio_portfolios" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'com_id', 'net_id', 'via_id', 'matrix_counter');

-- Update the search field
CREATE TRIGGER "portfolio_portfolios_update_search_trigger" BEFORE INSERT OR UPDATE ON "portfolio_portfolios" FOR EACH ROW EXECUTE PROCEDURE "update_search" ('search', 'title', 'B', 'heading', 'C', 'summary', 'C', 'meta_title', 'D', 'meta_description', 'D', 'meta_keywords', 'D');

--Update the total_item_count in module_matrix
CREATE TRIGGER "portfolio_portfolios_total_item_count_trigger" AFTER INSERT OR UPDATE OR DELETE ON "portfolio_portfolios" FOR EACH ROW EXECUTE PROCEDURE "update_total_count" ('module_matrix', 'total_item_count', 'false', 'com_id', 'com_id', 'net_id', 'net_id', 'via_id', 'via_id', 'module_id', 'module_id', 'counter', 'matrix_counter');


----------------------------------------------------------------------------------------------------


